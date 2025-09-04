<?php

namespace app\controllers;

use Yii;
use app\models\Users;
use yii\web\Controller;
use yii\web\Response;

class SignupController extends Controller
{
    // Ação para exibir o formulário
    public function actionIndex()
    {
        $model = new Users();

        if ($model->load(Yii::$app->request->post())) {
            $model->setPassword($model->password);
            $model->generateAuthKey();
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Usuário cadastrado!');
                return $this->redirect(['login']);
            }
        }

        return $this->render('signup', ['model' => $model]);
    }
}