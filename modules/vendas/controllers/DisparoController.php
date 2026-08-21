<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\DisparoMassa;
use app\modules\vendas\models\DisparoItem;
use app\modules\vendas\services\DisparoMassaService;
use app\modules\evolution\services\EvolutionService;
use app\modules\evolution\models\WhatsappConfig;

/**
 * Controller Web para gerenciamento e monitoramento de disparos em massa.
 */
class DisparoController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Retorna o ID da loja (dono) para usar nas queries
     */
    protected function getLojaId()
    {
        $usuario = Yii::$app->user->identity;

        if (!$usuario) {
            return null;
        }

        if (isset($usuario->eh_dono_loja) && ($usuario->eh_dono_loja === true || $usuario->eh_dono_loja === 't' || $usuario->eh_dono_loja === 1)) {
            return $usuario->id;
        }

        $colaborador = Colaborador::getColaboradorLogado();

        if ($colaborador) {
            return $colaborador->usuario_id;
        }

        return $usuario->id;
    }

    /**
     * Retorna o status de conexão da Evolution API / WhatsApp para a loja logada.
     */
    public function actionStatusWhatsapp()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $lojaId = $this->getLojaId();
        if (!$lojaId || strlen((string)$lojaId) !== 36) {
            return [
                'success' => true,
                'connected' => false,
                'instance_name' => null,
                'status' => 'DISCONNECTED',
                'message' => 'Instância não configurada.'
            ];
        }

        try {
            $service = new EvolutionService();
            $connected = $service->checkStatus($lojaId);
            $config = WhatsappConfig::findByEmpresa($lojaId);

            return [
                'success' => true,
                'connected' => $connected,
                'instance_name' => $config ? $config->instance_name : null,
                'status' => $config ? $config->status : 'DISCONNECTED',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'connected' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Retorna a lista de clientes para seleção no modal de disparo em massa.
     */
    public function actionClientes()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $lojaId = $this->getLojaId();
        
        $query = Cliente::find();
        if ($lojaId && strlen((string)$lojaId) === 36) {
            $query->andWhere(['usuario_id' => $lojaId]);
        }

        $clientesRaw = $query->select(['id', 'nome_completo', 'telefone', 'email'])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->asArray()
            ->all();

        $clientes = array_map(function($c) {
            $c['nome'] = $c['nome_completo'];
            $c['celular'] = $c['telefone'];
            $c['tem_whatsapp'] = !empty($c['telefone']);
            $c['tem_email'] = !empty($c['email']) && filter_var($c['email'], FILTER_VALIDATE_EMAIL);
            return $c;
        }, $clientesRaw);

        return [
            'success' => true,
            'clientes' => $clientes
        ];
    }

    /**
     * Ação AJAX para criar uma nova campanha de disparo em massa e iniciar o processamento.
     */
    public function actionCriar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Sessão expirada. Faça login novamente.'];
        }

        $request = Yii::$app->request;
        $lojaId = $this->getLojaId();

        // Se for requisição JSON, ler do body
        $rawBody = json_decode($request->getRawBody(), true) ?: [];

        $produtosIds = $rawBody['produtos_ids'] ?? $request->post('produtos_ids', []);
        $canais = $rawBody['canais'] ?? $request->post('canais', []);
        $clientesIds = $rawBody['clientes_ids'] ?? $request->post('clientes_ids', []);
        $telefonesManuais = $rawBody['telefones_manuais'] ?? $request->post('telefones_manuais', '');
        $emailsManuais = $rawBody['emails_manuais'] ?? $request->post('emails_manuais', '');
        $mensagemTexto = $rawBody['mensagem_texto'] ?? $request->post('mensagem_texto');
        
        $visualOptions = [
            'template' => $rawBody['template'] ?? $request->post('template', 'modern_dark'),
            'corTema' => $rawBody['cor_tema'] ?? $request->post('cor_tema', 'dark'),
            'fundoEstilo' => $rawBody['fundo_estilo'] ?? $request->post('fundo_estilo', 'gradient'),
        ];

        try {
            $service = new DisparoMassaService();
            $campanha = $service->criarCampanhaDisparo(
                $lojaId,
                $produtosIds,
                $canais,
                $clientesIds,
                $visualOptions,
                $mensagemTexto,
                $telefonesManuais,
                $emailsManuais
            );

            // Disparar worker assíncrono para processar o lote inicial imediato
            $service->processarFilaDisparo($campanha->id, 50);

            return [
                'success' => true,
                'message' => 'Campanha criada e disparo iniciado com sucesso!',
                'disparo_id' => $campanha->id,
                'total_itens' => $campanha->total_itens,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar disparo em massa: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retorna o status e progresso em tempo real de uma campanha de disparo.
     */
    public function actionStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $campanha = DisparoMassa::findOne($id);
        if (!$campanha) {
            return ['success' => false, 'message' => 'Campanha não encontrada.'];
        }

        // Tentar processar mais uma rodada da fila se ainda houver pendentes
        if ($campanha->status === DisparoMassa::STATUS_PENDENTE || $campanha->status === DisparoMassa::STATUS_PROCESSANDO) {
            $service = new DisparoMassaService();
            $service->processarFilaDisparo($campanha->id, 10);
            $campanha->refresh();
        }

        // Buscar histórico de erros caso existam itens com falha
        $errosItens = [];
        if ($campanha->itens_erro > 0) {
            $errosItens = DisparoItem::find()
                ->select(['canal', 'destino', 'erro_mensagem'])
                ->where(['disparo_id' => $campanha->id, 'status' => DisparoItem::STATUS_ERRO])
                ->asArray()
                ->all();
        }

        return [
            'success' => true,
            'disparo_id' => $campanha->id,
            'status' => $campanha->status,
            'total_itens' => (int)$campanha->total_itens,
            'itens_enviados' => (int)$campanha->itens_enviados,
            'itens_erro' => (int)$campanha->itens_erro,
            'progresso_percentual' => $campanha->getProgressoPercentual(),
            'erros' => $errosItens
        ];
    }

    /**
     * Re-executa os envios dos itens que falharam em uma campanha.
     */
    public function actionReenviarErros($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $campanha = DisparoMassa::findOne($id);
        if (!$campanha) {
            return ['success' => false, 'message' => 'Campanha não encontrada.'];
        }

        try {
            $service = new DisparoMassaService();
            $reprocessados = $service->retentarItensComErro($campanha->id);

            return [
                'success' => true,
                'message' => "Re-processamento iniciado para {$reprocessados} item(ns) com falha.",
                'disparo_id' => $campanha->id,
                'reprocessados' => $reprocessados,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao reprocessar disparos: ' . $e->getMessage()
            ];
        }
    }
}
