<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/output.css', // Arquivo CSS do Tailwind gerado
        'css/site.css',   // Manter seus estilos customizados se necessário
    ];
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
        // Comentar ou remover Bootstrap se quiser usar apenas Tailwind
        'yii\bootstrap\BootstrapAsset',
    ];
}