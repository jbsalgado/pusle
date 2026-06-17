<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\FormaPagamento;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class ClienteController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'optional' => ['index', 'create', 'buscar-cpf', 'login', 'view', 'dados-cobranca'],
        ];
        
        // Adicionar CORS para desenvolvimento
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost', 'http://localhost:*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'view' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'buscar-cpf' => ['GET'],
            'login' => ['POST'],
            'dados-cobranca' => ['GET'],
        ];
    }

    /**
     * Busca clientes por termo (nome ou CPF)
     * GET /api/cliente?termo=xxx&usuario_id=yyy
     */
    public function actionIndex()
    {
        try {
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
            
        } catch (\Exception $e) {
            Yii::error('Erro em actionIndex: ' . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao buscar clientes: ' . $e->getMessage());
        }
    }

    /**
     * Retorna dados de um cliente específico por ID
     * GET /api/cliente/<id>
     * 
     * CORREÇÃO APLICADA: Melhor tratamento de erros e validação
     */
    public function actionView($id)
    {
        try {
            // Validar UUID
            if (!$this->isValidUuid($id)) {
                Yii::error("ID inválido recebido: $id", 'api');
                throw new BadRequestHttpException('ID do cliente inválido.');
            }
            
            // Log para debug
            Yii::info("Buscando cliente com ID: $id", 'api');
            
            // Verificar conexão com banco
            try {
                $connection = Yii::$app->db;
                $connection->open();
                Yii::info("Conexão com banco OK", 'api');
            } catch (\Exception $dbError) {
                Yii::error("Erro de conexão com banco: " . $dbError->getMessage(), 'api');
                throw new ServerErrorHttpException('Erro de conexão com o banco de dados.');
            }
            
            // Buscar cliente
            $cliente = Cliente::find()
                ->where(['id' => $id, 'ativo' => true])
                ->one();
            
            if (!$cliente) {
                Yii::warning("Cliente não encontrado: $id", 'api');
                throw new NotFoundHttpException('Cliente não encontrado.');
            }
            
            // Log dos dados do cliente para debug
            Yii::info("Cliente encontrado: " . $cliente->nome_completo, 'api');
            
            // Retornar dados com todos os campos necessários
            $dadosCliente = [
                'id' => $cliente->id,
                'usuario_id' => $cliente->usuario_id,
                'nome' => $cliente->nome_completo,
                'nome_completo' => $cliente->nome_completo,
                'cpf' => $cliente->cpf,
                'cpf_cnpj' => $cliente->cpf,
                'telefone' => $cliente->telefone ?? '',
                'email' => $cliente->email ?? '',
                
                // Campos de endereço com aliases
                'logradouro' => $cliente->endereco_logradouro ?? '',
                'endereco_logradouro' => $cliente->endereco_logradouro ?? '',
                'numero' => $cliente->endereco_numero ?? '',
                'endereco_numero' => $cliente->endereco_numero ?? '',
                'complemento' => $cliente->endereco_complemento ?? '',
                'endereco_complemento' => $cliente->endereco_complemento ?? '',
                'bairro' => $cliente->endereco_bairro ?? '',
                'endereco_bairro' => $cliente->endereco_bairro ?? '',
                'cidade' => $cliente->endereco_cidade ?? '',
                'endereco_cidade' => $cliente->endereco_cidade ?? '',
                'estado' => $cliente->endereco_estado ?? '',
                'endereco_estado' => $cliente->endereco_estado ?? '',
                'cep' => $cliente->endereco_cep ?? '',
                'endereco_cep' => $cliente->endereco_cep ?? '',
            ];
            
            Yii::info("Retornando dados do cliente", 'api');
            return $dadosCliente;
            
        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (ServerErrorHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Erro não tratado em actionView: ' . $e->getMessage() . "\nStack: " . $e->getTraceAsString(), 'api');
            throw new ServerErrorHttpException('Erro interno ao buscar dados do cliente: ' . $e->getMessage());
        }
    }

    /**
     * Busca cliente por CPF (para verificar se existe)
     * GET /api/cliente/buscar-cpf?cpf=12345678900&usuario_id=xxx
     */
    public function actionBuscarCpf()
    {
        try {
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
                        'telefone' => $cliente->telefone ?? '',
                        'email' => $cliente->email ?? '',
                    ]
                ];
            } else {
                // Cliente não existe
                return [
                    'existe' => false,
                    'cpf' => $cpfLimpo
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('Erro em actionBuscarCpf: ' . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao buscar cliente por CPF.');
        }
    }

    /**
     * Login do cliente (autenticação para buscar dados completos)
     * POST /api/cliente/login
     */
    public function actionLogin()
    {
        try {
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
            
            // Gera token JWT simples
            $token = $this->gerarTokenJWT($cliente);
            
            // Retorna dados completos do cliente
            return [
                'token' => $token,
                'cliente' => [
                    'id' => $cliente->id,
                    'usuario_id' => $cliente->usuario_id, 
                    'nome_completo' => $cliente->nome_completo,
                    'cpf' => $cliente->cpf,
                    'telefone' => $cliente->telefone ?? '',
                    'email' => $cliente->email ?? '',
                    'endereco_logradouro' => $cliente->endereco_logradouro ?? '',
                    'endereco_numero' => $cliente->endereco_numero ?? '',
                    'endereco_complemento' => $cliente->endereco_complemento ?? '',
                    'endereco_bairro' => $cliente->endereco_bairro ?? '',
                    'endereco_cidade' => $cliente->endereco_cidade ?? '',
                    'endereco_estado' => $cliente->endereco_estado ?? '',
                    'endereco_cep' => $cliente->endereco_cep ?? '',
                ]
            ];
            
        } catch (\Exception $e) {
            Yii::error('Erro em actionLogin: ' . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao fazer login.');
        }
    }

    /**
     * Cria novo cliente
     * POST /api/cliente
     */
    public function actionCreate()
    {
        try {
            Yii::$app->request->enableCsrfValidation = false;

            $rawBody = Yii::$app->request->getRawBody();
            Yii::info('Corpo Cru Recebido (Cliente): ' . $rawBody, 'api');
            $data = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Yii::error('Falha ao decodificar JSON (Cliente): ' . json_last_error_msg(), 'api');
                throw new BadRequestHttpException('JSON inválido recebido: ' . json_last_error_msg());
            }
            
            if (!is_array($data)) {
                Yii::error('json_decode não retornou array. RawBody: '. $rawBody, 'api');
                throw new BadRequestHttpException('Dados recebidos em formato inesperado.');
            }
            
            Yii::info('Dados Decodificados ($data Cliente): ' . print_r($data, true), 'api');

            $cliente = new Cliente();

            if ($cliente->load($data, '')) {
                Yii::info('Dados carregados via load() (Cliente): ' . print_r($cliente->attributes, true), 'api');

                // Limpa CPF
                $cliente->cpf = isset($cliente->cpf) ? preg_replace('/[^0-9]/', '', $cliente->cpf) : null;
                $cliente->ativo = true;

                // Validação de usuario_id
                if (empty($cliente->usuario_id)) {
                    $cliente->usuario_id = $data['usuario_id'] ?? null;
                }
                
                if (empty($cliente->usuario_id)) {
                    $cliente->addError('usuario_id', 'Usuário não pode ficar em branco.');
                    Yii::$app->response->statusCode = 422;
                    Yii::error("Erro: usuario_id vazio: " . print_r($cliente->errors, true), 'api');
                    return ['errors' => $cliente->errors];
                }
                
                Yii::info('Atributos ANTES de save() (Cliente): ' . print_r($cliente->attributes, true), 'api');

                if ($cliente->save()) {
                    Yii::$app->response->statusCode = 201;
                    Yii::info("Cliente ID {$cliente->id} criado com sucesso.", 'api');
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
            
        } catch (\Exception $e) {
            Yii::error('Erro em actionCreate: ' . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao criar cliente.');
        }
    }

    /**
     * Valida se uma string é um UUID válido
     */
    private function isValidUuid($uuid)
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
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

    /**
     * Retorna dados de cobrança do cliente (parcelas pendentes e pagas)
     * GET /api/cliente/dados-cobranca?cliente_id=xxx&usuario_id=yyy
     */
    public function actionDadosCobranca()
    {
        try {
            $clienteId = Yii::$app->request->get('cliente_id');
            $usuarioId = Yii::$app->request->get('usuario_id');
            
            if (empty($clienteId)) {
                throw new BadRequestHttpException('Parâmetro cliente_id é obrigatório.');
            }
            
            if (empty($usuarioId)) {
                throw new BadRequestHttpException('Parâmetro usuario_id é obrigatório.');
            }
            
            // Verifica se o cliente existe e pertence ao usuário
            $cliente = Cliente::find()
                ->where(['id' => $clienteId, 'usuario_id' => $usuarioId, 'ativo' => true])
                ->one();
            
            if (!$cliente) {
                throw new NotFoundHttpException('Cliente não encontrado.');
            }
            
            // Busca todas as vendas parceladas do cliente
            $vendas = Venda::find()
                ->where(['cliente_id' => $clienteId, 'usuario_id' => $usuarioId])
                ->with(['parcelas', 'formaPagamento'])
                ->all();
            
            $totalParcelas = 0;
            $parcelasPagas = 0;
            $valorTotal = 0;
            $valorRecebido = 0;
            
            foreach ($vendas as $venda) {
                // Filtra apenas vendas parceladas (com mais de 1 parcela)
                if ($venda->numero_parcelas <= 1) {
                    continue;
                }
                
                // Filtra vendas que podem ser cobradas manualmente
                // Exclui cartão de crédito/débito
                $formaPagamento = $venda->formaPagamento;
                if ($formaPagamento) {
                    $tipoPagamento = strtoupper($formaPagamento->tipo ?? '');
                    if (in_array($tipoPagamento, ['CARTAO_CREDITO', 'CARTAO_DEBITO', 'CARTAO'])) {
                        continue; // Pula vendas de cartão
                    }
                }
                
                // Busca todas as parcelas da venda
                $parcelas = Parcela::find()
                    ->where(['venda_id' => $venda->id])
                    ->all();
                
                if (count($parcelas) === 0) {
                    continue; // Pula vendas sem parcelas
                }
                
                // Soma valores
                foreach ($parcelas as $parcela) {
                    $totalParcelas++;
                    $valorTotal += (float)$parcela->valor_parcela;
                    
                    if ($parcela->status_parcela_codigo === 'PAGA') {
                        $parcelasPagas++;
                        $valorRecebido += (float)($parcela->valor_pago ?? $parcela->valor_parcela);
                    }
                }
            }
            
            return [
                'total_parcelas' => $totalParcelas,
                'parcelas_pagas' => $parcelasPagas,
                'valor_total' => round($valorTotal, 2),
                'valor_recebido' => round($valorRecebido, 2),
            ];
            
        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Erro em actionDadosCobranca: ' . $e->getMessage(), 'api');
            throw new ServerErrorHttpException('Erro ao buscar dados de cobrança: ' . $e->getMessage());
        }
    }
}