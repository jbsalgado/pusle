<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\Usuario;
use app\modules\evolution\services\EvolutionService;

/**
 * Admin\LojaController — Gerenciamento de Lojas no Painel SaaS.
 *
 * Ações disponíveis:
 *   index     → Lista todas as lojas com filtros de status
 *   aprovar   → Ativa uma loja pendente
 *   suspender → Suspende uma loja ativa
 *   rejeitar  → Rejeita um cadastro pendente
 *   view      → Detalhes de uma loja específica
 */
class LojaController extends Controller
{
    public $layout = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'roles'   => ['@'],
                        'matchCallback' => function () {
                            return \app\components\TenantHelper::isAdmin();
                        },
                    ],
                ],
                'denyCallback' => function () {
                    if (Yii::$app->user->isGuest) {
                        return Yii::$app->response->redirect(['/auth/login']);
                    }
                    throw new ForbiddenHttpException('Acesso restrito ao administrador.');
                },
            ],
        ];
    }

    /**
     * Lista todas as lojas com filtros por status.
     */
    public function actionIndex()
    {
        $status = Yii::$app->request->get('status', 'todos');

        $query = Usuario::find()
            ->where(['eh_dono_loja' => true])
            ->orderBy(['data_criacao' => SORT_DESC]);

        if ($status !== 'todos') {
            $query->andWhere(['status_loja' => $status]);
        }

        // Contadores para o dashboard
        $contadores = [
            'pendente'  => Usuario::find()->where(['status_loja' => 'pendente', 'eh_dono_loja' => true])->count(),
            'ativa'     => Usuario::find()->where(['status_loja' => 'ativa', 'eh_dono_loja' => true])->count(),
            'suspensa'  => Usuario::find()->where(['status_loja' => 'suspensa', 'eh_dono_loja' => true])->count(),
            'rejeitada' => Usuario::find()->where(['status_loja' => 'rejeitada', 'eh_dono_loja' => true])->count(),
        ];

        $lojas = $query->all();

        return $this->render('index', [
            'lojas'      => $lojas,
            'status'     => $status,
            'contadores' => $contadores,
        ]);
    }

    /**
     * Visualiza detalhes de uma loja específica.
     */
    public function actionView($id)
    {
        $loja = $this->findLoja($id);
        return $this->render('view', ['loja' => $loja]);
    }

    /**
     * Aprova uma loja pendente, ativando-a e notificando o lojista via WhatsApp.
     */
    public function actionAprovar($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $loja = $this->findLoja($id);

        if ($loja->status_loja === 'ativa') {
            return ['success' => false, 'message' => 'Esta loja já está ativa.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $loja->status_loja  = 'ativa';
            $loja->blocked_at   = null;
            $loja->confirmed_at = date('Y-m-d H:i:s');

            if (!$loja->save(false, ['status_loja', 'blocked_at', 'confirmed_at'])) {
                throw new \Exception('Erro ao salvar alterações da loja.');
            }

            // Cria assinatura inicial (plano básico / sem plano)
            $this->criarAssinaturaBasica($loja);

            $transaction->commit();

            // Notifica o lojista via WhatsApp
            $this->notificarLojista($loja, 'aprovado');

            return ['success' => true, 'message' => "Loja \"{$loja->nome}\" ativada com sucesso!"];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Erro ao aprovar loja: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Suspende uma loja ativa.
     */
    public function actionSuspender($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $loja = $this->findLoja($id);

        $loja->status_loja = 'suspensa';
        $loja->blocked_at  = date('Y-m-d H:i:s');

        if ($loja->save(false, ['status_loja', 'blocked_at'])) {
            $this->notificarLojista($loja, 'suspensa');
            return ['success' => true, 'message' => "Loja \"{$loja->nome}\" suspensa."];
        }

        return ['success' => false, 'message' => 'Erro ao suspender loja.'];
    }

    /**
     * Rejeita um cadastro pendente.
     */
    public function actionRejeitar($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $loja = $this->findLoja($id);

        $loja->status_loja = 'rejeitada';
        $loja->blocked_at  = date('Y-m-d H:i:s');

        if ($loja->save(false, ['status_loja', 'blocked_at'])) {
            $this->notificarLojista($loja, 'rejeitada');
            return ['success' => true, 'message' => "Cadastro de \"{$loja->nome}\" rejeitado."];
        }

        return ['success' => false, 'message' => 'Erro ao rejeitar cadastro.'];
    }

    /**
     * Reativa uma loja suspensa ou rejeitada.
     */
    public function actionReativar($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $loja = $this->findLoja($id);

        $loja->status_loja  = 'ativa';
        $loja->blocked_at   = null;
        $loja->confirmed_at = $loja->confirmed_at ?? date('Y-m-d H:i:s');

        if ($loja->save(false, ['status_loja', 'blocked_at', 'confirmed_at'])) {
            $this->notificarLojista($loja, 'aprovado');
            return ['success' => true, 'message' => "Loja \"{$loja->nome}\" reativada."];
        }

        return ['success' => false, 'message' => 'Erro ao reativar loja.'];
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** Encontra o model de loja pelo ID ou lança 404. */
    private function findLoja(string $id): Usuario
    {
        $loja = Usuario::findOne(['id' => $id, 'eh_dono_loja' => true]);
        if (!$loja) {
            throw new NotFoundHttpException('Loja não encontrada.');
        }
        return $loja;
    }

    /** Cria assinatura básica ao aprovar a loja. */
    private function criarAssinaturaBasica(Usuario $loja): void
    {
        try {
            // Verifica se já existe assinatura ativa
            $existente = \app\models\Assinaturas::findOne(['usuario_id' => $loja->id, 'status' => 'ativa']);
            if ($existente) return;

            // Busca plano mais básico disponível
            $plano = \app\models\Plano::find()->where(['ativo' => true])->orderBy(['valor' => SORT_ASC])->one();
            if (!$plano) return;

            $assinatura = new \app\models\Assinaturas();
            $assinatura->id          = Yii::$app->db->createCommand('SELECT gen_random_uuid()')->queryScalar();
            $assinatura->usuario_id  = $loja->id;
            $assinatura->plano_id    = $plano->id;
            $assinatura->status      = 'ativa';
            $assinatura->data_inicio = date('Y-m-d');
            $assinatura->save(false);
        } catch (\Exception $e) {
            Yii::warning('Admin\LojaController: Erro ao criar assinatura: ' . $e->getMessage(), __METHOD__);
        }
    }

    /** Envia notificação WhatsApp para o lojista sobre mudança de status. */
    private function notificarLojista(Usuario $loja, string $tipo): void
    {
        if (empty($loja->telefone)) return;

        try {
            $mensagens = [
                'aprovado' => "🎉 *Parabéns, {$loja->nome}!*\n\n"
                    . "Sua loja no sistema PULSE foi *aprovada* e já está ativa!\n\n"
                    . "✅ Você já pode fazer login e começar a usar:\n"
                    . Yii::$app->request->hostInfo . "\n\n"
                    . "📱 Seu acesso: *CPF* e a senha que você cadastrou.\n\n"
                    . "Qualquer dúvida, entre em contato. Boas vendas! 🚀",

                'suspensa' => "⚠️ *Aviso sobre sua loja — PULSE*\n\n"
                    . "Sua loja foi temporariamente *suspensa*.\n"
                    . "Entre em contato com o suporte para regularizar.",

                'rejeitada' => "❌ *Aviso sobre sua solicitação — PULSE*\n\n"
                    . "Infelizmente sua solicitação de cadastro não pôde ser aprovada.\n"
                    . "Entre em contato com o suporte para mais informações.",
            ];

            $texto = $mensagens[$tipo] ?? "Atualização sobre sua loja no sistema PULSE.";

            // Usa a instância WhatsApp do admin para enviar ao lojista
            $admins = Usuario::find()->where(['is_admin' => true])->all();
            foreach ($admins as $admin) {
                $whatsappConfig = \app\modules\evolution\models\WhatsappConfig::findByEmpresa($admin->id);
                if ($whatsappConfig && $whatsappConfig->status === 'CONNECTED') {
                    $service = new EvolutionService();
                    $service->sendMessage($admin->id, $loja->telefone, $texto);
                    break;
                }
            }
        } catch (\Exception $e) {
            Yii::error('Admin: Erro ao notificar lojista: ' . $e->getMessage(), __METHOD__);
        }
    }
}
