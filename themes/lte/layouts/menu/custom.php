<?php

return [
                    ['label' => 'Geral', 'options' => ['class' => 'header']],
                    ['label' => 'Gii', 'icon' => 'fa fa-file-code-o', 'url' => ['/gii']],
                    ['label' => 'Login', 'url' => ['index.php/login/login'], 'visible' => Yii::$app->user->isGuest],
            ];