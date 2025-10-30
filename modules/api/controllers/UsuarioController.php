<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;

class UsuarioController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * GET /api/usuario/config?usuario_id=uuid
     * Retorna configurações de pagamento da loja
     */
    public function actionConfig($usuario_id)
    {
        try {
            $sql = "
                SELECT 
                    id,
                    nome,
                    api_de_pagamento,
                    gateway_pagamento,
                    mercadopago_public_key,
                    mercadopago_sandbox,
                    asaas_sandbox,
                    catalogo_path
                FROM prest_usuarios
                WHERE id = :id::uuid
                LIMIT 1
            ";
            
            $usuario = Yii::$app->db->createCommand($sql, [
                ':id' => $usuario_id
            ])->queryOne();
            
            if (!$usuario) {
                return ['erro' => 'Usuário não encontrado'];
            }
            
            return [
                'api_de_pagamento' => $usuario['api_de_pagamento'] ?? false,
                'gateway_pagamento' => $usuario['gateway_pagamento'] ?? 'nenhum',
                'mercadopago_public_key' => $usuario['mercadopago_public_key'] ?? null,
                'mercadopago_sandbox' => $usuario['mercadopago_sandbox'] ?? true,
                'asaas_sandbox' => $usuario['asaas_sandbox'] ?? true,
                'catalogo_path' => $usuario['catalogo_path'] ?? 'catalogo'
            ];
            
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return ['erro' => $e->getMessage()];
        }
    }
}