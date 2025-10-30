<?php
/**
 * DashboardController - Dashboard Global do Sistema
 * Localização: app/controllers/DashboardController.php
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

class DashboardController extends Controller
{
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

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Dashboard principal
     */
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        
        // Lista de módulos disponíveis
        $modulos = [
            [
                'codigo' => 'vendas',
                'nome' => 'Vendas',
                'descricao' => 'Sistema de gestão de vendas para prestamistas',
                'icone' => '🛒',
                'cor' => '#667eea',
                'url' => ['/vendas/inicio/index'],
            ],
            [
                'codigo' => 'porrinha',
                'nome' => 'Porrinha',
                'descricao' => 'Gestão de jogos e apostas',
                'icone' => '🎲',
                'cor' => '#28a745',
                'url' => ['/porrinha/default/index'],
            ],
            [
                'codigo' => 'metricas',
                'nome' => 'Métricas',
                'descricao' => 'Indicadores e análises',
                'icone' => '📊',
                'cor' => '#ffc107',
                'url' => ['/metricas/default/index'],
            ],
            [
                'codigo' => 'saas',
                'nome' => 'SaaS',
                'descricao' => 'Serviços online',
                'icone' => '☁️',
                'cor' => '#17a2b8',
                'url' => ['/saas/default/index'],
            ],
        ];
        
        // Verifica acesso a cada módulo
        foreach ($modulos as &$modulo) {
            $modulo['tem_acesso'] = self::verificarAcessoModulo($usuario->id, $modulo['codigo']);
        }
        
        return $this->render('index', [
            'usuario' => $usuario,
            'modulos' => $modulos,
        ]);
    }

    /**
     * Verifica se usuário tem acesso ao módulo
     * 
     * @param string $usuarioId UUID do usuário
     * @param string $moduloCodigo Código do módulo (vendas, porrinha, etc)
     * @return bool
     */
    public static function verificarAcessoModulo($usuarioId, $moduloCodigo)
    {
        // ============================================================
        // TODO: IMPLEMENTAR VERIFICAÇÃO DE ASSINATURA/PLANOS
        // ============================================================
        // Por enquanto, LIBERA ACESSO para todos (modo desenvolvimento)
        // 
        // Implementação futura:
        // 1. Buscar assinatura ativa do usuário
        // 2. Verificar se o plano da assinatura inclui o módulo
        // 3. Verificar se não está expirado
        // 4. Retornar true/false
        // ============================================================
        
        return true;
        
        /* 
        EXEMPLO DE IMPLEMENTAÇÃO FUTURA:
        
        use app\models\UsuarioAssinatura;
        use app\models\Plano;
        use app\models\PlanoModulo;
        
        // Busca assinatura ativa do usuário
        $assinatura = UsuarioAssinatura::find()
            ->where([
                'usuario_id' => $usuarioId,
                'ativo' => true,
            ])
            ->andWhere(['>', 'data_expiracao', date('Y-m-d')])
            ->one();
        
        if (!$assinatura) {
            return false; // Sem assinatura ativa
        }
        
        // Verifica se o plano da assinatura tem o módulo
        $temModulo = PlanoModulo::find()
            ->where([
                'plano_id' => $assinatura->plano_id,
                'modulo_codigo' => $moduloCodigo,
                'ativo' => true,
            ])
            ->exists();
        
        return $temModulo;
        */
    }
}