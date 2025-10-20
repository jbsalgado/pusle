<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\modules\vendas\models\Cliente;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use yii\web\NotFoundHttpException;

class ClienteController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'optional' => ['index', 'create', 'buscar-cpf', 'login'],
        ];
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'buscar-cpf' => ['GET'],
            'login' => ['POST'],
        ];
    }

    /**
     * Busca clientes por termo (nome ou CPF)
     * GET /api/cliente?termo=xxx&usuario_id=yyy
     */
    public function actionIndex()
    {
        $termo = Yii::$app->request->get('termo');
        $usuarioId = Yii::$app->request->get('usuario_id');
        
        if (empty($usuarioId)) {
            throw new BadRequestHttpException('Parâmetro usuario_id é obrigatório.');
        }
        
        if (empty($termo)) {
            return [];
        }
        
        $cpfLimpo = preg_replace('/[^0-9]/', '', $termo);
        
        $query = Cliente::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->andWhere(['or', 
                ['ilike', 'nome_completo', $termo], 
                ['=', 'cpf', $cpfLimpo]
            ])
            ->select(['id', 'nome_completo', 'cpf'])
            ->limit(10);
        
        return $query->asArray()->all();
    }

    /**
     * Busca cliente por CPF (para verificar se existe)
     * GET /api/cliente/buscar-cpf?cpf=12345678900&usuario_id=xxx
     */
    public function actionBuscarCpf()
    {
        $cpf = Yii::$app->request->get('cpf');
        $usuarioId = Yii::$app->request->get('usuario_id');
        
        if (empty($cpf)) {
            throw new BadRequestHttpException('CPF é obrigatório.');
        }
        
        if (empty($usuarioId)) {
            throw new BadRequestHttpException('Parâmetro usuario_id é obrigatório.');
        }
        
        // Remove formatação do CPF
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpfLimpo) !== 11) {
            throw new BadRequestHttpException('CPF inválido. Deve conter 11 dígitos.');
        }
        
        // Busca cliente
        $cliente = Cliente::find()
            ->where([
                'cpf' => $cpfLimpo,
                'usuario_id' => $usuarioId,
                'ativo' => true
            ])
            ->one();
        
        if ($cliente) {
            // Cliente existe - retorna dados básicos
            return [
                'existe' => true,
                'cliente' => [
                    'id' => $cliente->id,
                    'nome_completo' => $cliente->nome_completo,
                    'cpf' => $cliente->cpf,
                    'telefone' => $cliente->telefone,
                    'email' => $cliente->email,
                ]
            ];
        } else {
            // Cliente não existe
            return [
                'existe' => false,
                'cpf' => $cpfLimpo
            ];
        }
    }

    /**
     * Login do cliente (autenticação para buscar dados completos)
     * POST /api/cliente/login
     * Body: {"cpf": "12345678900", "senha": "1234", "usuario_id": "xxx"}
     */
    public function actionLogin()
    {
        Yii::$app->request->enableCsrfValidation = false;
        
        $rawBody = Yii::$app->request->getRawBody();
        $data = json_decode($rawBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('JSON inválido: ' . json_last_error_msg());
        }
        
        if (!isset($data['cpf']) || !isset($data['senha']) || !isset($data['usuario_id'])) {
            throw new BadRequestHttpException('CPF, senha e usuario_id são obrigatórios.');
        }
        
        $cpfLimpo = preg_replace('/[^0-9]/', '', $data['cpf']);
        
        // Busca cliente
        $cliente = Cliente::find()
            ->where([
                'cpf' => $cpfLimpo,
                'usuario_id' => $data['usuario_id'],
                'ativo' => true
            ])
            ->one();
        
        if (!$cliente) {
            throw new NotFoundHttpException('Cliente não encontrado.');
        }
        
        // Valida senha
        if (!$cliente->validarSenha($data['senha'])) {
            throw new UnauthorizedHttpException('Senha incorreta.');
        }
        
        // Gera token JWT simples (você pode melhorar isso)
        $token = $this->gerarTokenJWT($cliente);
        
        // Retorna dados completos do cliente
        return [
            'token' => $token,
            'cliente' => [
                'id' => $cliente->id,
                'nome_completo' => $cliente->nome_completo,
                'cpf' => $cliente->cpf,
                'telefone' => $cliente->telefone,
                'email' => $cliente->email,
                'endereco_logradouro' => $cliente->endereco_logradouro,
                'endereco_numero' => $cliente->endereco_numero,
                'endereco_complemento' => $cliente->endereco_complemento,
                'endereco_bairro' => $cliente->endereco_bairro,
                'endereco_cidade' => $cliente->endereco_cidade,
                'endereco_estado' => $cliente->endereco_estado,
                'endereco_cep' => $cliente->endereco_cep,
            ]
        ];
    }

    /**
     * Cria novo cliente
     * POST /api/cliente
     */
    public function actionCreate()
    {
        Yii::$app->request->enableCsrfValidation = false;

        $rawBody = Yii::$app->request->getRawBody();
        Yii::error('Corpo Cru Recebido (Cliente): ' . $rawBody, 'api');
        $data = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::error('Falha ao decodificar JSON (Cliente): ' . json_last_error_msg(), 'api');
            throw new BadRequestHttpException('JSON inválido recebido: ' . json_last_error_msg());
        }
        
        if (!is_array($data)) {
            Yii::error('json_decode não retornou array. RawBody: '. $rawBody, 'api');
            throw new BadRequestHttpException('Dados recebidos em formato inesperado.');
        }
        
        Yii::error('Dados Decodificados ($data Cliente): ' . print_r($data, true), 'api');

        $cliente = new Cliente();

        if ($cliente->load($data, '')) {
            Yii::error('Dados carregados via load() (Cliente): ' . print_r($cliente->attributes, true), 'api');

            // Limpa CPF
            $cliente->cpf = isset($cliente->cpf) ? preg_replace('/[^0-9]/', '', $cliente->cpf) : null;
            $cliente->ativo = true;

            // Validação de usuario_id
            if (empty($cliente->usuario_id) || $cliente->usuario_id !== ($data['usuario_id'] ?? null)) {
                Yii::error('Discrepância/falta de usuario_id. Forçando. Recebido: ' . ($data['usuario_id'] ?? 'N/A'), 'api');
                $cliente->usuario_id = $data['usuario_id'] ?? null;
                
                if (empty($cliente->usuario_id)) {
                    $cliente->addError('usuario_id', '"Usuário" não pode ficar em branco.');
                    Yii::$app->response->statusCode = 422;
                    Yii::error("Erro: usuario_id vazio: " . print_r($cliente->errors, true), 'api');
                    return ['errors' => $cliente->errors];
                }
            }
            
            Yii::error('Atributos ANTES de save() (Cliente): ' . print_r($cliente->attributes, true), 'api');

            if ($cliente->save()) {
                Yii::$app->response->statusCode = 201;
                Yii::error("Cliente ID {$cliente->id} criado com sucesso.", 'api');
                return $cliente->toArray([
                    'id', 'nome_completo', 'cpf', 'telefone', 'email',
                    'endereco_logradouro', 'endereco_numero', 'endereco_complemento',
                    'endereco_bairro', 'endereco_cidade', 'endereco_estado', 'endereco_cep'
                ]);
            } else {
                Yii::$app->response->statusCode = 422;
                Yii::error("Erro de validação ao criar cliente: " . print_r($cliente->errors, true), 'api');
                return ['errors' => $cliente->errors];
            }
        } else {
            Yii::error('Falha em $cliente->load(). Dados: ' . print_r($data, true), 'api');
            throw new BadRequestHttpException('Não foi possível carregar os dados do cliente.');
        }
    }

    /**
     * Gera token JWT simples (MELHORE ISSO EM PRODUÇÃO!)
     */
    private function gerarTokenJWT($cliente)
    {
        // Token simples baseado em hash
        // EM PRODUÇÃO, use uma biblioteca JWT adequada!
        $payload = [
            'cliente_id' => $cliente->id,
            'cpf' => $cliente->cpf,
            'exp' => time() + (60 * 60 * 24) // 24 horas
        ];
        
        return base64_encode(json_encode($payload));
    }
}