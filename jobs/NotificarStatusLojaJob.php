<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\RetryableJobInterface;
use app\models\Usuario;
use app\modules\evolution\services\EvolutionService;
use app\modules\evolution\models\WhatsappConfig;

/**
 * Job assíncrono para notificar o lojista sobre alterações no status de sua loja
 * ('aprovado', 'suspensa', 'rejeitada').
 *
 * Executa em background pelo worker da fila sem travar a requisição HTTP do Admin.
 * Notifica via WhatsApp e envia e-mail de confirmação.
 */
class NotificarStatusLojaJob extends BaseObject implements RetryableJobInterface
{
    /** @var string UUID da loja (prest_usuarios.id) */
    public $lojaId;

    /** @var string Tipo de alteração: 'aprovado'|'suspensa'|'rejeitada' */
    public $tipo;

    public function getTtr()
    {
        return 300; // 5 minutos
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < 3;
    }

    public function execute($queue)
    {
        Yii::info("Iniciando NotificarStatusLojaJob para loja ID: {$this->lojaId} com status: {$this->tipo}", __METHOD__);

        $loja = Usuario::findOne($this->lojaId);
        if (!$loja) {
            Yii::warning("NotificarStatusLojaJob: Loja {$this->lojaId} não encontrada.", __METHOD__);
            return;
        }

        $baseUrl = Yii::$app->params['domain'] ?? 'https://catalogos.oncode.app.br';

        // 1. Envio de WhatsApp
        $this->enviarWhatsApp($loja, $baseUrl);

        // 2. Envio de E-mail de confirmação
        $this->enviarEmail($loja, $baseUrl);
    }

    private function enviarWhatsApp(Usuario $loja, string $baseUrl): void
    {
        if (empty($loja->telefone)) return;

        try {
            $mensagens = [
                'aprovado' => "🎉 *Parabéns, {$loja->nome}!*\n\n"
                    . "Sua loja no sistema PULSE foi *aprovada* e já está ativa!\n\n"
                    . "✅ Você já pode fazer login e começar a cadastrar seus produtos:\n"
                    . $baseUrl . "/auth/login\n\n"
                    . "📱 Seu login: *CPF* (" . $loja->getCpfFormatado() . ") e a senha que você cadastrou.\n\n"
                    . "Qualquer dúvida, estamos à disposição. Boas vendas! 🚀",

                'suspensa' => "⚠️ *Aviso sobre sua loja — PULSE*\n\n"
                    . "Sua loja foi temporariamente *suspensa*.\n"
                    . "Entre em contato com o suporte para regularizar o acesso.",

                'rejeitada' => "❌ *Aviso sobre sua solicitação — PULSE*\n\n"
                    . "Infelizmente sua solicitação de cadastro não pôde ser aprovada no momento.\n"
                    . "Entre em contato com o suporte para mais informações.",
            ];

            $texto = $mensagens[$this->tipo] ?? "Atualização sobre sua loja no sistema PULSE.";

            $admins = Usuario::find()->where(['is_admin' => true])->all();
            foreach ($admins as $admin) {
                $whatsappConfig = WhatsappConfig::findByEmpresa($admin->id);
                if ($whatsappConfig && $whatsappConfig->status === 'CONNECTED') {
                    $service = new EvolutionService();
                    $service->sendMessage($admin->id, $loja->telefone, $texto);
                    break;
                }
            }
        } catch (\Throwable $t) {
            Yii::error("NotificarStatusLojaJob: Erro WhatsApp para loja {$loja->id}: " . $t->getMessage(), __METHOD__);
        }
    }

    private function enviarEmail(Usuario $loja, string $baseUrl): void
    {
        if (empty($loja->email) || $this->tipo !== 'aprovado') return;

        try {
            $adminEmail = Yii::$app->params['adminEmail'] ?? 'only.code.cru@gmail.com';

            $html = "
            <div style='font-family: Arial, sans-serif; background-color: #0f1026; color: #f0f0ff; padding: 30px; border-radius: 12px; max-width: 600px; margin: auto;'>
                <div style='text-align: center; margin-bottom: 24px;'>
                    <h2 style='color: #43E97B; margin: 0;'>🎉 Parabéns, {$loja->nome}!</h2>
                    <p style='color: #7A7998; font-size: 15px;'>Sua loja no PULSE foi <strong>aprovada</strong> com sucesso!</p>
                </div>

                <div style='background-color: #16182e; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 20px; margin-bottom: 24px;'>
                    <p style='margin: 8px 0; font-size: 14px;'>Sua loja já se encontra <strong>ativa</strong> e pronta para uso.</p>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>🔑 Usuário de Acesso:</strong> Seu CPF (" . $loja->getCpfFormatado() . ")</p>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>🔒 Senha:</strong> A mesma cadastrada na criação da conta</p>
                </div>

                <div style='text-align: center;'>
                    <a href='{$baseUrl}/auth/login' style='display: inline-block; background-color: #6C63FF; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: bold; font-size: 15px;'>
                        🚀 Acessar Minha Loja Agora
                    </a>
                </div>
            </div>";

            Yii::$app->mailer->compose()
                ->setFrom([$adminEmail => 'PULSE Sistema'])
                ->setTo($loja->email)
                ->setSubject("🎉 Sua loja no PULSE foi aprovada com sucesso!")
                ->setHtmlBody($html)
                ->send();

            Yii::info("NotificarStatusLojaJob: E-mail de aprovação enviado para {$loja->email}", __METHOD__);
        } catch (\Throwable $t) {
            Yii::error("NotificarStatusLojaJob: Erro envio email para loja {$loja->id}: " . $t->getMessage(), __METHOD__);
        }
    }
}
