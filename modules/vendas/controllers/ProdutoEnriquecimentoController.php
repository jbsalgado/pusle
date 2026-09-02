<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\ProdutoVideo;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\services\ProductWebScraperService;

class ProdutoEnriquecimentoController extends Controller
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
                    'buscar-midias-produto' => ['POST', 'GET'],
                    'extrair-url' => ['POST'],
                    'buscar-por-marcas' => ['POST', 'GET'],
                    'aplicar-enriquecimento' => ['POST'],
                    'cadastrar-lote-sugerido' => ['POST'],
                    'listar-produtos-para-enriquecer' => ['GET', 'POST'],
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
     * Lista produtos do lojista para o modal de enriquecimento
     */
    public function actionListarProdutosParaEnriquecer()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $filtro = $request->get('filtro', 'todos'); // todos, sem_foto, sem_ean, por_marca, por_categoria, selecionados
        $marca = trim($request->get('marca', ''));
        $categoriaId = $request->get('categoria_id', '');
        $ids = $request->get('ids', []);
        $busca = trim($request->get('busca', ''));

        $query = Produto::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true])
            ->with(['categoria', 'fotos']);

        if ($busca) {
            $query->andWhere([
                'or',
                ['ilike', 'nome', $busca],
                ['ilike', 'marca', $busca],
                ['ilike', 'codigo_barras', $busca],
            ]);
        }

        if ($filtro === 'sem_foto') {
            $query->andWhere([
                'not in', 'id',
                ProdutoFoto::find()->select('produto_id')->distinct()
            ]);
        } elseif ($filtro === 'sem_ean') {
            $query->andWhere(['or', ['codigo_barras' => null], ['codigo_barras' => '']]);
        }

        if (!empty($marca)) {
            $marcasArray = array_filter(array_map('trim', explode(',', $marca)));
            if (!empty($marcasArray)) {
                $condMarca = ['or'];
                foreach ($marcasArray as $m) {
                    $condMarca[] = ['ilike', 'marca', $m];
                }
                $query->andWhere($condMarca);
            }
        }

        if (!empty($categoriaId) && $categoriaId !== 'TODAS') {
            $query->andWhere(['categoria_id' => $categoriaId]);
        }

        if (!empty($ids) && is_array($ids)) {
            $query->andWhere(['id' => $ids]);
        }

        $limite = (int)$request->get('limit', 100);
        if ($limite <= 0) $limite = 100;
        if ($limite > 500) $limite = 500;

        // Se o filtro for sem_foto, precisamos trazer mais produtos e filtrar por arquivos físicos existentes
        $fetchLimit = ($filtro === 'sem_foto') ? min($limite * 3, 500) : $limite;
        $produtos = $query->orderBy(['nome' => SORT_ASC])->limit($fetchLimit)->all();

        $resultado = [];
        foreach ($produtos as $p) {
            $totalFotosValidas = 0;
            foreach ($p->fotos as $f) {
                $caminhoFisico = Yii::getAlias('@app/web/' . ltrim($f->arquivo_path, '/'));
                if (file_exists($caminhoFisico)) {
                    $totalFotosValidas++;
                }
            }

            // Se filtrou por sem_foto e o produto tem foto válida, pula
            if ($filtro === 'sem_foto' && $totalFotosValidas > 0) {
                continue;
            }

            $fotoPrincipalUrl = null;
            if ($p->fotoPrincipal) {
                if (method_exists($p->fotoPrincipal, 'getUrlCompleta')) {
                    $fotoPrincipalUrl = $p->fotoPrincipal->getUrlCompleta();
                } elseif (method_exists($p->fotoPrincipal, 'getUrl')) {
                    $fotoPrincipalUrl = $p->fotoPrincipal->getUrl();
                }
            }

            $resultado[] = [
                'id' => (string)$p->id,
                'nome' => $p->nome,
                'marca' => $p->marca ?: '',
                'codigo_barras' => $p->codigo_barras ?: '',
                'preco_venda' => (float)$p->preco_venda_sugerido,
                'preco_venda_formatado' => number_format((float)($p->preco_promocional ?: $p->preco_venda_sugerido), 2, ',', '.'),
                'categoria_id' => (string)$p->categoria_id,
                'categoria_nome' => $p->categoria ? $p->categoria->nome : 'Sem Categoria',
                'total_fotos' => $totalFotosValidas,
                'tem_foto' => $totalFotosValidas > 0,
                'foto_principal_url' => $fotoPrincipalUrl,
            ];

            if (count($resultado) >= $limite) {
                break;
            }
        }

        return [
            'success' => true,
            'total' => count($resultado),
            'produtos' => $resultado,
        ];
    }

    /**
     * Busca mídias (fotos e vídeos) para um produto específico na Web / Bases
     */
    public function actionBuscarMidiasProduto()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $produtoId = $request->post('produto_id') ?: $request->get('produto_id');
        $nome = trim($request->post('nome') ?: $request->get('nome', ''));
        $marca = trim($request->post('marca') ?: $request->get('marca', ''));
        $ean = trim($request->post('ean') ?: $request->get('ean', ''));

        if ($produtoId) {
            $produto = Produto::findOne(['id' => $produtoId, 'usuario_id' => $lojaId]);
            if ($produto) {
                if (empty($nome)) $nome = $produto->nome;
                if (empty($marca)) $marca = $produto->marca;
                if (empty($ean)) $ean = $produto->codigo_barras;
            }
        }

        if (empty($nome)) {
            return ['success' => false, 'message' => 'Nome do produto não informado para a busca.'];
        }

        $service = new ProductWebScraperService();
        $resultado = $service->buscarMidiasParaProduto($nome, $marca, $ean, 8, 3);

        return $resultado;
    }

    /**
     * Extrai mídias e dados de uma URL colada pelo usuário
     */
    public function actionExtrairUrl()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $url = trim(Yii::$app->request->post('url', ''));

        if (empty($url)) {
            return ['success' => false, 'message' => 'Informe a URL para extração.'];
        }

        $service = new ProductWebScraperService();
        return $service->extrairDeUrl($url);
    }

    /**
     * Busca catálogo sugerido para marcas separadas por vírgula
     */
    public function actionBuscarPorMarcas()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        
        $marcasStr = $request->post('marcas') ?: $request->get('marcas', '');
        $itensPorMarca = (int)($request->post('itens_por_marca') ?: $request->get('itens_por_marca', 8));

        $marcasArray = array_filter(array_map('trim', explode(',', $marcasStr)));
        if (empty($marcasArray)) {
            return ['success' => false, 'message' => 'Informe ao menos uma marca para pesquisa.'];
        }

        $service = new ProductWebScraperService();
        return $service->pesquisarProdutosPorMarcas($marcasArray, $itensPorMarca > 0 ? $itensPorMarca : 8);
    }

    /**
     * Aplica fotos, vídeos, EAN e nome enriquecidos em um produto existente
     */
    public function actionAplicarEnriquecimento()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $produtoId = $request->post('produto_id');
        $fotos = $request->post('fotos', []);
        $videos = $request->post('videos', []);
        $atualizarNome = (bool)$request->post('atualizar_nome', false);
        $novoNome = trim($request->post('novo_nome', ''));
        $atualizarMarca = (bool)$request->post('atualizar_marca', false);
        $novaMarca = trim($request->post('nova_marca', ''));
        $atualizarEan = (bool)$request->post('atualizar_ean', false);
        $novoEan = trim($request->post('novo_ean', ''));

        $produto = Produto::findOne(['id' => $produtoId, 'usuario_id' => $lojaId]);
        if (!$produto) {
            return ['success' => false, 'message' => 'Produto não encontrado.'];
        }

        $service = new ProductWebScraperService();
        $fotosSalvas = 0;
        $videosSalvos = 0;
        $errosFotos = [];

        // 1. Atualiza dados cadastrais se solicitado
        $camposAtualizados = [];
        if ($atualizarNome && !empty($novoNome)) {
            $produto->nome = $novoNome;
            $camposAtualizados[] = 'nome';
        }
        if ($atualizarMarca && !empty($novaMarca)) {
            $produto->marca = $novaMarca;
            $camposAtualizados[] = 'marca';
        }
        if ($atualizarEan && !empty($novoEan)) {
            $produto->codigo_barras = $novoEan;
            $camposAtualizados[] = 'codigo_barras';
        }

        if (!empty($camposAtualizados)) {
            $produto->save(false, $camposAtualizados);
        }

        // 2. Download e salvamento das fotos selecionadas
        if (!empty($fotos) && is_array($fotos)) {
            $ordemBase = ProdutoFoto::find()->where(['produto_id' => $produto->id])->count();
            foreach ($fotos as $idx => $fotoUrl) {
                try {
                    $ehPrincipal = ($idx === 0 && $ordemBase === 0);
                    $service->baixarESalvarFoto($fotoUrl, $produto->id, $ehPrincipal, $ordemBase + $idx);
                    $fotosSalvas++;
                } catch (\Exception $e) {
                    $errosFotos[] = $e->getMessage();
                }
            }
        }

        // 3. Registro dos vídeos promocionais selecionados
        if (!empty($videos) && is_array($videos)) {
            foreach ($videos as $v) {
                $vUrl = is_array($v) ? ($v['url'] ?? '') : $v;
                $vTitulo = is_array($v) ? ($v['titulo'] ?? '') : 'Vídeo Promocional Web';
                if (!empty($vUrl)) {
                    try {
                        $service->salvarVideoPromocional($vUrl, $produto->id, $lojaId, $vTitulo);
                        $videosSalvos++;
                    } catch (\Exception $e) {
                        Yii::error("Erro ao salvar vídeo web: " . $e->getMessage(), __METHOD__);
                    }
                }
            }
        }

        return [
            'success' => true,
            'message' => "Enriquecimento aplicado com sucesso! ({$fotosSalvas} fotos salvas, {$videosSalvos} vídeos adicionados).",
            'fotos_salvas' => $fotosSalvas,
            'videos_salvos' => $videosSalvos,
            'erros_fotos' => $errosFotos,
        ];
    }

    /**
     * Cadastra em lote produtos sugeridos pela busca de marcas ou extração de link
     */
    public function actionCadastrarLoteSugerido()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $produtosLote = $request->post('produtos', []);
        $categoriaPadraoId = $request->post('categoria_id', null);

        if (empty($produtosLote) || !is_array($produtosLote)) {
            return ['success' => false, 'message' => 'Nenhum produto enviado para cadastro.'];
        }

        // Se categoria não foi informada, pega a primeira categoria ativa da loja ou cria uma Geral
        if (empty($categoriaPadraoId)) {
            $primeiraCat = Categoria::findOne(['usuario_id' => $lojaId, 'ativo' => true]);
            if ($primeiraCat) {
                $categoriaPadraoId = $primeiraCat->id;
            } else {
                $novaCat = new Categoria();
                $novaCat->usuario_id = $lojaId;
                $novaCat->nome = 'Geral';
                $novaCat->ativo = true;
                $novaCat->save(false);
                $categoriaPadraoId = $novaCat->id;
            }
        }

        $service = new ProductWebScraperService();
        $cadastrados = 0;
        $erros = [];

        foreach ($produtosLote as $pData) {
            $nome = trim($pData['nome'] ?? '');
            if (empty($nome)) continue;

            $marca = trim($pData['marca'] ?? '');
            $ean = trim($pData['ean'] ?? '');
            $precoVenda = (float)str_replace(['R$', ' ', '.', ','], ['', '', '', '.'], $pData['preco_venda'] ?? '0.00');
            $fotos = $pData['fotos'] ?? [];
            $videos = $pData['videos'] ?? [];
            $catId = !empty($pData['categoria_id']) ? $pData['categoria_id'] : $categoriaPadraoId;

            $produto = new Produto();
            $produto->usuario_id = $lojaId;
            $produto->categoria_id = $catId;
            $produto->nome = $nome;
            $produto->marca = $marca;
            $produto->codigo_barras = !empty($ean) ? $ean : null;
            $produto->preco_custo = 0.00;
            $produto->preco_venda_sugerido = $precoVenda > 0 ? $precoVenda : 0.00;
            $produto->estoque_atual = 0;
            $produto->unidade_medida = 'UN';

            if ($produto->save()) {
                $cadastrados++;

                // Baixa fotos
                if (!empty($fotos) && is_array($fotos)) {
                    foreach ($fotos as $idx => $fotoUrl) {
                        try {
                            $service->baixarESalvarFoto($fotoUrl, $produto->id, ($idx === 0), $idx);
                        } catch (\Exception $e) {
                            Yii::error("Erro ao baixar foto no cadastro em lote: " . $e->getMessage(), __METHOD__);
                        }
                    }
                }

                // Salva vídeos
                if (!empty($videos) && is_array($videos)) {
                    foreach ($videos as $v) {
                        $vUrl = is_array($v) ? ($v['url'] ?? '') : $v;
                        if (!empty($vUrl)) {
                            try {
                                $service->salvarVideoPromocional($vUrl, $produto->id, $lojaId, $nome);
                            } catch (\Exception $e) {
                                Yii::error("Erro ao salvar vídeo no cadastro em lote: " . $e->getMessage(), __METHOD__);
                            }
                        }
                    }
                }

            } else {
                $erros[] = "Erro ao cadastrar '{$nome}': " . implode(', ', $produto->getFirstErrors());
            }
        }

        return [
            'success' => $cadastrados > 0,
            'message' => "{$cadastrados} produto(s) cadastrado(s) com sucesso na sua base!",
            'total_cadastrados' => $cadastrados,
            'erros' => $erros,
        ];
    }
}
