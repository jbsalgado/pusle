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
        return $behaviors;
    }

    /**
     * Lista todos os produtos ativos para o catálogo.
     * GET /api/produto (ou /api/produtos se pluralize=true)
     */
    // public function actionIndex()
    // {
    //     $query = Produto::find()
    //         ->where(['ativo' => true])
    //         ->with(['categoria', 'fotos']); // Carrega as relações

    //     // Retorna o ActiveDataProvider diretamente. O Controller cuidará da serialização.
    //     return new ActiveDataProvider([
    //         'query' => $query,
    //         'pagination' => [
    //             'pageSize' => 20,
    //         ],
    //         'sort' => [
    //             'defaultOrder' => ['nome' => SORT_ASC]
    //         ],
    //         // REMOVIDO: A configuração 'serializer' estava incorreta aqui.
    //     ]);
    // }

    /**
     * Lista todos os produtos ativos para o catálogo.
     * GET /api/produto (ou /api/produtos se pluralize=true)
     * ***** VERSÃO DE TESTE PARA DEPURAÇÃO *****
     */
    public function actionIndex()
    {
        // 1. Busca os modelos diretamente, carregando a relação 'fotos'
        $models = Produto::find()
            ->where(['ativo' => true])
            ->with(['fotos']) // Tenta carregar as fotos
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        // 2. Converte manualmente para array, FORÇANDO a inclusão de 'fotos'
        // Usamos toArray() que respeita os métodos fields() e extraFields() do modelo
        $data = [];
        foreach ($models as $model) {
             // O segundo parâmetro ['fotos'] diz para expandir essa relação
             // se ela estiver definida em fields() ou extraFields()
            $data[] = $model->toArray([], ['fotos']);
        }

        // 3. Retorna o array diretamente (O Yii cuidará de converter para JSON)
        // Se 'fotos' aparecer no console com este código, o problema está na
        // serialização do ActiveDataProvider. Se NÃO aparecer, o problema
        // está no modelo Produto.php, na relação getFotos() ou no banco de dados.
        return $data;


        /* // Código original com ActiveDataProvider comentado para o teste
        $query = Produto::find()
            ->where(['ativo' => true])
            ->with(['categoria', 'fotos']);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['nome' => SORT_ASC]
            ]
        ]);
        */
    }

    /**
     * Vê um produto específico.
     * GET /api/produto/123 (ou /api/produtos/123 se pluralize=true)
     */
    public function actionView($id)
    {
        $model = Produto::find()
            ->where(['id' => $id, 'ativo' => true])
            ->with(['categoria', 'fotos'])
            ->one();

        if ($model === null) {
            throw new \yii\web\NotFoundHttpException("Produto não encontrado.");
        }
        // Retornar o modelo diretamente é o padrão e deve funcionar
        // para incluir as relações carregadas via ->with().
        return $model;
    }
}