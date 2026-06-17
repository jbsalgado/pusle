<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\contas_pagar\models\ContaPagar;

/**
 * Console command para enviar notificações de vencimento de contas a pagar
 * 
 * Uso:
 * php yii notificacao-contas/enviar
 * 
 * Configurar no cron para executar diariamente:
 * 0 8 * * * cd /srv/http/pulse && php yii notificacao-contas/enviar >> /var/log/pulse-notificacoes.log 2>&1
 */
class NotificacaoContasController extends Controller
{
    /**
     * Envia notificações de vencimento de contas a pagar
     * 
     * @return int Exit code
     */
    public function actionEnviar()
    {
        $this->stdout("=== Iniciando envio de notificações de contas a pagar ===\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("Data/Hora: " . date('Y-m-d H:i:s') . "\n\n");

        $totalEnviadas = 0;
        $totalErros = 0;

        // 1. Notificações de contas vencendo em 3 dias
        $this->stdout("📅 Verificando contas vencendo em 3 dias...\n", \yii\helpers\Console::FG_YELLOW);
        $resultado3Dias = $this->enviarNotificacoesVencimento(3, 'vencendo');
        $totalEnviadas += $resultado3Dias['enviadas'];
        $totalErros += $resultado3Dias['erros'];

        // 2. Notificações de contas vencendo hoje
        $this->stdout("\n📅 Verificando contas vencendo hoje...\n", \yii\helpers\Console::FG_YELLOW);
        $resultadoHoje = $this->enviarNotificacoesVencimento(0, 'vence_hoje');
        $totalEnviadas += $resultadoHoje['enviadas'];
        $totalErros += $resultadoHoje['erros'];

        // 3. Notificações de contas vencidas há 1 dia
        $this->stdout("\n📅 Verificando contas vencidas há 1 dia...\n", \yii\helpers\Console::FG_RED);
        $resultadoVencidas = $this->enviarNotificacoesVencimento(-1, 'vencida');
        $totalEnviadas += $resultadoVencidas['enviadas'];
        $totalErros += $resultadoVencidas['erros'];

        // Resumo
        $this->stdout("\n" . str_repeat("=", 60) . "\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("✅ Total de notificações enviadas: {$totalEnviadas}\n", \yii\helpers\Console::FG_GREEN);
        if ($totalErros > 0) {
            $this->stdout("❌ Total de erros: {$totalErros}\n", \yii\helpers\Console::FG_RED);
        }
        $this->stdout(str_repeat("=", 60) . "\n\n", \yii\helpers\Console::FG_CYAN);

        return ExitCode::OK;
    }

    /**
     * Envia notificações para contas com vencimento específico
     * 
     * @param int $diasDiferenca Dias de diferença (positivo = futuro, negativo = passado, 0 = hoje)
     * @param string $tipo Tipo de notificação (vencendo, vence_hoje, vencida)
     * @return array ['enviadas' => int, 'erros' => int]
     */
    protected function enviarNotificacoesVencimento($diasDiferenca, $tipo)
    {
        $dataAlvo = date('Y-m-d', strtotime("+{$diasDiferenca} days"));

        // Busca contas pendentes com vencimento na data alvo
        $contas = ContaPagar::find()
            ->where(['status' => ContaPagar::STATUS_PENDENTE])
            ->andWhere(['data_vencimento' => $dataAlvo])
            ->with(['usuario', 'fornecedor'])
            ->all();

        $enviadas = 0;
        $erros = 0;

        $this->stdout("   Encontradas " . count($contas) . " conta(s) para notificar\n");

        foreach ($contas as $conta) {
            try {
                // Verifica se o usuário tem e-mail configurado
                if (empty($conta->usuario->email)) {
                    $this->stdout("   ⚠️  Usuário {$conta->usuario->nome} sem e-mail configurado\n", \yii\helpers\Console::FG_YELLOW);
                    continue;
                }

                // Envia e-mail
                $enviado = $this->enviarEmail($conta, $tipo, $diasDiferenca);

                if ($enviado) {
                    $enviadas++;
                    $this->stdout("   ✓ E-mail enviado para {$conta->usuario->email} - Conta #{$conta->id}\n", \yii\helpers\Console::FG_GREEN);
                } else {
                    $erros++;
                    $this->stdout("   ✗ Erro ao enviar e-mail para {$conta->usuario->email}\n", \yii\helpers\Console::FG_RED);
                }
            } catch (\Exception $e) {
                $erros++;
                $this->stdout("   ✗ Exceção: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
                Yii::error("Erro ao enviar notificação: " . $e->getMessage(), __METHOD__);
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros];
    }

    /**
     * Envia e-mail de notificação
     * 
     * @param ContaPagar $conta
     * @param string $tipo
     * @param int $diasDiferenca
     * @return bool
     */
    protected function enviarEmail($conta, $tipo, $diasDiferenca)
    {
        // Define assunto e mensagem baseado no tipo
        switch ($tipo) {
            case 'vencendo':
                $assunto = "⏰ Conta a Pagar vencendo em {$diasDiferenca} dias";
                $urgencia = 'info';
                $mensagemTipo = "vencerá em <strong>{$diasDiferenca} dias</strong>";
                break;

            case 'vence_hoje':
                $assunto = "🔔 Conta a Pagar vence HOJE";
                $urgencia = 'warning';
                $mensagemTipo = "vence <strong>HOJE</strong>";
                break;

            case 'vencida':
                $diasAtraso = abs($diasDiferenca);
                $assunto = "🚨 Conta a Pagar VENCIDA há {$diasAtraso} dia(s)";
                $urgencia = 'danger';
                $mensagemTipo = "está <strong>VENCIDA</strong> há {$diasAtraso} dia(s)";
                break;

            default:
                $assunto = "Notificação de Conta a Pagar";
                $urgencia = 'info';
                $mensagemTipo = "requer atenção";
        }

        // Formata valores
        $valorFormatado = 'R$ ' . number_format($conta->valor, 2, ',', '.');
        $dataVencimento = date('d/m/Y', strtotime($conta->data_vencimento));
        $fornecedor = $conta->fornecedor->nome_fantasia ?? 'Não informado';

        // Monta corpo do e-mail em HTML
        $corpo = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .conta-info { background: white; padding: 15px; border-left: 4px solid #667eea; margin: 15px 0; }
                .urgencia-{$urgencia} { border-left-color: " . ($urgencia == 'danger' ? '#ef4444' : ($urgencia == 'warning' ? '#f59e0b' : '#3b82f6')) . "; }
                .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; }
                .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin: 0;'>💰 Pulse - Gestão Financeira</h2>
                    <p style='margin: 5px 0 0 0; opacity: 0.9;'>Notificação de Conta a Pagar</p>
                </div>
                
                <div class='content'>
                    <p>Olá, <strong>{$conta->usuario->nome}</strong>!</p>
                    
                    <p>A seguinte conta a pagar {$mensagemTipo}:</p>
                    
                    <div class='conta-info urgencia-{$urgencia}'>
                        <p><strong>📋 Descrição:</strong> {$conta->descricao}</p>
                        <p><strong>💵 Valor:</strong> {$valorFormatado}</p>
                        <p><strong>📅 Vencimento:</strong> {$dataVencimento}</p>
                        <p><strong>🏢 Fornecedor:</strong> {$fornecedor}</p>
                        <p><strong>🏷️ Categoria:</strong> {$conta->categoria}</p>
                    </div>
                    
                    <p>Acesse o sistema para mais detalhes e realizar o pagamento:</p>
                    
                    <a href='" . Yii::$app->params['siteUrl'] . "/contas-pagar/conta-pagar/view?id={$conta->id}' class='btn'>
                        Ver Conta no Sistema
                    </a>
                </div>
                
                <div class='footer'>
                    <p>Este é um e-mail automático. Não responda esta mensagem.</p>
                    <p>&copy; " . date('Y') . " Pulse - Sistema de Gestão</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Envia e-mail usando o componente mailer do Yii2
        try {
            return Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['adminEmail'] ?? 'noreply@pulse.com' => 'Pulse - Sistema de Gestão'])
                ->setTo($conta->usuario->email)
                ->setSubject($assunto)
                ->setHtmlBody($corpo)
                ->send();
        } catch (\Exception $e) {
            Yii::error("Erro ao enviar e-mail: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Action de teste para enviar notificação de uma conta específica
     * 
     * @param string $id ID da conta
     * @return int
     */
    public function actionTestar($id)
    {
        $this->stdout("=== Teste de Notificação ===\n\n", \yii\helpers\Console::FG_CYAN);

        $conta = ContaPagar::findOne($id);

        if (!$conta) {
            $this->stdout("❌ Conta não encontrada: {$id}\n", \yii\helpers\Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $this->stdout("Conta encontrada:\n");
        $this->stdout("  ID: {$conta->id}\n");
        $this->stdout("  Descrição: {$conta->descricao}\n");
        $this->stdout("  Valor: R$ " . number_format($conta->valor, 2, ',', '.') . "\n");
        $this->stdout("  Vencimento: " . date('d/m/Y', strtotime($conta->data_vencimento)) . "\n");
        $this->stdout("  E-mail: {$conta->usuario->email}\n\n");

        $enviado = $this->enviarEmail($conta, 'vence_hoje', 0);

        if ($enviado) {
            $this->stdout("✅ E-mail de teste enviado com sucesso!\n", \yii\helpers\Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stdout("❌ Erro ao enviar e-mail de teste\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
