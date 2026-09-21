<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\Usuario|null $usuarioLoja
 * @var \app\modules\vendas\models\LojaConfiguracao|null $lojaConfig
 * @var \app\modules\vendas\models\CanalSetor[] $setoresPermitidos
 * @var bool $ehDono
 * @var \app\modules\vendas\models\Colaborador[] $colaboradores
 * @var string $hubUrlCompleta
 */

$this->title = 'Canal de Comunicação Interno';
?>

<div class="canal-comunicacao-index h-[calc(100vh-80px)] flex flex-col">
    <?= $this->render('_modal_chat_whatsapp', [
        'usuarioLoja' => $usuarioLoja,
        'lojaConfig' => $lojaConfig,
        'setoresPermitidos' => $setoresPermitidos,
        'ehDono' => $ehDono,
        'colaboradores' => $colaboradores,
        'hubUrlCompleta' => $hubUrlCompleta,
    ]) ?>
</div>

<script>
    // Quando acessado diretamente como página, abre o modal imediatamente sem overlay bloqueante
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalChatWhatsApp');
        if (modal) {
            modal.classList.remove('hidden');
            // Remove o botão fechar para parecer uma tela integrada
            const btnFechar = modal.querySelector('button[title="Fechar"]');
            if (btnFechar) btnFechar.classList.add('hidden');
            if (typeof carregarConversasChat === 'function') {
                carregarConversasChat();
            }
        }
    });
</script>
