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
        
        // Remove authenticator para reinseri-lo depois do CORS filter
        $auth = $behaviors['authenticator'] ?? null;
        unset($behaviors['authenticator']);

        // CORS - corrigido para evitar InvalidConfigException e preflight OPTIONS
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost', 'http://localhost:*', 'http://127.0.0.1', 'http://127.0.0.1:*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        // Reinsere o authenticator DEPOIS do CORS filter
        if ($auth) {
            $behaviors['authenticator'] = $auth;
        } else {
            $behaviors['authenticator'] = [
                'class' => \yii\filters\auth\HttpBearerAuth::class,
            ];
        }
        $behaviors['authenticator']['optional'] = ['dados-loja', 'config'];

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
                    u.id,
                    u.nome,
                    u.api_de_pagamento,
                    u.gateway_pagamento,
                    u.mercadopago_public_key,
                    u.mercadopago_sandbox,
                    u.asaas_sandbox,
                    u.catalogo_path,
                    c.imprimir_automatico
                FROM prest_usuarios u
                LEFT JOIN prest_configuracoes c ON c.usuario_id = u.id
                WHERE u.id = :id::uuid
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
                'catalogo_path' => $usuario['catalogo_path'] ?? 'catalogo',
                'imprimir_automatico' => (bool)($usuario['imprimir_automatico'] ?? false)
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return ['erro' => $e->getMessage()];
        }
    }

    /**
     * GET /api/usuario/dados-loja?usuario_id=uuid
     * Retorna dados completos da loja para comprovantes
     * PRIORIZA: loja_configuracao (centralizado) > prest_usuarios (fallback)
     */
    public function actionDadosLoja($usuario_id = null)
    {
        try {
            // ✅ IDENTIFICAÇÃO INTELIGENTE DO ID DA LOJA (OWNER)
            $lojaId = $usuario_id;

            // 1. Se o usuário logado for um colaborador, usamos o ID do DONO
            $colaboradorLogado = \app\modules\vendas\models\Colaborador::getColaboradorLogado();
            if ($colaboradorLogado) {
                $lojaId = $colaboradorLogado->usuario_id;
            }
            // 2. Se não logado mas passou um ID, verifica se esse ID é de um colaborador
            elseif (!empty($lojaId)) {
                $checkColab = \app\modules\vendas\models\Colaborador::find()
                    ->where(['prest_usuario_login_id' => $lojaId])
                    ->one();
                if ($checkColab) {
                    $lojaId = $checkColab->usuario_id;
                }
            }
            // 3. Fallback para o próprio usuário logado se for dono
            elseif (!Yii::$app->user->isGuest) {
                $lojaId = Yii::$app->user->id;
            }

            if (empty($lojaId)) {
                // Se ainda não temos um ID, tenta pegar qualquer loja configurada (fallback extremo)
                $firstConfig = \app\modules\vendas\models\LojaConfiguracao::find()->one();
                if ($firstConfig) {
                    $lojaId = $firstConfig->usuario_id;
                } else {
                    Yii::$app->response->statusCode = 400;
                    return ['erro' => 'Não foi possível identificar o ID da loja'];
                }
            }

            // ✅ PRIORIDADE 1: Busca na tabela loja_configuracao (Configuração Centralizada)
            $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $lojaId]);

            // ✅ BUSCA TAMBÉM EM prest_configuracoes (Configuração Principal onde costumam estar os dados de PIX)
            $config = \app\modules\vendas\models\Configuracao::findOne(['usuario_id' => $lojaId]);

            if ($lojaConfig) {
                return [
                    'nome' => $lojaConfig->nome_loja,
                    'nome_loja' => $lojaConfig->nome_loja,
                    'razao_social' => $lojaConfig->razao_social,
                    'cpf_cnpj' => $lojaConfig->cpf_cnpj,
                    'telefone' => $lojaConfig->telefone,
                    'celular' => $lojaConfig->celular,
                    'email' => $lojaConfig->email,
                    'endereco' => $lojaConfig->logradouro . ($lojaConfig->numero ? ', ' . $lojaConfig->numero : ''),
                    'bairro' => $lojaConfig->bairro,
                    'cidade' => $lojaConfig->cidade,
                    'estado' => $lojaConfig->estado,
                    'cep' => $lojaConfig->cep,
                    'endereco_completo' => $lojaConfig->getEnderecoCompleto(),
                    'logo_path' => $lojaConfig->logo_path,
                    'inscricao_estadual' => $lojaConfig->inscricao_estadual,
                    'inscricao_municipal' => $lojaConfig->inscricao_municipal,
                    'site' => $lojaConfig->site,
                    // ✅ PRIORIZA: prest_configuracoes para PIX se preenchido, senão usa loja_configuracao
                    'pix_chave' => $config->pix_chave ?? $lojaConfig->pix_chave,
                    'pix_nome' => $config->pix_nome ?? $lojaConfig->pix_nome,
                    'pix_cidade' => $config->pix_cidade ?? $lojaConfig->pix_cidade,
                    'aparencia' => [
                        'tema' => $lojaConfig->aparencia_tema ?: 'azul',
                        'cor_primaria' => $lojaConfig->aparencia_cor_primaria,
                        'cor_secundaria' => $lojaConfig->aparencia_cor_secundaria,
                        'escala_cores' => $lojaConfig->getEscalaCores(),
                    ],
                ];
            }

            // ⚠️ FALLBACK: Busca em prest_usuarios (Configuração Legada)
            $sql = "
                SELECT 
                    id, nome, cpf, telefone, email, endereco, bairro, cidade, estado, logo_path
                FROM prest_usuarios
                WHERE id = :id::uuid
                LIMIT 1
            ";

            $usuario = Yii::$app->db->createCommand($sql, [
                ':id' => $lojaId
            ])->queryOne();

            if (!$usuario) {
                Yii::$app->response->statusCode = 404;
                return ['erro' => 'Configuração da loja não encontrada'];
            }

            // Monta endereço completo
            $enderecoPartes = array_filter([
                $usuario['endereco'] ?? '',
                $usuario['bairro'] ?? '',
                $usuario['cidade'] ?? '',
                $usuario['estado'] ?? ''
            ]);
            // Se tiver $config (prest_configuracoes), usa o endereço dela se o outro estiver vazio
            $enderecoCompleto = !empty($enderecoPartes) ? implode(', ', $enderecoPartes) : ($config->endereco_completo ?? '');

            $tempConfig = new \app\modules\vendas\models\LojaConfiguracao();
            $tempConfig->aparencia_tema = 'azul';

            return [
                'nome' => $usuario['nome'] ?? 'Loja',
                'nome_loja' => $config->nome_loja ?? $usuario['nome'] ?? 'Loja',
                'cpf_cnpj' => $usuario['cpf'] ?? '',
                'telefone' => $usuario['telefone'] ?? '',
                'email' => $usuario['email'] ?? '',
                'endereco' => $usuario['endereco'] ?? '',
                'bairro' => $usuario['bairro'] ?? '',
                'cidade' => $usuario['cidade'] ?? '',
                'estado' => $usuario['estado'] ?? '',
                'endereco_completo' => $enderecoCompleto,
                'logo_path' => (!empty($config->logo_path)) ? $config->logo_path : ($usuario['logo_path'] ?? ''),
                // ✅ PEGA SEMPRE DE prest_configuracoes (Configuracao) para PIX no fallback
                'pix_chave' => $config->pix_chave ?? null,
                'pix_nome' => $config->pix_nome ?? null,
                'pix_cidade' => $config->pix_cidade ?? null,
                'aparencia' => [
                    'tema' => 'azul',
                    'cor_primaria' => null,
                    'cor_secundaria' => null,
                    'escala_cores' => $tempConfig->getEscalaCores(),
                ],
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

            // Buscar colaborador associado ao usuário logado (usando helper estático)
            $colaborador = Colaborador::getColaboradorLogado();

            if ($colaborador) {
                $dados['colaborador'] = [
                    'id' => $colaborador->id,
                    'usuario_id' => $colaborador->usuario_id, // ID do Dono da Loja
                    'nome_completo' => $colaborador->nome_completo,
                    'cpf' => $colaborador->cpf,
                    'telefone' => $colaborador->telefone,
                    'email' => $colaborador->email,
                    'eh_vendedor' => (bool)$colaborador->eh_vendedor,
                    'percentual_comissao_venda' => (float)$colaborador->percentual_comissao_venda,
                ];
            }

            return $dados;
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['erro' => $e->getMessage()];
        }
    }

    /**
     * POST /api/usuario/salvar-aparencia
     * Salva as configurações de aparência da loja
     */
    public function actionSalvarAparencia()
    {
        try {
            Yii::$app->response->format = Response::FORMAT_JSON;

            // Apenas usuários logados podem alterar a aparência
            if (Yii::$app->user->isGuest) {
                Yii::$app->response->statusCode = 401;
                return ['erro' => 'Usuário não autenticado'];
            }

            // Identificar o ID da loja (usuario_id dono)
            $usuarioLogado = Yii::$app->user->identity;
            $lojaId = $usuarioLogado->id;

            // Se for colaborador vendedor, o dono da loja está no campo usuario_id do colaborador
            $colaborador = Colaborador::getColaboradorLogado();
            if ($colaborador && $colaborador->usuario_id) {
                $lojaId = $colaborador->usuario_id;
            }

            // Carregar a requisição POST
            $postData = Yii::$app->request->post();
            
            // Caso seja enviado como JSON no body
            if (empty($postData)) {
                $rawBody = Yii::$app->request->getRawBody();
                $postData = json_decode($rawBody, true) ?: [];
            }

            $tema = $postData['tema'] ?? 'azul';
            $corPrimaria = $postData['cor_primaria'] ?? null;
            $corSecundaria = $postData['cor_secundaria'] ?? null;

            // Buscar ou criar a configuração da loja
            $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $lojaId]);
            if (!$lojaConfig) {
                $lojaConfig = new \app\modules\vendas\models\LojaConfiguracao();
                $lojaConfig->usuario_id = $lojaId;
                $lojaConfig->nome_loja = $usuarioLogado->nome ?? 'Minha Loja';
                $lojaConfig->cpf_cnpj = '00.000.000/0000-00';
            }

            $lojaConfig->aparencia_tema = $tema;
            $lojaConfig->aparencia_cor_primaria = $corPrimaria;
            $lojaConfig->aparencia_cor_secundaria = $corSecundaria;

            if ($lojaConfig->save()) {
                return [
                    'sucesso' => true,
                    'mensagem' => 'Aparência salva com sucesso!',
                    'aparencia' => [
                        'tema' => $lojaConfig->aparencia_tema,
                        'cor_primaria' => $lojaConfig->aparencia_cor_primaria,
                        'cor_secundaria' => $lojaConfig->aparencia_cor_secundaria,
                        'escala_cores' => $lojaConfig->getEscalaCores(),
                    ]
                ];
            } else {
                Yii::$app->response->statusCode = 422;
                return [
                    'sucesso' => false,
                    'erro' => 'Falha ao salvar as configurações.',
                    'erros' => $lojaConfig->getErrors()
                ];
            }

        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 500;
            return [
                'sucesso' => false,
                'erro' => $e->getMessage(),
                'classe' => get_class($e),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
}
