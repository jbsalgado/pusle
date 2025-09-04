<?php
namespace app\assets;

use yii\web\AssetBundle;

class ChartJsAsset extends AssetBundle
{
    // Aponte para a raiz do pacote.
    public $sourcePath = '@bower/chart.js';

    // Aponte para o arquivo distribuível correto dentro da pasta 'dist'.
    public $js = [
        'dist/chart.umd.js',
    ];
}