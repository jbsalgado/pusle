<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\modules\vendas\models\Colaborador;

class UsuarioController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        
        // Adiciona controle de acesso para actionMe
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'actions' => ['me'],
                    'allow' => true,
                    'roles' => ['@'], // Apenas usuários autenticados
                ],
            ],
        ];
        
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

    /**
     * GET /api/usuario/me
     * Retorna dados do usuário logado e do colaborador (se for vendedor)
     */
    public function actionMe()
    {
        try {
            $usuario = Yii::$app->user->identity;
            
            if (!$usuario) {
                Yii::$app->response->statusCode = 401;
                return ['erro' => 'Usuário não autenticado'];
            }

            $dados = [
                'usuario' => [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome ?? $usuario->username ?? 'Usuário',
                    'username' => $usuario->username ?? null,
                    'email' => $usuario->email ?? null,
                ],
                'colaborador' => null,
            ];

            // Buscar colaborador associado ao usuário (se for vendedor)
            $colaborador = Colaborador::find()
                ->where(['usuario_id' => $usuario->id])
                ->andWhere(['eh_vendedor' => true])
                ->andWhere(['ativo' => true])
                ->one();

            if ($colaborador) {
                $dados['colaborador'] = [
                    'id' => $colaborador->id,
                    'nome_completo' => $colaborador->nome_completo,
                    'cpf' => $colaborador->cpf,
                    'telefone' => $colaborador->telefone,
                    'email' => $colaborador->email,
                    'eh_vendedor' => $colaborador->eh_vendedor,
                    'percentual_comissao_venda' => $colaborador->percentual_comissao_venda,
                ];
            }

            return $dados;
            
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return ['erro' => $e->getMessage()];
        }
    }
}