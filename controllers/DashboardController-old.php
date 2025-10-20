<?php
/**
 * DashboardController - Dashboard Central do Sistema
 * Localização: app/controllers/DashboardController.php
 * 
 * Mostra os módulos disponíveis para o usuário logado
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\Modulo;
use app\models\Assinatura;
use app\models\SisAssinatura;

/**
 * DashboardController - Seleção de módulos
 */
class DashboardController extends Controller
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Dashboard central - Mostra módulos disponíveis
     */
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;

        // Busca módulos disponíveis para o usuário
        $modulosDisponiveis = $this->getModulosDisponiveis($usuario->id);

        // Busca assinatura ativa
        $assinatura = SisAssinatura::getAssinaturaAtiva($usuario->id);

        // Se não tiver assinatura, cria um trial automático
        if (!$assinatura) {
            $assinatura = SisAssinatura::criarTrial($usuario->id);
            
            if ($assinatura) {
                Yii::$app->session->setFlash('info', 'Você ganhou 7 dias grátis! Aproveite para conhecer todos os módulos.');
                // Recarrega os módulos disponíveis
                $modulosDisponiveis = $this->getModulosDisponiveis($usuario->id);
            }
        }

        return $this->render('index', [
            'usuario' => $usuario,
            'modulosDisponiveis' => $modulosDisponiveis,
            'assinatura' => $assinatura,
        ]);
    }

    /**
     * Busca módulos disponíveis para o usuário
     * 
     * @param string $usuarioId
     * @return array
     */
    protected function getModulosDisponiveis($usuarioId)
    {
        // Usando a view do banco de dados
        $sql = "
            SELECT DISTINCT
                modulo_id,
                modulo_codigo,
                modulo_nome,
                modulo_descricao,
                icone,
                cor,
                rota,
                tipo_acesso,
                data_expiracao
            FROM vw_usuario_modulos_disponiveis
            WHERE usuario_id = :usuario_id
            ORDER BY modulo_nome
        ";

        return Yii::$app->db->createCommand($sql)
            ->bindValue(':usuario_id', $usuarioId)
            ->queryAll();
    }

    /**
     * Verifica se usuário tem acesso a um módulo específico
     * 
     * @param string $usuarioId
     * @param string $moduloCodigo
     * @return boolean
     */
    public static function verificarAcessoModulo($usuarioId, $moduloCodigo)
    {
        $result = Yii::$app->db->createCommand(
            'SELECT verificar_acesso_modulo(:usuario_id, :modulo_codigo) as tem_acesso'
        )
        ->bindValue(':usuario_id', $usuarioId)
        ->bindValue(':modulo_codigo', $moduloCodigo)
        ->queryOne();

        return $result['tem_acesso'] ?? false;
    }
}