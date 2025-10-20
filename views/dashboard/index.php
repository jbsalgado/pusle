<?php
/**
 * View: Dashboard Global
 * Localização: app/views/dashboard/index.php
 */

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $usuario app\modules\vendas\models\Usuario */
/* @var $modulos array */

$this->title = 'Dashboard - THAUSZ-PULSE';
?>

<div class="dashboard-index" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
    
    <!-- HEADER -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 15px; margin-bottom: 40px; text-align: center;">
        <h1 style="margin: 0 0 15px 0; font-size: 42px;">
            🚀 THAUSZ-PULSE
        </h1>
        <p style="margin: 0; opacity: 0.95; font-size: 20px;">
            Olá, <?= Html::encode($usuario->nome) ?>!
        </p>
        <p style="margin: 10px 0 0 0; opacity: 0.85; font-size: 14px;">
            Escolha um módulo abaixo para começar
        </p>
    </div>

    <!-- MÓDULOS DISPONÍVEIS -->
    <h2 style="margin: 0 0 30px 0; color: #333; text-align: center;">
        📦 Módulos Disponíveis
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px;">
        
        <?php foreach ($modulos as $modulo): ?>
            <?php if ($modulo['tem_acesso']): ?>
                <!-- Módulo com Acesso -->
                <?= Html::a(
                    '<div style="font-size: 64px; margin-bottom: 15px;">' . $modulo['icone'] . '</div>' .
                    '<h3 style="margin: 0 0 10px 0; font-size: 24px;">' . Html::encode($modulo['nome']) . '</h3>' .
                    '<p style="margin: 0; opacity: 0.9; font-size: 14px;">' . Html::encode($modulo['descricao']) . '</p>' .
                    '<div style="margin-top: 20px; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 5px; display: inline-block; font-weight: bold;">Acessar →</div>',
                    $modulo['url'],
                    [
                        'style' => 'background: ' . $modulo['cor'] . '; color: white; padding: 30px; border-radius: 15px; text-align: center; text-decoration: none; display: block; transition: all 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);',
                        'onmouseover' => 'this.style.transform="translateY(-5px)"; this.style.boxShadow="0 8px 12px rgba(0,0,0,0.15)"',
                        'onmouseout' => 'this.style.transform="translateY(0)"; this.style.boxShadow="0 4px 6px rgba(0,0,0,0.1)"'
                    ]
                ) ?>
            <?php else: ?>
                <!-- Módulo Bloqueado -->
                <div style="background: #e9ecef; color: #6c757d; padding: 30px; border-radius: 15px; text-align: center; opacity: 0.7; border: 2px dashed #dee2e6;">
                    <div style="font-size: 64px; margin-bottom: 15px; opacity: 0.5;"><?= $modulo['icone'] ?></div>
                    <h3 style="margin: 0 0 10px 0; font-size: 24px;"><?= Html::encode($modulo['nome']) ?></h3>
                    <p style="margin: 0 0 15px 0; font-size: 14px;"><?= Html::encode($modulo['descricao']) ?></p>
                    <div style="background: #ffc107; color: #333; padding: 8px 15px; border-radius: 5px; display: inline-block; font-size: 12px; font-weight: bold;">
                        🔒 Assine um Plano
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
    </div>

    <!-- INFORMAÇÕES ADICIONAIS -->
    <div style="background: white; border: 1px solid #dee2e6; padding: 30px; border-radius: 15px; text-align: center;">
        <h3 style="margin: 0 0 15px 0; color: #333;">
            💡 Precisa de Ajuda?
        </h3>
        <p style="margin: 0 0 20px 0; color: #666;">
            Entre em contato conosco ou consulte a documentação para mais informações.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <?= Html::a(
                '📚 Documentação',
                '#',
                ['style' => 'background: #667eea; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold;']
            ) ?>
            <?= Html::a(
                '💬 Suporte',
                '#',
                ['style' => 'background: #28a745; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold;']
            ) ?>
            <?= Html::a(
                '⚙️ Configurações',
                ['/vendas/configuracao/index'],
                ['style' => 'background: #6c757d; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold;']
            ) ?>
        </div>
    </div>

    <!-- LOGOUT -->
    <div style="text-align: center; margin-top: 30px;">
        <?= Html::a(
            '🚪 Sair',
            ['/auth/logout'],
            [
                'style' => 'color: #dc3545; text-decoration: none; font-weight: bold;',
                'data-method' => 'post'
            ]
        ) ?>
    </div>

</div>

<style>
body {
    background: #f8f9fa;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    margin: 0;
    padding: 0;
}
</style>