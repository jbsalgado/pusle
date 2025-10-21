<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\filters\auth\HttpBearerAuth;
use app\modules\vendas\models\Colaborador; // Certifique-se que o namespace está correto

/**
 * API Controller para Colaboradores
 */
class ColaboradorController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        // Autenticação é opcional para a busca por CPF (pode ajustar se necessário)
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => ['buscar-cpf'], 
        ];
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'buscar-cpf' => ['GET'], // Define que buscar-cpf usa o método GET
            // Adicione outras ações e verbos aqui se necessário
        ];
    }

    /**
     * Busca um colaborador vendedor ativo pelo CPF e ID do usuário (loja).
     * GET /api/colaborador/buscar-cpf
     * * @param string $cpf O CPF do colaborador (somente números)
     * @param string $usuario_id O ID do usuário (loja)
     * @return array ['existe' => bool, 'colaborador' => ['id' => string, 'nome_completo' => string] | null]
     */
    public function actionBuscarCpf($cpf = null, $usuario_id = null)
    {
        if ($cpf === null || $usuario_id === null) {
            throw new BadRequestHttpException('Parâmetros cpf e usuario_id são obrigatórios.');
        }

        // Limpa o CPF
        $cpfLimpo = preg_replace('/[^\d]/', '', $cpf);
        if (strlen($cpfLimpo) !== 11) {
             throw new BadRequestHttpException('CPF inválido.');
        }

        // Busca no banco usando o modelo Colaborador
        $colaborador = Colaborador::findOne([
            'cpf' => $cpfLimpo,
            'usuario_id' => $usuario_id,
            'ativo' => true,      // Somente colaboradores ativos
            'eh_vendedor' => true // Garante que é um vendedor
        ]);

        if ($colaborador) {
            // Se encontrado, retorna que existe e os dados básicos
            return [
                'existe' => true,
                'colaborador' => [
                    'id' => $colaborador->id,
                    'nome_completo' => $colaborador->nome_completo,
                ]
            ];
        } else {
            // Se não encontrado, retorna que não existe
            return ['existe' => false];
        }
    }

    // Outras ações da API para Colaborador podem ser adicionadas aqui (ex: list, view, create, update, delete)
}