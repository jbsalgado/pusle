<?php

namespace app\modules\api\controllers;

use Yii;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;

class AuthController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionLogin()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            throw new BadRequestHttpException('Apenas requisições POST são permitidas.');
        }

        $data = json_decode($request->getRawBody(), true);
        $login = $data['username'] ?? $data['login'] ?? null;
        $password = $data['password'] ?? $data['senha'] ?? null;

        if (!$login || !$password) {
            throw new BadRequestHttpException('Login e senha são obrigatórios.');
        }

        $usuario = Usuario::findByLogin($login);

        if (!$usuario || !$usuario->validatePassword($password)) {
            throw new UnauthorizedHttpException('Credenciais inválidas.');
        }

        if ($usuario->isBlocked()) {
            throw new UnauthorizedHttpException('Usuário bloqueado.');
        }

        // Gera o Token JWT
        $token = $usuario->generateJwt();

        // Dados complementares do colaborador (se houver)
        $colaborador = Colaborador::find()
            ->where(['usuario_id' => $usuario->id])
            ->andWhere(['eh_vendedor' => true])
            ->andWhere(['ativo' => true])
            ->one();

        return $this->success([
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'username' => $usuario->username,
                'email' => $usuario->email,
            ],
            'colaborador' => $colaborador ? [
                'id' => $colaborador->id,
                'nome_completo' => $colaborador->nome_completo,
                'eh_vendedor' => (bool)$colaborador->eh_vendedor,
            ] : null
        ], 'Login realizado com sucesso.');
    }
}
