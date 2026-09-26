<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Throwable */
/* @var $incidentId string|null */

use yii\helpers\Html;

$this->title = $name;

$incidentId = $incidentId ?? null;

$usuario = Yii::$app->user->getIsGuest() ? null : Yii::$app->user->identity;
$ehAdmin = $usuario !== null && !empty($usuario->is_admin);

// Detalhes técnicos: sempre em DEV; em produção só para admin da plataforma.
$detalhes = [];
if (YII_DEBUG) {
    $detalhes['Classe']   = get_class($exception);
    $detalhes['Mensagem'] = $exception->getMessage();
    $detalhes['Origem']   = $exception->getFile() . ':' . $exception->getLine();
    $detalhes['Código']   = (string)$exception->getCode();
} elseif ($ehAdmin) {
    $detalhes['Classe'] = get_class($exception);
    $detalhes['Código'] = (string)$exception->getCode();
}

$urlAtual = Yii::$app->request->getUrl();
?>
<div class="site-error" style="max-width:640px;margin:56px auto;padding:0 16px;font-family:Inter,system-ui,-apple-system,sans-serif;color:#111827;">

    <h1 style="font-size:24px;line-height:1.3;margin:0 0 16px;"><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-danger" style="background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:8px;padding:16px;margin-bottom:20px;color:#7f1d1d;">
        <?= nl2br(Html::encode($message)) ?>
    </div>

    <p style="margin:0 0 8px;">O erro acima ocorreu enquanto o servidor processava sua requisição.</p>
    <p style="margin:0 0 20px;">Se o problema persistir, informe o <strong>código do incidente</strong> abaixo ao suporte.</p>

    <table style="border-collapse:collapse;font-size:13px;margin-bottom:20px;">
        <?php if ($incidentId): ?>
            <tr>
                <td style="padding:4px 12px 4px 0;color:#6b7280;">Código do incidente</td>
                <td style="padding:4px 0;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;font-weight:600;"><?= Html::encode($incidentId) ?></code></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td style="padding:4px 12px 4px 0;color:#6b7280;">Horário</td>
            <td style="padding:4px 0;"><?= Html::encode(date('d/m/Y H:i:s')) ?></td>
        </tr>
        <tr>
            <td style="padding:4px 12px 4px 0;color:#6b7280;">Endereço</td>
            <td style="padding:4px 0;word-break:break-all;"><?= Html::encode($urlAtual) ?></td>
        </tr>
    </table>

    <p style="margin:0 0 20px;">
        <a href="<?= Html::encode(Yii::$app->homeUrl) ?>" style="color:#2563eb;text-decoration:none;font-weight:600;">&larr; Voltar ao in&iacute;cio</a>
    </p>

    <?php if (!empty($detalhes)): ?>
        <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
        <p style="font-size:12px;color:#6b7280;margin:0 0 8px;">Detalhes t&eacute;cnicos (vis&iacute;veis apenas em desenvolvimento ou para administradores):</p>
        <ul style="font-size:12px;color:#374151;margin:0;padding-left:20px;">
            <?php foreach ($detalhes as $rotulo => $valor): ?>
                <li><strong><?= Html::encode($rotulo) ?>:</strong> <?= Html::encode((string)$valor) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p style="font-size:12px;color:#9ca3af;margin-top:24px;">
        Se voc&ecirc; acredita que isto &eacute; um erro do servidor, entre em contato conosco. Obrigado.
    </p>
</div>

