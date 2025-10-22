<?php
namespace app\modules\api\controllers;

use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\Produto;
use yii\web\Response;
use yii\web\BadRequestHttpException;

class ProdutoController extends Controller
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'view' => ['GET', 'HEAD'],
        ];
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => 'yii\filters\ContentNegotiator',
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        return $behaviors;
    }

    /**
     * Lista todos os produtos ativos para o catálogo.
     * GET /api/produto?usuario_id=xxx
     * 
     * REQUER usuario_id obrigatório para multi-tenancy
     */
    public function actionIndex()
    {
        // Pega o usuario_id da query string
        $usuarioId = \Yii::$app->request->get('usuario_id');
        
        // Se não informar usuario_id, retorna vazio (segurança multi-tenancy)
        if (!$usuarioId) {
            \Yii::warning("Tentativa de acessar produtos sem usuario_id - bloqueado", 'api');
            
            // Retorna ActiveDataProvider vazio
            return new ActiveDataProvider([
                'query' => Produto::find()->where('1=0'), // Query que nunca retorna resultados
                'pagination' => false,
            ]);
            
            // OU pode retornar erro 400:
            // throw new BadRequestHttpException('O parâmetro usuario_id é obrigatório');
        }
        
        \Yii::info("Filtrando produtos por usuario_id: {$usuarioId}", 'api');
        
        $query = Produto::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->with(['fotos', 'categoria']);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['nome' => SORT_ASC]
            ],
        ]);
    }

    /**
     * Vê um produto específico.
     * GET /api/produto/123
     */
    public function actionView($id)
    {
        $model = Produto::find()
            ->where(['id' => $id, 'ativo' => true])
            ->with(['fotos', 'categoria'])
            ->one();

        if ($model === null) {
            throw new \yii\web\NotFoundHttpException("Produto não encontrado.");
        }
        
        return $model;
    }
}