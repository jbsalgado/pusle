<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\RetryableJobInterface;
use app\models\Usuario;
use app\modules\evolution\services\EvolutionService;
use app\modules\evolution\models\WhatsappConfig;

/**
 * Job assíncrono para notificar os administradores do sistema
 * quando uma nova loja é cadastrada no SaaS (status pendente).
 *
 * Dispara múltiplos canais em background:
 * 1. E-mail detalhado para o admin (Gmail SMTP configurado);
 * 2. WhatsApp para todos os admins com telefone cadastrado;
 * 3. Telegram Bot (se configurado em params).
 */
class NotificarAdminNovaLojaJob extends BaseObject implements RetryableJobInterface
{
    /** @var string UUID do novo usuário/loja (prest_usuarios.id) */
    public $lojaId;

    public function getTtr()
    {
        return 300; // 5 minutos
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < 3;
    }

    /**
     * Executa as notificações em segundo plano via worker queue/listen.
     */
    public function execute($queue)
    {
        Yii::info("Iniciando NotificarAdminNovaLojaJob para loja ID: {$this->lojaId}", __METHOD__);

        $loja = Usuario::findOne($this->lojaId);
        if (!$loja) {
            Yii::warning("NotificarAdminNovaLojaJob: Loja {$this->lojaId} não encontrada.", __METHOD__);
            return;
        }

        $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $loja->id]);
        $nomeLoja = $lojaConfig->nome_loja ?? $loja->nome;
        $baseUrl = Yii::$app->params['domain'] ?? 'https://catalogos.oncode.app.br';
        $linkAprovacao = rtrim($baseUrl, '/') . '/admin/loja/index?status=pendente';

        // 1. NOTIFICAÇÃO POR E-MAIL AO ADMIN
        $this->enviarEmailAdmin($loja, $nomeLoja, $linkAprovacao);

        // 2. NOTIFICAÇÃO POR WHATSAPP AOS ADMINS
        $this->enviarWhatsAppAdmins($loja, $nomeLoja, $linkAprovacao);

        // 3. NOTIFICAÇÃO TELEGRAM (se configurado)
        $this->enviarTelegramAdmin($loja, $nomeLoja, $linkAprovacao);
    }

    /**
     * Envia e-mail de alerta para o administrador principal
     */
    private function enviarEmailAdmin(Usuario $loja, string $nomeLoja, string $linkAprovacao): void
    {
        try {
            $adminEmail = Yii::$app->params['adminEmail'] ?? 'only.code.cru@gmail.com';
            if (empty($adminEmail)) return;

            $dataHora = date('d/m/Y \à\s H:i');
            $cpfFmt = $loja->getCpfFormatado();
            $cidadeEstado = ($loja->cidade ?? '') . ($loja->estado ? '/' . $loja->estado : '');

            $html = "
            <div style='font-family: Arial, sans-serif; background-color: #0f1026; color: #f0f0ff; padding: 30px; border-radius: 12px; max-width: 600px; margin: auto;'>
                <div style='text-align: center; margin-bottom: 24px;'>
                    <h2 style='color: #6C63FF; margin: 0;'>PULSE SaaS — Nova Loja Cadastrada</h2>
                    <p style='color: #7A7998; font-size: 14px;'>Uma nova loja acabou de solicitar cadastro e aguarda aprovação.</p>
                </div>

                <div style='background-color: #16182e; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 20px; margin-bottom: 24px;'>
                    <h3 style='color: #43E97B; margin-top: 0; font-size: 16px;'>📋 Dados da Loja e Responsável:</h3>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>🏪 Nome da Loja:</strong> {$nomeLoja}</p>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>👤 Responsável:</strong> {$loja->nome}</p>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>📄 CPF:</strong> {$cpfFmt}</p>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>📱 WhatsApp:</strong> {$loja->telefone}</p>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>✉️ E-mail:</strong> {$loja->email}</p>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>📍 Localização:</strong> " . ($cidadeEstado ?: 'Não informada') . "</p>
                    <p style='margin: 8px 0; font-size: 14px;'><strong>🕒 Data do Cadastro:</strong> {$dataHora}</p>
                </div>

                <div style='text-align: center;'>
                    <a href='{$linkAprovacao}' style='display: inline-block; background-color: #43E97B; color: #0a0b1e; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: bold; font-size: 15px;'>
                        ✅ Acessar Painel e Aprovar Loja
                    </a>
                </div>
            </div>";

            Yii::$app->mailer->compose()
                ->setFrom([$adminEmail => 'PULSE Sistema'])
                ->setTo($adminEmail)
                ->setSubject("🏪 [PULSE] Nova Loja Cadastrada: {$nomeLoja} - Aguardando Aprovação")
                ->setHtmlBody($html)
                ->send();

            Yii::info("NotificarAdminNovaLojaJob: E-mail enviado com sucesso para {$adminEmail}", __METHOD__);
        } catch (\Throwable $t) {
            Yii::error("NotificarAdminNovaLojaJob: Erro ao enviar e-mail admin: " . $t->getMessage(), __METHOD__);
        }
    }

    /**
     * Envia mensagem WhatsApp aos administradores
     */
    private function enviarWhatsAppAdmins(Usuario $loja, string $nomeLoja, string $linkAprovacao): void
    {
        try {
            $admins = Usuario::find()
                ->where(['is_admin' => true])
                ->andWhere(['not', ['telefone' => null]])
                ->all();

            if (empty($admins)) return;

            $dataHora = date('d/m/Y \à\s H:i');
            $cpfFmt = $loja->getCpfFormatado();

            $texto = "🏪 *Nova Loja Aguardando Aprovação!*\n\n"
                . "📋 *Dados do Solicitante:*\n"
                . "• Loja: *{$nomeLoja}*\n"
                . "• Responsável: *{$loja->nome}*\n"
                . "• CPF: {$cpfFmt}\n"
                . "• Telefone: {$loja->telefone}\n"
                . "• E-mail: {$loja->email}\n"
                . "• Data: {$dataHora}\n\n"
                . "⚙️ Acesse o painel para aprovar:\n"
                . $linkAprovacao;

            foreach ($admins as $admin) {
                $whatsappConfig = WhatsappConfig::findByEmpresa($admin->id);
                if ($whatsappConfig && $whatsappConfig->status === 'CONNECTED') {
                    $service = new EvolutionService();
                    $service->sendMessage($admin->id, $admin->telefone, $texto);
                    break;
                }
            }
        } catch (\Throwable $t) {
            Yii::error("NotificarAdminNovaLojaJob: Erro ao enviar WhatsApp admins: " . $t->getMessage(), __METHOD__);
        }
    }

    /**
     * Envia alerta no Telegram se configurado em params
     */
    private function enviarTelegramAdmin(Usuario $loja, string $nomeLoja, string $linkAprovacao): void
    {
        try {
            $botToken = Yii::$app->params['telegram_bot_token'] ?? '';
            $chatId = Yii::$app->params['telegram_chat_id'] ?? '';

            if (empty($botToken) || empty($chatId)) return;

            $msg = "🏪 *Nova Loja no PULSE!*\n"
                . "Loja: *{$nomeLoja}*\n"
                . "Dono: {$loja->nome}\n"
                . "Tel: {$loja->telefone}\n"
                . "Link: {$linkAprovacao}";

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'chat_id' => $chatId,
                'text' => $msg,
                'parse_mode' => 'Markdown',
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $t) {
            Yii::error("NotificarAdminNovaLojaJob: Erro telegram: " . $t->getMessage(), __METHOD__);
        }
    }
}
