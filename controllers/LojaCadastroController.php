<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\SignupForm;

/**
 * LojaCadastroController — Cadastro público de novas lojas no sistema SaaS.
 *
 * Rotas públicas (sem autenticação):
 *   GET  /loja-cadastro       → Formulário multi-etapas de cadastro
 *   POST /loja-cadastro/salvar → Processa o cadastro, cria loja com status "pendente"
 *   GET  /loja-cadastro/sucesso → Página de confirmação
 */
class LojaCadastroController extends Controller
{
    public $layout = false; // Layout próprio para a landing pública

    /**
     * Libera acesso público para as actions deste controller.
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => ['index', 'salvar', 'sucesso'],
                        'roles'   => ['?', '@'], // público: logado ou não
                    ],
                ],
            ],
        ];
    }

    /**
     * Exibe o formulário público de cadastro de nova loja.
     */
    public function actionIndex()
    {
        // Se já está logado, redireciona para o sistema
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/vendas/inicio']);
        }

        $model = new SignupForm();
        return $this->render('index', ['model' => $model]);
    }

    /**
     * Processa o cadastro da nova loja.
     * Cria usuário com status "pendente" e notifica o admin via WhatsApp.
     */
    public function actionSalvar()
    {
        if (!Yii::$app->request->isPost) {
            return $this->redirect(['index']);
        }

        $model = new SignupForm();

        if ($model->load(Yii::$app->request->post())) {
            if ($usuario = $model->signupPendente()) {
                // Notifica o administrador via WhatsApp
                $this->notificarAdminWhatsApp($usuario);

                return $this->redirect(['sucesso', 'nome' => $usuario->nome]);
            }
        }

        return $this->render('index', ['model' => $model]);
    }

    /**
     * Página de sucesso após o cadastro.
     */
    public function actionSucesso()
    {
        $nome = Yii::$app->request->get('nome', 'Lojista');
        return $this->render('sucesso', ['nome' => $nome]);
    }

    /**
     * Envia notificação WhatsApp para o administrador do sistema
     * informando sobre o novo cadastro pendente de aprovação.
     */
    private function notificarAdminWhatsApp(\app\models\Usuario $novoUsuario): void
    {
        try {
            // Busca todos os admins do sistema para notificar
            $admins = \app\models\Usuario::find()
                ->where(['is_admin' => true])
                ->andWhere(['not', ['telefone' => null]])
                ->all();

            if (empty($admins)) {
                Yii::warning('LojaCadastroController: Nenhum admin com telefone encontrado para notificação.', __METHOD__);
                return;
            }

            $dataHora = date('d/m/Y \à\s H:i');
            $mensagem = "🏪 *Nova Loja Aguardando Aprovação!*\n\n"
                . "📋 *Dados do Solicitante:*\n"
                . "• Nome: *{$novoUsuario->nome}*\n"
                . "• E-mail: {$novoUsuario->email}\n"
                . "• Telefone: {$novoUsuario->telefone}\n"
                . "• Nome da Loja: *{$novoUsuario->nome_loja}*\n"
                . "• Data: {$dataHora}\n\n"
                . "⚙️ Acesse o painel admin para aprovar ou rejeitar:\n"
                . Yii::$app->request->hostInfo . "/admin/loja";

            // Notifica cada admin usando a instância WhatsApp do primeiro admin conectado
            foreach ($admins as $admin) {
                $whatsappConfig = \app\modules\evolution\models\WhatsappConfig::findByEmpresa($admin->id);

                if ($whatsappConfig && $whatsappConfig->status === 'CONNECTED') {
                    $service = new \app\modules\evolution\services\EvolutionService();
                    $service->sendMessage($admin->id, $admin->telefone, $mensagem);
                    break; // Envia apenas pelo primeiro admin conectado
                }
            }
        } catch (\Exception $e) {
            Yii::error('Erro ao enviar notificação WhatsApp para admin: ' . $e->getMessage(), __METHOD__);
        }
    }
}
