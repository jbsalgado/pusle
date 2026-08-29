<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\vendas\models\Produto;
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
     * Ação AJAX para criar/gerar um Encarte Digital a partir dos produtos selecionados.
     */
    public function actionGerar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        $request = Yii::$app->request;
        $produtosIds = $request->post('produtos_ids', []);
        $titulo = $request->post('titulo', 'Encarte de Ofertas Imbatíveis');
        $subtitulo = $request->post('subtitulo', 'Ofertas válidas enquanto durarem os estoques');
        $estiloLayout = $request->post('estilo_layout', 'flipsnack_supermarket');
        $corTema = $request->post('cor_tema', 'red_gold');
        $ppp = (int)$request->post('produtos_por_pagina', 6);

        if (empty($produtosIds) || !is_array($produtosIds)) {
            return ['success' => false, 'message' => 'Nenhum produto foi selecionado para o encarte.'];
        }

        // Valida se os produtos pertencem à loja
        $produtos = Produto::find()
            ->where(['id' => $produtosIds, 'usuario_id' => $lojaId, 'ativo' => true])
            ->all();

        if (empty($produtos)) {
            return ['success' => false, 'message' => 'Nenhum produto ativo válido encontrado.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $encarte = new Encarte();
            $encarte->usuario_id = $lojaId;
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
                $encarteItem = new EncarteProduto();
                $encarteItem->encarte_id = $encarte->id;
                $encarteItem->produto_id = $p->id;
                $encarteItem->ordem = $ordem++;
                if (isset($produtosTags[$p->id])) {
                    $encarteItem->tag_promocional = $produtosTags[$p->id];
                }
                if (!$encarteItem->save()) {
                    throw new \Exception('Erro ao salvar item do encarte.');
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
}
