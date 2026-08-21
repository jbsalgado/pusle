<?php

namespace app\modules\vendas\services;

use Yii;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\LojaConfiguracao;

/**
 * Serviço responsável por gerar e disparar e-mails promocionais de produtos.
 */
class EmailDisparoService
{
    /**
     * Envia um e-mail promocional com o card do produto para o cliente especificado.
     *
     * @param string $emailDestino E-mail do cliente
     * @param Produto $produto Model do produto
     * @param string $cardAbsPath Caminho absoluto do card gerado (PNG)
     * @param string|null $cardUrl URL pública do card
     * @param string|null $mensagemCorpo Texto customizado do e-mail
     * @return bool
     */
    public function enviarEmailCard(string $emailDestino, Produto $produto, string $cardAbsPath, ?string $cardUrl = null, ?string $mensagemCorpo = null): bool
    {
        if (empty($emailDestino) || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            Yii::error("E-mail de destino inválido: {$emailDestino}", __METHOD__);
            return false;
        }

        $loja = LojaConfiguracao::findOne(['usuario_id' => $produto->usuario_id]);
        $nomeLoja = $loja ? ($loja->nome_fantasia ?: $loja->nome_loja) : 'PULSE';

        $precoFinal = $produto->getPrecoFinal();
        $precoStr = 'R$ ' . number_format((float)$precoFinal, 2, ',', '.');
        $precoOriginalStr = $produto->preco_venda_sugerido > 0 ? 'R$ ' . number_format((float)$produto->preco_venda_sugerido, 2, ',', '.') : null;

        $assunto = "🔥 Oferta Especial: {$produto->nome} por apenas {$precoStr}!";

        // Construir corpo do e-mail em HTML responsivo
        $html = $this->gerarTemplateHtml($produto, $nomeLoja, $precoStr, $precoOriginalStr, $cardUrl, $mensagemCorpo);

        try {
            $mailer = Yii::$app->mailer->compose();
            $mailer->setTo($emailDestino);
            $mailer->setSubject($assunto);
            $mailer->setHtmlBody($html);

            // Anexar/Embutir a imagem se existir no disco local
            if (file_exists($cardAbsPath)) {
                $cid = $mailer->embed($cardAbsPath);
                // Substituir placeholder pelo CID incorporado
                $htmlComCid = str_replace('{{CARD_IMAGE_SRC}}', $cid, $html);
                $mailer->setHtmlBody($htmlComCid);
            } else if ($cardUrl) {
                $htmlComUrl = str_replace('{{CARD_IMAGE_SRC}}', $cardUrl, $html);
                $mailer->setHtmlBody($htmlComUrl);
            }

            return (bool) $mailer->send();
        } catch (\Exception $e) {
            Yii::error("Erro ao enviar e-mail promocional para {$emailDestino}: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Gera o HTML responsivo do e-mail promocional.
     */
    private function gerarTemplateHtml(Produto $produto, string $nomeLoja, string $precoStr, ?string $precoOriginalStr, ?string $cardUrl, ?string $mensagemCorpo): string
    {
        $corpoPersonalizado = !empty($mensagemCorpo) ? nl2br(htmlspecialchars($mensagemCorpo)) : "Confira esta oferta incrível preparada especialmente para você!";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oferta Especial</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 24px; color:#ffffff;">
                            <h2 style="margin:0; font-size:22px; font-weight:800; letter-spacing: -0.5px;">{$nomeLoja}</h2>
                            <p style="margin:4px 0 0 0; font-size:13px; color:#e0e7ff;">Oferta Exclusiva para Você</p>
                        </td>
                    </tr>

                    <!-- Card Image -->
                    <tr>
                        <td align="center" style="padding: 20px; background-color:#0f172a;">
                            <img src="{{CARD_IMAGE_SRC}}" alt="Card do Produto" style="max-width:100%; height:auto; border-radius:12px; display:block; box-shadow:0 8px 20px rgba(0,0,0,0.3);">
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 24px 30px;">
                            <h3 style="margin:0 0 10px 0; color:#1e293b; font-size:20px; font-weight:700;">{$produto->nome}</h3>
                            <p style="margin:0 0 16px 0; color:#475569; font-size:14px; line-height:1.6;">{$corpoPersonalizado}</p>
                            
                            <div style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; text-align:center;">
                                <span style="font-size:12px; color:#64748b; font-weight:600; text-transform:uppercase;">Preço Especial</span><br>
                                <span style="font-size:28px; color:#16a34a; font-weight:900;">{$precoStr}</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color:#f8fafc; padding: 16px; border-top:1px solid #e2e8f0; color:#94a3b8; font-size:12px;">
                            <p style="margin:0;">Enviado com carinho por <strong>{$nomeLoja}</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
