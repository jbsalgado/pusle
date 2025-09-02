<?php
/**
 * Adicione este código ao seu layout principal (views/layouts/main.php)
 * Antes do </head>
 */
?>

<!-- Adicione estas bibliotecas CSS no <head> do layout -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<!-- CSS Global personalizado -->
<style>
/* Reset e configurações globais */
* {
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8f9fb;
    line-height: 1.6;
}

/* Container responsivo */
.container-fluid {
    max-width: 1400px;
    margin: 0 auto;
}

/* Animações suaves */
.animated {
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translate3d(0, 40px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.fadeInUp {
    animation-name: fadeInUp;
}

/* Scrollbar personalizada */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #5a6fd8;
}

/* Mobile First - Configurações gerais */
@media (max-width: 576px) {
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .btn {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
}

/* Melhorias no Select2 */
.select2-container {
    width: 100% !important;
}

.select2-container--krajee .select2-selection--multiple .select2-selection__choice {
    margin-top: 4px;
    margin-right: 6px;
}

/* Estilos para badges */
.badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
}

.badge-success {
    color: #155724;
    background-color: #d4edda;
}

.badge-danger {
    color: #721c24;
    background-color: #f8d7da;
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Configurações do Toastr */
.toast-top-right {
    top: 20px;
    right: 20px;
}

.toast-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.toast-error {
    background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
}

.toast-info {
    background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
}

.toast-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

/* Responsive tables */
@media (max-width: 768px) {
    .table-responsive table,
    .table-responsive thead,
    .table-responsive tbody,
    .table-responsive th,
    .table-responsive td,
    .table-responsive tr {
        display: block;
    }

    .table-responsive thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    .table-responsive tr {
        border: 1px solid #ccc;
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 8px;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .table-responsive td {
        border: none;
        position: relative;
        padding: 8px 8px 8px 25%;
        white-space: normal;
        text-align: left;
    }

    .table-responsive td:before {
        content: attr(data-label) ": ";
        position: absolute;
        left: 6px;
        width: 20%;
        padding-right: 10px;
        white-space: nowrap;
        text-align: left;
        font-weight: bold;
        color: #333;
    }
}
</style>

<!-- Adicione estes scripts antes do </body> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
// Configurações globais do Toastr
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

// Função global para mostrar mensagens flash do Yii2
<?php if (Yii::$app->session->hasFlash('success')): ?>
    toastr.success('<?= Yii::$app->session->getFlash('success') ?>');
<?php endif; ?>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    toastr.error('<?= Yii::$app->session->getFlash('error') ?>');
<?php endif; ?>

<?php if (Yii::$app->session->hasFlash('info')): ?>
    toastr.info('<?= Yii::$app->session->getFlash('info') ?>');
<?php endif; ?>

<?php if (Yii::$app->session->hasFlash('warning')): ?>
    toastr.warning('<?= Yii::$app->session->getFlash('warning') ?>');
<?php endif; ?>

// Função para adicionar labels responsivos nas tabelas
$(document).ready(function() {
    // Adiciona labels para tabelas responsivas
    $('.table-responsive table').each(function() {
        var $table = $(this);
        var $headers = $table.find('thead th');
        
        $table.find('tbody tr').each(function() {
            var $row = $(this);
            $row.find('td').each(function(index) {
                var $cell = $(this);
                var headerText = $headers.eq(index).text().trim();
                $cell.attr('data-label', headerText);
            });
        });
    });

    // Smooth scrolling para âncoras
    $('a[href*="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if(target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 1000);
        }
    });

    // Loading states para formulários
    $('form').on('submit', function() {
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
        
        $submitBtn.addClass('loading').prop('disabled', true);
        
        // Restaura o estado após 5 segundos (timeout de segurança)
        setTimeout(function() {
            $submitBtn.removeClass('loading').prop('disabled', false);
        }, 5000);
    });

    // Confirmação melhorada para exclusões
    $('a[data-confirm]').on('click', function(e) {
        var message = $(this).data('confirm');
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>