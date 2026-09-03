<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\modules\vendas\models\Encarte;
use app\modules\vendas\services\EncartePdfService;
use kartik\mpdf\Pdf;

class EncartePublicoController extends Controller
{
    /**
     * Define o layout nulo/vazio para permitir experiência 100% personalizada e imersiva
     */
    public $layout = false;

    /**
     * Desabilita controle de acesso (permissão pública)
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * Exibe a página pública interativa do Encarte Digital Flipbook
     */
    public function actionVer($token)
    {
        $encarte = Encarte::find()
            ->where(['token_publico' => $token])
            ->with(['usuario', 'encarteProdutos.produto.fotos', 'encarteProdutos.produto.categoria'])
            ->one();

        if (!$encarte) {
            throw new NotFoundHttpException('O encarte solicitado não foi encontrado.');
        }

        $loja = $encarte->usuario;
        $lojaConfig = null;
        if ($loja && $loja->id) {
            $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $loja->id]);
        }

        // Se o encarte estiver inativo ou expirado, busca o mais recente e exibe tela de aviso com redirecionamento
        if ($encarte->status !== 'ativo') {
            $novoEncarte = Encarte::find()
                ->where(['usuario_id' => $encarte->usuario_id, 'status' => 'ativo'])
                ->andWhere(['!=', 'id', $encarte->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            return $this->render('expirado', [
                'encarteAntigo' => $encarte,
                'novoEncarte' => $novoEncarte,
                'loja' => $loja,
                'lojaConfig' => $lojaConfig,
            ]);
        }

        // Incrementa visualizações
        $encarte->updateCounters(['visualizacoes_count' => 1]);

        return $this->render('ver', [
            'encarte' => $encarte,
            'loja' => $loja,
            'lojaConfig' => $lojaConfig,
            'encarteProdutos' => $encarte->encarteProdutos,
        ]);
    }

    /**
     * Download do PDF público
     */
    public function actionPdf($token)
    {
        $encarte = Encarte::find()
            ->where(['token_publico' => $token])
            ->with(['usuario', 'encarteProdutos.produto.fotos', 'encarteProdutos.produto.categoria'])
            ->one();

        if (!$encarte) {
            throw new NotFoundHttpException('O encarte solicitado não foi encontrado.');
        }

        if ($encarte->status !== 'ativo') {
            $novoEncarte = Encarte::find()
                ->where(['usuario_id' => $encarte->usuario_id, 'status' => 'ativo'])
                ->andWhere(['!=', 'id', $encarte->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if ($novoEncarte) {
                return $this->redirect($novoEncarte->getUrlPdf());
            }
            throw new NotFoundHttpException('Este encarte foi encerrado e não está mais disponível.');
        }

        return EncartePdfService::gerarPdf($encarte, Pdf::DEST_BROWSER);
    }

    /**
     * Desabilita CSRF para a ação de envio de pedido público
     */
    public function beforeAction($action)
    {
        if ($action->id === 'enviar-pedido') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Registra o pedido enviado pelo Encarte Digital no Canal de Comunicação Interno (ClienteInbox)
     */
    public function actionEnviarPedido($token)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $encarte = Encarte::find()
            ->where(['token_publico' => $token])
            ->with(['usuario'])
            ->one();

        if (!$encarte) {
            return ['success' => false, 'message' => 'Encarte não encontrado.'];
        }

        if ($encarte->status !== 'ativo') {
            return [
                'success' => false,
                'message' => 'Este encarte expirou e não aceita mais novos pedidos para evitar compras fora da promoção.',
                'expirado' => true,
            ];
        }

        $request = Yii::$app->request;
        $rawBody = $request->getRawBody();
        $payload = json_decode($rawBody, true) ?: $request->post();

        if (empty($payload['itens'])) {
            return ['success' => false, 'message' => 'A sacola está vazia.'];
        }

        $usuarioId = $encarte->usuario_id;
        $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $usuarioId]);
        $lojaNome = ($lojaConfig && !empty($lojaConfig->nome_fantasia)) 
            ? $lojaConfig->nome_fantasia 
            : (($lojaConfig && !empty($lojaConfig->nome_loja)) 
                ? $lojaConfig->nome_loja 
                : ($encarte->usuario ? $encarte->usuario->nome_loja : 'Loja'));
        $nomeCliente = trim($payload['nome_cliente'] ?? '');
        $telefoneCliente = trim($payload['telefone_cliente'] ?? '');
        $enderecoCliente = trim($payload['endereco_cliente'] ?? '');
        $itens = $payload['itens'] ?? [];
        $total = (float) ($payload['total'] ?? 0);

        $identificacao = $nomeCliente ?: ($telefoneCliente ?: 'Cliente');
        $titulo = "🛒 Pedido Encarte: {$identificacao}" . ($telefoneCliente && $nomeCliente ? " ({$telefoneCliente})" : "");

        $textoLinhas = [];
        $textoLinhas[] = "📋 **Novo Pedido Recebido via Encarte Digital**";
        $textoLinhas[] = "📖 **Encarte:** {$encarte->titulo}";
        if ($nomeCliente) {
            $textoLinhas[] = "👤 **Cliente:** {$nomeCliente}";
        }
        if ($telefoneCliente) {
            $textoLinhas[] = "📱 **WhatsApp:** {$telefoneCliente}";
        }
        if ($enderecoCliente) {
            $textoLinhas[] = "📍 **Endereço/Obs:** {$enderecoCliente}";
        }
        $textoLinhas[] = "\n📦 **Itens do Pedido:**";

        foreach ($itens as $it) {
            $qtd = (int) ($it['qtd'] ?? 1);
            $nome = $it['nome'] ?? 'Produto';
            $preco = (float) ($it['precoVal'] ?? ($it['preco'] ?? 0));
            $sub = $preco * $qtd;
            $subFmt = number_format($sub, 2, ',', '.');
            $textoLinhas[] = "• {$qtd}x {$nome} — R$ {$subFmt}";
        }

        $totalFmt = number_format($total, 2, ',', '.');
        $textoLinhas[] = "\n💰 **Total do Pedido:** R$ {$totalFmt}";
        $textoConteudo = implode("\n", $textoLinhas);

        try {
            $clienteObj = null;
            if (!empty($telefoneCliente)) {
                $clienteObj = \app\modules\vendas\models\Clientes::findOrCreateQuick($usuarioId, $nomeCliente ?: 'Cliente Encarte', $telefoneCliente);
            }

            $inbox = \app\modules\vendas\models\ClienteInbox::postar(
                $usuarioId,
                $clienteObj ? $clienteObj->id : null,
                \app\modules\vendas\models\ClienteInbox::TIPO_CARD,
                $titulo,
                $textoConteudo,
                null,
                [
                    'origem'      => 'encarte_digital',
                    'remetente'   => $identificacao,
                    'autor'       => $identificacao,
                    'encarte_id'  => $encarte->id,
                    'total'       => $total,
                    'cliente'     => $nomeCliente,
                    'telefone'    => $telefoneCliente,
                    'endereco'    => $enderecoCliente,
                    'itens_count' => count($itens),
                ]
            );

            return [
                'success'  => true,
                'message'  => 'Pedido registrado com sucesso no Canal Interno Pulse!',
                'inbox_id' => $inbox ? $inbox->id : null,
            ];
        } catch (\Throwable $e) {
            Yii::error("Erro ao registrar pedido do encarte no Canal Interno: " . $e->getMessage() . "\n" . $e->getTraceAsString(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Erro interno ao processar pedido: ' . $e->getMessage(),
            ];
        }
    }
}

