<?php

namespace app\modules\marketplace\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use app\components\TenantHelper;
use app\modules\vendas\models\Categoria;
use app\modules\marketplace\models\MarketplaceCategoriaMap;
use app\modules\marketplace\models\MarketplaceConfig;

/**
 * CategoriaMapController - Gestão de Mapeamento De-Para de Categorias e Atributos de Marketplaces
 */
class CategoriaMapController extends Controller
{
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
     * Lista todas as categorias internas e o status de mapeamento para cada canal
     */
    public function actionIndex()
    {
        $usuarioId = TenantHelper::getId();
        $marketplace = Yii::$app->request->get('marketplace', MarketplaceConfig::MARKETPLACE_SHOPEE);

        $categorias = Categoria::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        $mapeamentos = MarketplaceCategoriaMap::find()
            ->where(['usuario_id' => $usuarioId, 'marketplace' => $marketplace])
            ->indexBy('categoria_id')
            ->all();

        return $this->render('index', [
            'categorias' => $categorias,
            'mapeamentos' => $mapeamentos,
            'marketplace' => $marketplace,
        ]);
    }

    /**
     * Salva o vínculo de uma categoria e seus atributos via AJAX
     */
    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $usuarioId = TenantHelper::getId();

        $categoriaId = Yii::$app->request->post('categoria_id');
        $marketplace = Yii::$app->request->post('marketplace');
        $marketplaceCategoriaId = Yii::$app->request->post('marketplace_categoria_id');
        $marketplaceCategoriaNome = Yii::$app->request->post('marketplace_categoria_nome');

        if (!$categoriaId || !$marketplace || !$marketplaceCategoriaId) {
            return ['success' => false, 'message' => 'Parâmetros obrigatórios ausentes.'];
        }

        $map = MarketplaceCategoriaMap::findOne([
            'usuario_id' => $usuarioId,
            'marketplace' => $marketplace,
            'categoria_id' => $categoriaId,
        ]) ?? new MarketplaceCategoriaMap();

        $map->usuario_id = $usuarioId;
        $map->categoria_id = $categoriaId;
        $map->marketplace = $marketplace;
        $map->marketplace_categoria_id = $marketplaceCategoriaId;
        $map->marketplace_categoria_nome = $marketplaceCategoriaNome;

        if ($map->save()) {
            return ['success' => true, 'message' => 'Mapeamento salvo com sucesso!'];
        }

        return ['success' => false, 'errors' => $map->errors];
    }
}
