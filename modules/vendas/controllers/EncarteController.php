<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\Encarte;
use app\modules\vendas\models\EncarteProduto;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\services\EncartePdfService;
use app\modules\evolution\services\EvolutionService;
use kartik\mpdf\Pdf;

class EncarteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'gerar' => ['POST'],
                    'enviar-whatsapp' => ['POST'],
                    'postar-status-whatsapp' => ['POST'],
                    'excluir' => ['POST'],
                ],
            ],
        ];
    }

    protected function getLojaId()
    {
        $usuario = Yii::$app->user->identity;
        if (!$usuario) return null;

        if ($usuario->eh_dono_loja === true || $usuario->eh_dono_loja === 't' || $usuario->eh_dono_loja === 1) {
            return $usuario->id;
        }

        $colaborador = Colaborador::getColaboradorLogado();
        return $colaborador ? $colaborador->usuario_id : $usuario->id;
    }

    /**
     * Tela Principal de Gestão de Encartes Digitais
     */
    public function actionIndex()
    {
        $this->layout = '@app/modules/vendas/views/layouts/main-vendas';
        $lojaId = $this->getLojaId();

        $encartes = Encarte::find()
            ->where(['usuario_id' => $lojaId])
            ->orderBy(['created_at' => SORT_DESC])
            ->with(['encarteProdutos.produto.fotos'])
            ->all();

        $totalEncartes = count($encartes);
        $totalAtivos = 0;
        $totalInativos = 0;
        $totalVisualizacoes = 0;
        $totalProdutos = 0;

        foreach ($encartes as $e) {
            if ($e->status === 'ativo') {
                $totalAtivos++;
            } else {
                $totalInativos++;
            }
            $totalVisualizacoes += (int)$e->visualizacoes_count;
            $totalProdutos += count($e->encarteProdutos);
        }

        $loja = \app\models\Usuario::findOne($lojaId);
        $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $lojaId]);

        return $this->render('index', [
            'encartes' => $encartes,
            'metricas' => [
                'total' => $totalEncartes,
                'ativos' => $totalAtivos,
                'inativos' => $totalInativos,
                'visualizacoes' => $totalVisualizacoes,
                'total_produtos' => $totalProdutos,
            ],
            'loja' => $loja,
            'lojaConfig' => $lojaConfig,
        ]);
    }

    /**
     * Ação AJAX para criar/gerar um Encarte Digital a partir dos produtos selecionados.
     */
    public function actionGerar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $produtosIds = $request->post('produtos_ids', []);
        $modoSelecao = strtoupper($request->post('modo_selecao', 'MANUAL'));
        $qtdDesejada = (int)$request->post('qtd_desejada', 0);
        $categoriaId = $request->post('categoria_id', null);
        $filtroFoto = strtoupper($request->post('filtro_foto', 'COM_FOTO')); // COM_FOTO, SEM_FOTO, TODOS

        $titulo = $request->post('titulo', 'Encarte de Ofertas Imbatíveis');
        $subtitulo = $request->post('subtitulo', 'Ofertas válidas enquanto durarem os estoques');
        $estiloLayout = $request->post('estilo_layout', 'flipsnack_supermarket');
        $corTema = $request->post('cor_tema', 'red_gold');
        $ppp = (int)$request->post('produtos_por_pagina', 6);
        $inativarAnteriores = filter_var($request->post('inativar_anteriores', true), FILTER_VALIDATE_BOOLEAN);
        $desmembrarMatriz = filter_var($request->post('desmembrar_matriz', true), FILTER_VALIDATE_BOOLEAN);
        $apenasComEstoque = filter_var($request->post('apenas_com_estoque', true), FILTER_VALIDATE_BOOLEAN);

        if ($modoSelecao === 'TODOS') {
            $query = Produto::find()
                ->where(['usuario_id' => $lojaId, 'ativo' => true]);
            if (!empty($categoriaId) && $categoriaId !== 'TODAS') {
                $query->andWhere(['categoria_id' => $categoriaId]);
            }
            if ($filtroFoto === 'COM_FOTO') {
                $query->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            } elseif ($filtroFoto === 'SEM_FOTO') {
                $query->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            }
            $produtos = $query->orderBy(['nome' => SORT_ASC])->all();

        } elseif ($modoSelecao === 'ALEATORIO') {
            $query = Produto::find()
                ->where(['usuario_id' => $lojaId, 'ativo' => true]);
            if (!empty($categoriaId) && $categoriaId !== 'TODAS') {
                $query->andWhere(['categoria_id' => $categoriaId]);
            }
            if ($filtroFoto === 'COM_FOTO') {
                $query->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            } elseif ($filtroFoto === 'SEM_FOTO') {
                $query->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            }
            $produtos = $query->orderBy(new \yii\db\Expression('RANDOM()'))
                ->limit($qtdDesejada)
                ->all();

        } elseif ($modoSelecao === 'MAIS_VENDIDOS') {
            $query = Produto::find()
                ->where(['usuario_id' => $lojaId, 'ativo' => true]);
            if (!empty($categoriaId) && $categoriaId !== 'TODAS') {
                $query->andWhere(['categoria_id' => $categoriaId]);
            }
            if ($filtroFoto === 'COM_FOTO') {
                $query->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            } elseif ($filtroFoto === 'SEM_FOTO') {
                $query->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            }
            $produtos = $query->orderBy(['qtd_vendida' => SORT_DESC])
                ->limit($qtdDesejada)
                ->all();

        } elseif ($modoSelecao === 'CATEGORIA') {
            $query = Produto::find()
                ->where(['usuario_id' => $lojaId, 'ativo' => true]);
            if (!empty($categoriaId) && $categoriaId !== 'TODAS') {
                $query->andWhere(['categoria_id' => $categoriaId]);
            }
            if ($filtroFoto === 'COM_FOTO') {
                $query->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            } elseif ($filtroFoto === 'SEM_FOTO') {
                $query->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            }
            $query->orderBy(['nome' => SORT_ASC]);
            if ($qtdDesejada > 0) {
                $query->limit($qtdDesejada);
            }
            $produtos = $query->all();

        } elseif ($modoSelecao === 'QUANTIDADE' && $qtdDesejada > 0) {
            $query = Produto::find()
                ->where(['usuario_id' => $lojaId, 'ativo' => true]);
            if (!empty($categoriaId) && $categoriaId !== 'TODAS') {
                $query->andWhere(['categoria_id' => $categoriaId]);
            }
            if ($filtroFoto === 'COM_FOTO') {
                $query->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            } elseif ($filtroFoto === 'SEM_FOTO') {
                $query->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            }
            $produtos = $query->orderBy(['nome' => SORT_ASC])
                ->limit($qtdDesejada)
                ->all();

        } else {
            // PRODUTOS, PAGINA, MANUAL
            if (empty($produtosIds) || !is_array($produtosIds)) {
                return ['success' => false, 'message' => 'Nenhum produto foi selecionado para o encarte.'];
            }
            $query = Produto::find()
                ->where(['id' => $produtosIds, 'usuario_id' => $lojaId, 'ativo' => true]);
            if ($filtroFoto === 'COM_FOTO') {
                $query->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            } elseif ($filtroFoto === 'SEM_FOTO') {
                $query->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            }
            $produtos = $query->all();
        }

        if (empty($produtos)) {
            return ['success' => false, 'message' => 'Nenhum produto ativo válido encontrado para o encarte.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($inativarAnteriores) {
                Encarte::updateAll(['status' => 'inativo'], ['usuario_id' => $lojaId, 'status' => 'ativo']);
            }

            $encarte = new Encarte();
            $encarte->usuario_id = $lojaId;
            $encarte->status = 'ativo';
            $encarte->titulo = $titulo;
            $encarte->subtitulo = $subtitulo;
            $encarte->estilo_layout = $estiloLayout;
            $encarte->cor_tema = $corTema;
            $encarte->produtos_por_pagina = $ppp > 0 ? $ppp : 6;

            if (!$encarte->save()) {
                throw new \Exception('Erro ao salvar encarte: ' . implode(', ', $encarte->getFirstErrors()));
            }

            $produtosTags = $request->post('produtos_tags', []);

            $ordem = 1;
            foreach ($produtos as $p) {
                // Verificação Inteligente se o produto possui Matriz (Cor/Tamanho)
                $temMatriz = ($desmembrarMatriz && (
                    $p->modo_grade === 'matriz' || 
                    $p->possuiGrade || 
                    \app\modules\vendas\models\ProdutoVariante::find()->where(['produto_id' => $p->id, 'ativo' => true])->exists()
                ));

                if ($temMatriz) {
                    $queryVar = \app\modules\vendas\models\ProdutoVariante::find()
                        ->where(['produto_id' => $p->id, 'ativo' => true])
                        ->orderBy(['cor' => SORT_ASC, 'tamanho' => SORT_ASC]);

                    if ($apenasComEstoque) {
                        $varsComEstoque = (clone $queryVar)->andWhere(['>', 'estoque_atual', 0])->all();
                        $variantes = !empty($varsComEstoque) ? $varsComEstoque : $queryVar->all();
                    } else {
                        $variantes = $queryVar->all();
                    }

                    if (!empty($variantes)) {
                        // Agrupa as variantes por Cor (1 Card por Cor no Encarte)
                        $variantesPorCor = [];
                        foreach ($variantes as $var) {
                            $nomeCor = !empty($var->cor) ? mb_strtoupper(trim($var->cor), 'UTF-8') : 'PADRÃO';
                            $variantesPorCor[$nomeCor][] = $var;
                        }

                        foreach ($variantesPorCor as $corNome => $varsDaCor) {
                            $gradeTamanhos = [];
                            $totalEstoqueCor = 0.0;
                            $refVariante = $varsDaCor[0];
                            $precoOfertaCor = null;

                            foreach ($varsDaCor as $v) {
                                $qtd = (float)$v->estoque_atual;
                                $totalEstoqueCor += $qtd;
                                $gradeTamanhos[] = [
                                    'variante_id' => (string)$v->id,
                                    'tamanho' => (string)$v->tamanho,
                                    'qtd' => (int)$qtd,
                                    'preco' => ($v->preco_venda_sugerido !== null && (float)$v->preco_venda_sugerido > 0)
                                        ? (float)$v->preco_venda_sugerido
                                        : ($p->preco_promocional ? (float)$p->preco_promocional : (float)$p->preco_venda_sugerido)
                                ];
                                if ($precoOfertaCor === null && $v->preco_venda_sugerido !== null && (float)$v->preco_venda_sugerido > 0) {
                                    $precoOfertaCor = (float)$v->preco_venda_sugerido;
                                }
                            }

                            if ($precoOfertaCor === null) {
                                $precoOfertaCor = $p->preco_promocional ? (float)$p->preco_promocional : null;
                            }

                            $encarteItem = new EncarteProduto();
                            $encarteItem->encarte_id = $encarte->id;
                            $encarteItem->produto_id = $p->id;
                            $encarteItem->variante_id = (string)$refVariante->id;
                            $encarteItem->cor = ($corNome !== 'PADRÃO') ? $corNome : null;
                            $encarteItem->tamanho = json_encode($gradeTamanhos, JSON_UNESCAPED_UNICODE);
                            $encarteItem->quantidade = $totalEstoqueCor;
                            $encarteItem->preco_oferta = $precoOfertaCor;
                            $encarteItem->ordem = $ordem++;
                            if (isset($produtosTags[$p->id])) {
                                $encarteItem->tag_promocional = $produtosTags[$p->id];
                            }
                            if (!$encarteItem->save()) {
                                throw new \Exception('Erro ao salvar card por cor da matriz no encarte: ' . implode(', ', $encarteItem->getFirstErrors()));
                            }
                        }
                        continue;
                    }
                }

                // Produto Padrão (Sem matriz ou com desmembramento desativado)
                $encarteItem = new EncarteProduto();
                $encarteItem->encarte_id = $encarte->id;
                $encarteItem->produto_id = $p->id;
                $encarteItem->quantidade = (float)$p->estoque_atual;
                $encarteItem->ordem = $ordem++;
                if (isset($produtosTags[$p->id])) {
                    $encarteItem->tag_promocional = $produtosTags[$p->id];
                }
                if (!$encarteItem->save()) {
                    throw new \Exception('Erro ao salvar item do encarte: ' . implode(', ', $encarteItem->getFirstErrors()));
                }
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Encarte gerado com sucesso!',
                'encarte_id' => $encarte->id,
                'token' => $encarte->token_publico,
                'url_publica' => $encarte->getUrlPublica(),
                'url_pdf' => $encarte->getUrlPdf(),
            ];

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("EncarteController::actionGerar erro: " . $e->getMessage(), __METHOD__);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Ação AJAX para enviar o Encarte público + Anexo PDF via Evolution API.
     */
    public function actionEnviarWhatsapp()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $encarteId = $request->post('encarte_id');
        $telefonesManuais = $request->post('telefones_manuais', '');
        $clientesIds = $request->post('clientes_ids', []);
        $mensagemCustom = $request->post('mensagem_texto', '');

        $encarte = Encarte::findOne(['id' => $encarteId, 'usuario_id' => $lojaId]);
        if (!$encarte) {
            return ['success' => false, 'message' => 'Encarte não encontrado.'];
        }

        // Coleta números de telefone
        $numeros = [];

        if (!empty($clientesIds) && is_array($clientesIds)) {
            $clientes = \app\modules\vendas\models\Clientes::find()
                ->where(['id' => $clientesIds, 'usuario_id' => $lojaId])
                ->all();
            foreach ($clientes as $c) {
                $num = $c->celular ?: $c->telefone;
                if ($num) $numeros[] = $num;
            }
        }

        if (!empty($telefonesManuais)) {
            $linhas = preg_split('/[\s,;\n]+/', $telefonesManuais);
            foreach ($linhas as $l) {
                $clean = preg_replace('/[^0-9]/', '', $l);
                if (strlen($clean) >= 10) {
                    $numeros[] = $clean;
                }
            }
        }

        $numeros = array_unique($numeros);

        if (empty($numeros)) {
            return ['success' => false, 'message' => 'Nenhum número de telefone válido foi fornecido.'];
        }

        try {
            $evolution = new EvolutionService();
            $urlPublica = $encarte->getUrlPublica();
            $urlPdf = $encarte->getUrlPdf();

            // Gera PDF em String Base64 para anexo
            $pdfContent = EncartePdfService::gerarPdf($encarte, Pdf::DEST_STRING);
            $base64Pdf = base64_encode($pdfContent);

            if (!empty($mensagemCustom)) {
                $textoEnvio = trim($mensagemCustom) . "\n\n📖 *Folheto Digital:* {$urlPublica}\n📄 *Baixar PDF:* {$urlPdf}";
            } else {
                $textoEnvio = "🔥 *CONFIRA NOSSO NOVO ENCARTE DE OFERTAS!* 🔥\n\n*{$encarte->titulo}*\n{$encarte->subtitulo}\n\n📖 *Folheto Digital Interativo:* {$urlPublica}\n📄 *Baixar Encarte em PDF:* {$urlPdf}";
            }

            $enviados = 0;
            $erros = 0;

            foreach ($numeros as $idx => $num) {
                if ($idx > 0) {
                    // Pausa humanizada entre disparos (1.2 a 2.5 segundos) para proteção anti-bloqueio WhatsApp
                    usleep(rand(1200000, 2500000));
                }

                // 1. Envia Mensagem de Texto com Link
                $resMsg = $evolution->sendMessage($lojaId, $num, $textoEnvio);

                // 2. Envia PDF em Anexo
                $resDoc = $evolution->sendDocument($lojaId, $num, $base64Pdf, 'encarte_ofertas.pdf', $encarte->titulo);

                if ($resMsg || $resDoc) {
                    $enviados++;
                } else {
                    $erros++;
                }
            }

            return [
                'success' => true,
                'enviados' => $enviados,
                'erros' => $erros,
                'message' => "Encarte disparado! Sucesso: {$enviados}, Falhas: {$erros}.",
            ];

        } catch (\Exception $e) {
            Yii::error("EncarteController::actionEnviarWhatsapp erro: " . $e->getMessage(), __METHOD__);
            return ['success' => false, 'message' => 'Erro ao enviar via WhatsApp: ' . $e->getMessage()];
        }
    }

    /**
     * Retorna contatos aleatórios de clientes da base para disparos de encarte em massa com proteção anti-bloqueio.
     */
    public function actionCarregarClientesZap()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $qtd = max(1, min(100, (int)($request->post('qtd') ?: $request->get('qtd', 20))));

        $clientes = \app\modules\vendas\models\Cliente::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true])
            ->andWhere(['or', ['not', ['telefone' => null]], ['not', ['telefone' => '']]])
            ->orderBy(new \yii\db\Expression('RANDOM()'))
            ->limit($qtd)
            ->all();

        if (empty($clientes)) {
            $clientes = \app\modules\vendas\models\Clientes::find()
                ->where(['usuario_id' => $lojaId, 'ativo' => true])
                ->orderBy(new \yii\db\Expression('RANDOM()'))
                ->limit($qtd)
                ->all();
        }

        $listaTelefones = [];
        $linhasFormatadas = [];

        foreach ($clientes as $c) {
            $numRaw = !empty($c->telefone) ? $c->telefone : (!empty($c->celular) ? $c->celular : '');
            $clean = preg_replace('/[^0-9]/', '', $numRaw);
            if (strlen($clean) >= 10) {
                if (strlen($clean) <= 11 && strpos($clean, '55') !== 0) {
                    $clean = '55' . $clean;
                }
                $nomeCli = !empty($c->nome_completo) ? $c->nome_completo : (!empty($c->nome) ? $c->nome : 'Cliente');
                $listaTelefones[] = $clean;
                $linhasFormatadas[] = "{$clean} # {$nomeCli}";
            }
        }

        return [
            'success' => true,
            'qtd' => count($listaTelefones),
            'texto_formatado' => implode("\n", $linhasFormatadas),
            'message' => count($listaTelefones) . ' cliente(s) aleatório(s) carregado(s) da base com sucesso.'
        ];
    }

    /**
     * Publica o Encarte Digital (Link Público + Imagem de Capa) diretamente no Status / Stories do WhatsApp via Evolution API.
     */
    public function actionPostarStatusWhatsapp()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $encarteId = $request->post('encarte_id');
        $mensagemCustom = $request->post('mensagem_texto', '');

        $encarte = Encarte::findOne(['id' => $encarteId, 'usuario_id' => $lojaId]);
        if (!$encarte) {
            return ['success' => false, 'message' => 'Encarte não encontrado.'];
        }

        try {
            $urlPublica = $encarte->getUrlPublica();
            
            // 1. Busca a imagem de capa (primeiro produto do encarte)
            $imagemCapaUrl = null;
            $primeiroItem = EncarteProduto::find()
                ->where(['encarte_id' => $encarte->id])
                ->orderBy(['ordem' => SORT_ASC])
                ->one();

            if ($primeiroItem && $primeiroItem->produto) {
                $foto = $primeiroItem->produto->fotoPrincipal;
                if ($foto) {
                    if (method_exists($foto, 'getUrlCompleta')) {
                        $imagemCapaUrl = $foto->getUrlCompleta();
                    } elseif (method_exists($foto, 'getUrl')) {
                        $c = ltrim($foto->getUrl(), '/');
                        $imagemCapaUrl = 'https://catalogos.oncode.app.br/' . $c;
                    }
                }
            }

            // Fallback para URL relativa ou imagem padrão se não houver foto principal
            if (empty($imagemCapaUrl)) {
                $baseUrl = \yii\helpers\Url::base(true);
                $imagemCapaUrl = $baseUrl . '/img/encarte-cover-placeholder.png';
            }

            // 2. Monta o texto promocional para o Status do WhatsApp
            if (!empty($mensagemCustom)) {
                $textoStatus = trim($mensagemCustom) . "\n\n📖 *Acesse nosso Folheto Digital:* {$urlPublica}";
            } else {
                $textoStatus = "🔥 *CONFIRA NOSSO NOVO ENCARTE DE OFERTAS!* 🔥\n\n*{$encarte->titulo}*\n{$encarte->subtitulo}\n\n📖 *Acesse o Folheto Digital Interativo:* {$urlPublica}";
            }

            // 3. Executa envio para o Status (status@broadcast) via Evolution API
            $evolution = new EvolutionService();
            $sucesso = $evolution->sendWhatsAppStatus($lojaId, $imagemCapaUrl, $textoStatus);

            if ($sucesso) {
                return [
                    'success' => true,
                    'message' => 'Encarte postado no Status do WhatsApp com sucesso!'
                ];
            } else {
                $erroMsg = $evolution->lastError ?: 'Erro ao publicar no Status do WhatsApp. Verifique se a sua Evolution API está conectada.';
                return ['success' => false, 'message' => $erroMsg];
            }

        } catch (\Exception $e) {
            Yii::error("EncarteController::actionPostarStatusWhatsapp erro: " . $e->getMessage(), __METHOD__);
            return ['success' => false, 'message' => 'Erro ao publicar Status: ' . $e->getMessage()];
        }
    }

    /**
     * Download do PDF no Admin
     */
    public function actionDownloadPdf($id)
    {
        $lojaId = $this->getLojaId();
        $encarte = Encarte::findOne(['id' => $id, 'usuario_id' => $lojaId]);
        if (!$encarte) {
            throw new NotFoundHttpException('Encarte não encontrado.');
        }

        return EncartePdfService::gerarPdf($encarte, Pdf::DEST_BROWSER);
    }

    /**
     * Retorna a lista de categorias do lojista com a contagem de produtos ativos conforme filtro_foto
     */
    public function actionCategoriasComContagem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();
        $filtroFoto = strtoupper(Yii::$app->request->get('filtro_foto', 'COM_FOTO'));

        $categorias = \app\modules\vendas\models\Categoria::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true])
            ->orderBy(['ordem' => SORT_ASC, 'nome' => SORT_ASC])
            ->all();

        $dados = [];
        $totalGeral = 0;

        foreach ($categorias as $cat) {
            $q = Produto::find()
                ->where(['usuario_id' => $lojaId, 'categoria_id' => $cat->id, 'ativo' => true]);
            
            if ($filtroFoto === 'COM_FOTO') {
                $q->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            } elseif ($filtroFoto === 'SEM_FOTO') {
                $q->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
            }

            $count = $q->count();
            
            $dados[] = [
                'id' => (string)$cat->id,
                'nome' => $cat->nome,
                'total_produtos' => (int)$count,
            ];
            $totalGeral += (int)$count;
        }

        return [
            'success' => true,
            'categorias' => $dados,
            'total_geral' => $totalGeral,
            'filtro_foto' => $filtroFoto,
        ];
    }

    /**
     * Retorna lista de produtos de uma categoria para pré-visualização/tags no modal
     */
    public function actionProdutosPorCategoria($categoria_id = null, $qtd = 0, $filtro_foto = 'COM_FOTO')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();
        $filtroFoto = strtoupper(Yii::$app->request->get('filtro_foto', $filtro_foto));

        $query = Produto::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true]);

        if (!empty($categoria_id) && $categoria_id !== 'TODAS') {
            $query->andWhere(['categoria_id' => $categoria_id]);
        }

        if ($filtroFoto === 'COM_FOTO') {
            $query->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
        } elseif ($filtroFoto === 'SEM_FOTO') {
            $query->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
        }

        $query->orderBy(['nome' => SORT_ASC]);

        $qtd = (int)$qtd;
        if ($qtd > 0) {
            $query->limit($qtd);
        }

        $produtos = $query->all();

        $itens = [];
        foreach ($produtos as $p) {
            $fotoUrl = null;
            if ($p->fotoPrincipal) {
                if (method_exists($p->fotoPrincipal, 'getUrlCompleta')) {
                    $fotoUrl = $p->fotoPrincipal->getUrlCompleta();
                } elseif (method_exists($p->fotoPrincipal, 'getUrl')) {
                    $fotoUrl = $p->fotoPrincipal->getUrl();
                }
            }

            $itens[] = [
                'id' => (string)$p->id,
                'nome' => $p->nome,
                'preco_venda' => (float)$p->preco_venda_sugerido,
                'preco_venda_formatado' => number_format((float)($p->preco_promocional ?: $p->preco_venda_sugerido), 2, ',', '.'),
                'categoria_nome' => $p->categoria ? $p->categoria->nome : '',
                'foto_url' => $fotoUrl,
            ];
        }

        return [
            'success' => true,
            'total' => count($itens),
            'produtos' => $itens,
        ];
    }

    /**
     * Retorna a lista de encartes gerados pela loja para gestão de status
     */
    public function actionListar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $encartes = Encarte::find()
            ->where(['usuario_id' => $lojaId])
            ->orderBy(['created_at' => SORT_DESC])
            ->with(['encarteProdutos'])
            ->all();

        $lista = [];
        foreach ($encartes as $enc) {
            $lista[] = [
                'id' => (string)$enc->id,
                'titulo' => $enc->titulo,
                'subtitulo' => $enc->subtitulo,
                'token' => $enc->token_publico,
                'status' => $enc->status ?: 'ativo',
                'visualizacoes' => (int)$enc->visualizacoes_count,
                'total_produtos' => count($enc->encarteProdutos),
                'url_publica' => $enc->getUrlPublica(),
                'url_pdf' => $enc->getUrlPdf(),
                'data_criacao' => date('d/m/Y H:i', strtotime($enc->created_at)),
                'tempo_relativo' => Yii::$app->formatter->asRelativeTime($enc->created_at),
            ];
        }

        return [
            'success' => true,
            'total' => count($lista),
            'encartes' => $lista,
        ];
    }

    /**
     * Alterna o status do encarte entre ativo e inativo
     */
    public function actionAlternarStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $encarte = Encarte::findOne(['id' => $id, 'usuario_id' => $lojaId]);
        if (!$encarte) {
            return ['success' => false, 'message' => 'Encarte não encontrado.'];
        }

        $novoStatus = ($encarte->status === 'ativo') ? 'inativo' : 'ativo';
        $encarte->status = $novoStatus;

        if ($encarte->save(false)) {
            $msg = ($novoStatus === 'ativo') ? 'Encarte ativado com sucesso!' : 'Encarte inativado com sucesso!';
            return [
                'success' => true,
                'message' => $msg,
                'novo_status' => $novoStatus,
            ];
        }

        return ['success' => false, 'message' => 'Erro ao alterar status do encarte.'];
    }

    /**
     * Exclui um encarte e seus itens vinculados
     */
    public function actionExcluir($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $encarte = Encarte::findOne(['id' => $id, 'usuario_id' => $lojaId]);
        if (!$encarte) {
            return ['success' => false, 'message' => 'Encarte não encontrado.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            EncarteProduto::deleteAll(['encarte_id' => $encarte->id]);
            $encarte->delete();
            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Encarte excluído com sucesso!',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("EncarteController::actionExcluir erro: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Erro ao excluir encarte: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Busca produtos da loja para seleção interativa no modal de encarte
     */
    public function actionBuscarProdutos()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();
        $request = Yii::$app->request;

        $q = trim($request->get('q', ''));
        $categoriaId = $request->get('categoria_id', null);
        $filtroFoto = strtoupper($request->get('filtro_foto', 'TODOS'));
        $limite = max(1, min(100, (int)$request->get('limite', 30)));

        $query = Produto::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true]);

        if (!empty($categoriaId) && $categoriaId !== 'TODAS') {
            $query->andWhere(['categoria_id' => $categoriaId]);
        }

        if ($filtroFoto === 'COM_FOTO') {
            $query->andWhere(['in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
        } elseif ($filtroFoto === 'SEM_FOTO') {
            $query->andWhere(['not in', 'id', ProdutoFoto::find()->select('produto_id')->distinct()]);
        }

        if (!empty($q)) {
            $termo = '%' . mb_strtolower($q) . '%';
            $query->andWhere(['or',
                ['ilike', 'nome', $termo],
                ['ilike', 'codigo_referencia', $termo],
                ['ilike', 'codigo_barras', $termo],
            ]);
        }

        $produtos = $query->orderBy(['nome' => SORT_ASC])->limit($limite)->all();

        $itens = [];
        foreach ($produtos as $p) {
            $fotoUrl = null;
            if ($p->fotoPrincipal) {
                $fotoUrl = method_exists($p->fotoPrincipal, 'getUrlCompleta') ? $p->fotoPrincipal->getUrlCompleta() : $p->fotoPrincipal->getUrl();
            } elseif (!empty($p->fotos)) {
                $fotoUrl = method_exists($p->fotos[0], 'getUrlCompleta') ? $p->fotos[0]->getUrlCompleta() : $p->fotos[0]->getUrl();
            }

            // Verifica se tem matriz
            $ehMatriz = ($p->modo_grade === 'matriz' || $p->possuiGrade || \app\modules\vendas\models\ProdutoVariante::find()->where(['produto_id' => $p->id, 'ativo' => true])->exists());
            
            $totalVariantes = 0;
            $resumoCores = [];
            if ($ehMatriz) {
                $vars = \app\modules\vendas\models\ProdutoVariante::find()
                    ->where(['produto_id' => $p->id, 'ativo' => true])
                    ->all();
                $totalVariantes = count($vars);
                foreach ($vars as $v) {
                    if ($v->cor && !in_array($v->cor, $resumoCores)) {
                        $resumoCores[] = $v->cor;
                    }
                }
            }

            $itens[] = [
                'id' => (string)$p->id,
                'nome' => $p->nome,
                'codigo_referencia' => $p->codigo_referencia ?: '',
                'codigo_barras' => $p->codigo_barras ?: '',
                'preco_venda' => (float)$p->preco_venda_sugerido,
                'preco_formatado' => number_format((float)($p->preco_promocional ?: $p->preco_venda_sugerido), 2, ',', '.'),
                'estoque_atual' => (float)$p->estoque_atual,
                'categoria_nome' => $p->categoria ? $p->categoria->nome : 'Sem Categoria',
                'foto_url' => $fotoUrl,
                'eh_matriz' => $ehMatriz,
                'total_variantes' => $totalVariantes,
                'resumo_cores' => array_slice($resumoCores, 0, 5),
            ];
        }

        return [
            'success' => true,
            'total' => count($itens),
            'produtos' => $itens,
        ];
    }
}

