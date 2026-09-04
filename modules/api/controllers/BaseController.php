<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        // ✅ Adiciona autenticação JWT (Bearer Token)
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * Retorna campos extras solicitados via ?expand=...
     */
    protected function getRequestedExpands()
    {
        $expand = Yii::$app->request->get('expand');
        if (is_string($expand)) {
            return preg_split('/\s*,\s*/', trim($expand), -1, PREG_SPLIT_NO_EMPTY);
        }
        return [];
    }

    /**
     * Retorna campos específicos solicitados via ?fields=...
     */
    protected function getRequestedFields()
    {
        $fields = Yii::$app->request->get('fields');
        if (is_string($fields)) {
            return preg_split('/\s*,\s*/', trim($fields), -1, PREG_SPLIT_NO_EMPTY);
        }
        return [];
    }

    /**
     * Standard success response
     */
    protected function success($data = [], $message = 'Success')
    {
        $fields = $this->getRequestedFields();
        $expand = $this->getRequestedExpands();

        if ($data instanceof \yii\data\DataProviderInterface) {
            $models = $data->getModels();
            $items = [];
            foreach ($models as $model) {
                if ($model instanceof \yii\base\Arrayable) {
                    $items[] = $model->toArray($fields, $expand);
                } else {
                    $items[] = $model;
                }
            }

            return [
                'success' => true,
                'items' => $items,
                'meta' => [
                    'totalCount' => (int)$data->getTotalCount(),
                    'pageCount' => $data->getPagination() ? (int)$data->getPagination()->getPageCount() : 1,
                    'currentPage' => $data->getPagination() ? (int)$data->getPagination()->getPage() + 1 : 1,
                    'perPage' => $data->getPagination() ? (int)$data->getPagination()->pageSize : (int)$data->getTotalCount(),
                ],
                'message' => $message,
            ];
        }

        if ($data instanceof \yii\base\Arrayable) {
            return [
                'success' => true,
                'data' => $data->toArray($fields, $expand),
                'message' => $message,
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];
    }

    /**
     * Standard error response
     */
    protected function error($message = 'Error', $code = 400, $data = [])
    {
        Yii::$app->response->statusCode = $code;
        return [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ];
    }
}
