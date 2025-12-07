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
        
        // CORS para permitir cookies de sessão
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost', 'http://localhost:*', '*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        
        // Adiciona controle de acesso para actionMe
        // Usa 'denyCallback' para retornar 401 em vez de 403
        // actionDadosLoja e actionConfig são públicos (não requerem autenticação)
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'actions' => ['me'],
                    'allow' => true,
                    'roles' => ['@'], // Apenas usuários autenticados
                ],
                [
                    'actions' => ['dados-loja', 'config'],
                    'allow' => true, // Público - não requer autenticação
                ],
            ],
            'denyCallback' => function ($rule, $action) {
                Yii::$app->response->statusCode = 401;
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['erro' => 'Usuário não autenticado'];
            },
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
     * GET /api/usuario/dados-loja?usuario_id=uuid
     * Retorna dados completos da loja para comprovantes
     */
    public function actionDadosLoja($usuario_id)
    {
        try {
            $sql = "
                SELECT 
                    id,
                    nome,
                    cpf,
                    telefone,
                    email,
                    endereco,
                    bairro,
                    cidade,
                    estado,
                    logo_path
                FROM prest_usuarios
                WHERE id = :id::uuid
                LIMIT 1
            ";
            
            $usuario = Yii::$app->db->createCommand($sql, [
                ':id' => $usuario_id
            ])->queryOne();
            
            if (!$usuario) {
                Yii::$app->response->statusCode = 404;
                return ['erro' => 'Usuário não encontrado'];
            }
            
            // Busca configuração da loja (se existir)
            $config = \app\modules\vendas\models\Configuracao::findOne(['usuario_id' => $usuario_id]);
            
            // Monta endereço completo a partir dos campos individuais
            $enderecoPartes = array_filter([
                $usuario['endereco'] ?? '',
                $usuario['bairro'] ?? '',
                $usuario['cidade'] ?? '',
                $usuario['estado'] ?? ''
            ]);
            $enderecoCompleto = !empty($enderecoPartes) ? implode(', ', $enderecoPartes) : ($config->endereco_completo ?? '');
            
            // Logo: prioriza prest_configuracoes, depois prest_usuarios
            $logoPath = '';
            if ($config && !empty($config->logo_path)) {
                $logoPath = $config->logo_path;
            } elseif (!empty($usuario['logo_path'])) {
                $logoPath = $usuario['logo_path'];
            }
            
            return [
                'nome' => $usuario['nome'] ?? 'Loja',
                'cpf_cnpj' => $usuario['cpf'] ?? '',
                'telefone' => $usuario['telefone'] ?? '',
                'email' => $usuario['email'] ?? '',
                'endereco' => $usuario['endereco'] ?? '',
                'bairro' => $usuario['bairro'] ?? '',
                'cidade' => $usuario['cidade'] ?? '',
                'estado' => $usuario['estado'] ?? '',
                'endereco_completo' => $enderecoCompleto,
                'logo_path' => $logoPath,
                'nome_loja' => $config ? ($config->nome_loja ?? $usuario['nome'] ?? 'Loja') : ($usuario['nome'] ?? 'Loja'),
                // Dados PIX da configuração
                'pix_chave' => $config ? ($config->pix_chave ?? null) : null,
                'pix_nome' => $config ? ($config->pix_nome ?? null) : null,
                'pix_cidade' => $config ? ($config->pix_cidade ?? null) : null,
                // Dados adicionais de prest_configuracoes para landing page
                'cor_primaria' => $config ? ($config->cor_primaria ?? '#DC2626') : '#DC2626',
                'cor_secundaria' => $config ? ($config->cor_secundaria ?? '#F59E0B') : '#F59E0B',
                'mensagem_boas_vindas' => $config ? ($config->mensagem_boas_vindas ?? null) : null,
                'whatsapp' => $config ? ($config->whatsapp ?? null) : null,
                'instagram' => $config ? ($config->instagram ?? null) : null,
                'facebook' => $config ? ($config->facebook ?? null) : null,
                // Background image - null por padrão (usa background.jpg da landing page)
                // Pode ser adicionado campo específico na tabela depois
                'background_image' => null,
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
            // Verifica se o usuário está autenticado
            if (Yii::$app->user->isGuest) {
                Yii::$app->response->statusCode = 401;
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['erro' => 'Usuário não autenticado'];
            }
            
            $usuario = Yii::$app->user->identity;
            
            if (!$usuario) {
                Yii::$app->response->statusCode = 401;
                Yii::$app->response->format = Response::FORMAT_JSON;
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
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['erro' => $e->getMessage()];
        }
    }
}