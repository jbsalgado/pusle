<?php
namespace app\modules\api\controllers;

use Yii; // Adicionar Yii se não estiver
use yii\rest\Controller;
use yii\web\Response;
use app\modules\vendas\models\FormaPagamento;
use yii\web\BadRequestHttpException;

// Removido: use app\models\Usuario; (Não vamos mais buscar o usuário aqui por enquanto)

class FormaPagamentoController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        // Adicionar text/html para forçar JSON em alguns casos
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
        ];
    }

    /**
     * Lista formas de pagamento ativas para o usuário da loja (fixo por enquanto).
     * GET /api/forma-pagamento (ou /api/formas-pagamento se pluralize=true)
     */
    public function actionIndex()
    {
        // ✅ 1. Obter o usuario_id do parâmetro da URL
        $usuarioId = Yii::$app->request->get('usuario_id');

        // ✅ 2. Validar se o parâmetro foi fornecido
        if (empty($usuarioId)) {
             throw new BadRequestHttpException('O parâmetro "usuario_id" é obrigatório.');
        }
        // TODO: Adicionar validação se $usuarioId é um UUID válido e/ou se existe na tabela usuarios, se necessário.

        // ✅ 3. Usar o usuario_id recebido para buscar as formas de pagamento
        $formas = FormaPagamento::getListaDropdown($usuarioId);

        // Verificação se o retorno é um array
        if (!is_array($formas)) {
             Yii::error("FormaPagamento::getListaDropdown não retornou um array para o usuário ID: " . $usuarioId, 'api');
             $formas = [];
        }

        // Formata o resultado
        $resultado = [];
        foreach ($formas as $id => $nome) {
            if (!empty($id) && !empty($nome)) {
                $resultado[] = ['id' => $id, 'nome' => $nome];
            }
        }

        return $resultado;
    }
}