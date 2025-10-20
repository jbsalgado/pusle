<?php
namespace app\modules\vendas\controllers\api;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\FormaPagamento;

/**
 * API REST para Formas de Pagamento
 * 
 * Endpoints:
 * GET    /api/formas-pagamento          - Lista todas
 * GET    /api/formas-pagamento/{id}     - Visualiza uma
 * POST   /api/formas-pagamento          - Cria nova
 * PUT    /api/formas-pagamento/{id}     - Atualiza
 * DELETE /api/formas-pagamento/{id}     - Deleta
 * GET    /api/formas-pagamento/ativas   - Lista apenas ativas
 */
class FormaPagamentoApiController extends ActiveController
{
    public $modelClass = 'app\modules\vendas\models\FormaPagamento';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // CORS para permitir requisições de diferentes origens
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        // Autenticação via Bearer Token
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'], // Permitir OPTIONS sem autenticação
        ];

        // Rate Limiting
        $behaviors['rateLimiter']['enableRateLimitHeaders'] = true;

        return $behaviors;
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        $actions = parent::actions();

        // Personalizar ação index
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        // Desabilitar ações não necessárias
        // unset($actions['delete']);

        return $actions;
    }

    /**
     * Prepara o data provider filtrando por usuário
     */
    public function prepareDataProvider()
    {
        $query = FormaPagamento::find()
            ->where(['usuario_id' => Yii::$app->user->id]);

        // Filtros via query string
        $tipo = Yii::$app->request->get('tipo');
        if ($tipo) {
            $query->andWhere(['tipo' => $tipo]);
        }

        $ativo = Yii::$app->request->get('ativo');
        if ($ativo !== null) {
            $query->andWhere(['ativo' => $ativo]);
        }

        $parcelamento = Yii::$app->request->get('aceita_parcelamento');
        if ($parcelamento !== null) {
            $query->andWhere(['aceita_parcelamento' => $parcelamento]);
        }

        // Busca por nome
        $search = Yii::$app->request->get('q');
        if ($search) {
            $query->andWhere(['like', 'nome', $search]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => Yii::$app->request->get('per_page', 20),
            ],
            'sort' => [
                'defaultOrder' => ['nome' => SORT_ASC]
            ],
        ]);
    }

    /**
     * Ação customizada: listar apenas formas ativas
     */
    public function actionAtivas()
    {
        $formas = FormaPagamento::find()
            ->where([
                'usuario_id' => Yii::$app->user->id,
                'ativo' => true
            ])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        return $formas;
    }

    /**
     * Ação customizada: estatísticas
     */
    public function actionStats()
    {
        $userId = Yii::$app->user->id;

        $stats = [
            'total' => FormaPagamento::find()->where(['usuario_id' => $userId])->count(),
            'ativas' => FormaPagamento::find()->where(['usuario_id' => $userId, 'ativo' => 1])->count(),
            'inativas' => FormaPagamento::find()->where(['usuario_id' => $userId, 'ativo' => 0])->count(),
            'com_parcelamento' => FormaPagamento::find()->where(['usuario_id' => $userId, 'aceita_parcelamento' => 1])->count(),
            'por_tipo' => FormaPagamento::find()
                ->select(['tipo', 'COUNT(*) as total'])
                ->where(['usuario_id' => $userId])
                ->groupBy('tipo')
                ->asArray()
                ->all(),
        ];

        return $stats;
    }

    /**
     * Ação customizada: alternar status ativo/inativo
     */
    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);
        $model->ativo = !$model->ativo;
        
        if ($model->save(false)) {
            return [
                'success' => true,
                'message' => 'Status alterado com sucesso',
                'ativo' => $model->ativo
            ];
        }

        return [
            'success' => false,
            'message' => 'Erro ao alterar status',
            'errors' => $model->errors
        ];
    }

    /**
     * Ação customizada: verificar se pode deletar
     */
    public function actionCanDelete($id)
    {
        $model = $this->findModel($id);
        $temParcelas = $model->getParcelas()->count() > 0;

        return [
            'can_delete' => !$temParcelas,
            'total_parcelas' => $model->getParcelas()->count(),
            'message' => $temParcelas 
                ? 'Não pode deletar: possui parcelas associadas' 
                : 'Pode deletar'
        ];
    }

    /**
     * Verifica permissões antes de cada ação
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // Verifica se o model pertence ao usuário logado
        if ($model && $model->usuario_id !== Yii::$app->user->id) {
            throw new \yii\web\ForbiddenHttpException('Você não tem permissão para acessar este recurso.');
        }
    }

    /**
     * Encontra o model e verifica permissões
     */
    protected function findModel($id)
    {
        $model = FormaPagamento::findOne([
            'id' => $id,
            'usuario_id' => Yii::$app->user->id
        ]);

        if ($model === null) {
            throw new \yii\web\NotFoundHttpException('Forma de pagamento não encontrada.');
        }

        return $model;
    }

    /**
     * Sobrescreve beforeAction para adicionar o usuario_id automaticamente
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            // Se for POST, adiciona usuario_id automaticamente
            if (Yii::$app->request->isPost) {
                $data = Yii::$app->request->getBodyParams();
                if (isset($data['FormaPagamento'])) {
                    $data['FormaPagamento']['usuario_id'] = Yii::$app->user->id;
                } else {
                    $data['usuario_id'] = Yii::$app->user->id;
                }
                Yii::$app->request->setBodyParams($data);
            }
            return true;
        }
        return false;
    }
}