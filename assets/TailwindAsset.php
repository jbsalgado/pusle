<?php

namespace app\assets;

use yii\web\AssetBundle;

class TailwindAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/tailwind.css', // O arquivo gerado pelo Tailwind
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}