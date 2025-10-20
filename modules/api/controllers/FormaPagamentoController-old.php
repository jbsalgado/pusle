<?php
namespace app\modules\api\controllers;

use yii\rest\Controller;
use yii\web\Response;
use app\modules\vendas\models\FormaPagamento;
use app\models\Usuario; // Importe o modelo Usuario

class FormaPagamentoController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
        ];
    }

    /**
     * Lista formas de pagamento ativas para um usuário (simulado).
     * GET /api/forma-pagamento  (ou /api/formas-pagamento se pluralize=true)
     */
    public function actionIndex()
    {
        // Em um app real, você pegaria o ID do usuário logado ou associado à PWA
        // Aqui, vamos simular pegando as formas do primeiro usuário encontrado
        $usuario = Usuario::find()->one();
        if (!$usuario) {
             throw new \yii\web\NotFoundHttpException("Nenhum usuário encontrado para buscar formas de pagamento.");
        }

        // Usa o método estático do modelo que você já tem
        $formas = FormaPagamento::getListaDropdown($usuario->id);

        // Formata para [{id: 'uuid', nome: 'Dinheiro'}, ...] para facilitar no JS
        $resultado = [];
        foreach ($formas as $id => $nome) {
            $resultado[] = ['id' => $id, 'nome' => $nome];
        }
        return $resultado;
    }
}