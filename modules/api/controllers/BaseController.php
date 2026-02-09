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
        return $behaviors;
    }

    /**
     * Standard success response
     */
    protected function success($data = [], $message = 'Success')
    {
        if ($data instanceof \yii\data\DataProviderInterface) {
            return [
                'success' => true,
                'items' => $data->getModels(),
                'meta' => [
                    'totalCount' => (int)$data->getTotalCount(),
                    'pageCount' => $data->getPagination() ? (int)$data->getPagination()->getPageCount() : 1,
                    'currentPage' => $data->getPagination() ? (int)$data->getPagination()->getPage() + 1 : 1,
                    'perPage' => $data->getPagination() ? (int)$data->getPagination()->pageSize : (int)$data->getTotalCount(),
                ],
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
