<?php

namespace app\modules\cobranca\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use app\modules\cobranca\models\CobrancaTemplate;

/**
 * TemplateController
 * 
 * Gerencia os templates de mensagens de cobrança
 */
class TemplateController extends Controller
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lista todos os templates do usuário
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        $templates = CobrancaTemplate::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['tipo' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Atualiza um template
     */
    public function actionUpdate($tipo)
    {
        $usuarioId = Yii::$app->user->id;

        $model = CobrancaTemplate::findOne([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
        ]);

        if (!$model) {
            throw new NotFoundHttpException('Template não encontrado.');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Template atualizado com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Preview do template com dados de exemplo (AJAX)
     */
    public function actionPreview()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $mensagem = Yii::$app->request->post('mensagem', '');

        // Dados de exemplo
        $variaveis = [
            '{nome}' => 'João Silva',
            '{valor}' => '150,00',
            '{vencimento}' => date('d/m/Y', strtotime('+3 days')),
            '{parcela}' => '1/12',
            '{empresa}' => Yii::$app->name,
        ];

        $preview = str_replace(array_keys($variaveis), array_values($variaveis), $mensagem);

        return [
            'success' => true,
            'preview' => $preview,
        ];
    }
}
