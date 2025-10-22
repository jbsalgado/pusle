<?php
namespace app\modules\api\controllers;

use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\Produto;
use yii\web\Response;

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
        // O Serializer padrão do yii\rest\Controller será usado automaticamente
        // e agora deve respeitar o fields() do modelo Produto.php
        return $behaviors;
    }

    /**
     * Lista todos os produtos ativos para o catálogo.
     * GET /api/produto (ou /api/produtos se pluralize=true)
     */
    public function actionIndex()
    {
        $query = Produto::find()
            ->where(['ativo' => true])
            // Carrega a relação 'fotos'. O método fields() no Produto.php
            // garantirá que ela seja incluída no JSON final.
            ->with(['fotos', 'categoria']); // Pode incluir 'categoria' se o frontend precisar

        // Retorna o ActiveDataProvider. O Controller REST e o método fields() do modelo
        // devem lidar corretamente com a serialização agora.
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20, // Ajuste conforme necessário
            ],
            'sort' => [
                'defaultOrder' => ['nome' => SORT_ASC]
            ],
        ]);
    }

    /**
     * Vê um produto específico.
     * GET /api/produto/123 (ou /api/produtos/123 se pluralize=true)
     */
    public function actionView($id)
    {
        $model = Produto::find()
            ->where(['id' => $id, 'ativo' => true])
            ->with(['fotos', 'categoria']) // Garante que as relações sejam carregadas
            ->one();

        if ($model === null) {
            throw new \yii\web\NotFoundHttpException("Produto não encontrado.");
        }
        // Retornar o modelo diretamente deve funcionar, pois respeita fields()
        return $model;
    }
}