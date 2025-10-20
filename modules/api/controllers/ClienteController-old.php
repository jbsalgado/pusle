<?php
// Namespace correto
namespace app\modules\api\controllers;

// Use statements corretos
use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\modules\vendas\models\Cliente;
use yii\web\BadRequestHttpException;
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
            'optional' => ['index', 'create'],
        ];
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'create' => ['POST'],
        ];
    }

    public function actionIndex()
    {
        // ... (código actionIndex sem mudanças) ...
        $termo = Yii::$app->request->get('termo');
        $usuarioId = Yii::$app->request->get('usuario_id');
        if (empty($usuarioId)) { throw new BadRequestHttpException('...'); }
        if (empty($termo)) { return []; }
        $cpfLimpo = preg_replace('/[^0-9]/', '', $termo);
        $query = Cliente::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->andWhere(['or', ['ilike', 'nome_completo', $termo], ['=', 'cpf', $cpfLimpo]])
            ->select(['id', 'nome_completo', 'cpf'])
            ->limit(10);
        return $query->asArray()->all();
    }

    /**
     * Cria um novo cliente via API.
     * POST /api/cliente
     */
    public function actionCreate()
    {
        Yii::$app->request->enableCsrfValidation = false;

        // ✅ LER CORPO CRU E DECODIFICAR MANUALMENTE
        $rawBody = Yii::$app->request->getRawBody();
        Yii::error('Corpo Cru Recebido (Cliente): ' . $rawBody, 'api');
        $data = json_decode($rawBody, true); // true para array associativo

        // Verifica se o JSON foi decodificado corretamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::error('Falha ao decodificar JSON manualmente (Cliente): ' . json_last_error_msg(), 'api');
            throw new BadRequestHttpException('JSON inválido recebido: ' . json_last_error_msg());
        }
        // Verifica se $data é um array (após decode bem sucedido, deve ser)
        if (!is_array($data)) {
             Yii::error('json_decode não retornou um array. RawBody: '. $rawBody, 'api');
             throw new BadRequestHttpException('Dados recebidos em formato inesperado.');
        }
        Yii::error('Dados Decodificados Manualmente ($data Cliente): ' . print_r($data, true), 'api');


        $cliente = new Cliente();

        // Usa $cliente->load() com os dados decodificados manualmente
        if ($cliente->load($data, '')) {
            Yii::error('Dados carregados via load() (Cliente): ' . print_r($cliente->attributes, true), 'api');

             $cliente->cpf = isset($cliente->cpf) ? preg_replace('/[^0-9]/', '', $cliente->cpf) : null;
             $cliente->ativo = true;

             // VALIDAÇÃO/ATRIBUIÇÃO DE usuario_id (INSEGURO SEM AUTH)
             if (empty($cliente->usuario_id) || $cliente->usuario_id !== ($data['usuario_id'] ?? null)) {
                  Yii::error('Discrepância/falta de usuario_id (Cliente). Forçando (TESTE). Recebido: ' . ($data['usuario_id'] ?? 'N/A'), 'api');
                  $cliente->usuario_id = $data['usuario_id'] ?? null;
                   if (empty($cliente->usuario_id)) {
                         // Adiciona erro específico se usuario_id ainda estiver vazio
                         $cliente->addError('usuario_id', '"Usuário" não pode ficar em branco (verificação pós-load).');
                         Yii::$app->response->statusCode = 422;
                         Yii::error("Erro de validação (usuario_id vazio pós-load): " . print_r($cliente->errors, true), 'api');
                         return ['errors' => $cliente->errors];
                   }
             }
             Yii::error('Atributos ANTES de save() (Cliente): ' . print_r($cliente->attributes, true), 'api');


            if ($cliente->save()) {
                Yii::$app->response->statusCode = 201;
                Yii::error("Cliente ID {$cliente->id} criado com sucesso.", 'api');
                return $cliente->toArray(['id', 'nome_completo', 'cpf', 'telefone', 'email']);
            } else {
                Yii::$app->response->statusCode = 422;
                Yii::error("Erro de validação ao criar cliente: " . print_r($cliente->errors, true), 'api');
                return ['errors' => $cliente->errors];
            }
        } else {
             Yii::error('Falha em $cliente->load($data, \'\') (Cliente). Dados usados (decode manual): ' . print_r($data, true), 'api');
            throw new BadRequestHttpException('Não foi possível carregar os dados do cliente (load falhou).');
        }
    }
}