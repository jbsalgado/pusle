<?php

use app\helpers\RenderWidget;
use yii\helpers\Html;

?>
<aside class="main-sidebar">

    <section class="sidebar">

        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                </br>
                </br>
            </div>
            <div class="pull-left info">
                <p><?php if(!Yii::$app->user->isGuest){
                    echo Yii::$app->user->identity->username;
                }?></p>
            </div>
        </div>

            <?php
            $items = require('menu/custom.php');
        ?>
        <?php  //if(!Yii::$app->user->isGuest){
           RenderWidget::createRolesMenu($items);
        //}
        ?>

<span class="fa fa-sign-out fa-lg" aria-hidden="true" style="padding-left: 20px; color:white"></span>
            <?php if(!Yii::$app->user->isGuest){
                echo Html::a(
                                    'Sair',
                                    ['/index.php/site/logout'],
                                    ['data-method' => 'post', 'class' => 'btn', 'style'=>'color:white; font-size:12pt']
                        ); 
            }?>
            
    </section>

</aside>
