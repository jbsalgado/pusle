<?php
/**
 * DashboardController - Página inicial do prestanista
 * Localização: app/modules/vendas/controllers/DashboardController.php
 */

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;

/**
 * DashboardController - Painel principal do prestanista
 */
class DashboardController extends Controller
{
    public $layout = 'main'; // Layout da área logada

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
                        'roles' => ['@'], // Apenas usuários autenticados
                    ],
                ],
            ],
        ];
    }

    /**
     * Página inicial - Dashboard
     */
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;

        // Busca o colaborador associado ao usuário (se houver)
        $colaborador = null;
        $ehAdministrador = false;
        if ($usuario) {
            $colaborador = \app\modules\vendas\models\Colaborador::find()
                ->where(['usuario_id' => $usuario->id])
                ->andWhere(['ativo' => true])
                ->one();
            
            if ($colaborador) {
                $ehAdministrador = (bool)$colaborador->eh_administrador;
            }
        }

        // Estatísticas gerais (apenas para administradores)
        $stats = [];
        $totalVendasMes = 0;
        $quantidadeVendasMes = 0;
        $clientesRecentes = [];
        $produtosMaisVendidos = [];
        $vendasRecentes = [];

        if ($ehAdministrador) {
            $stats = [
                'total_clientes' => Cliente::find()->where(['usuario_id' => $usuario->id])->count(),
                'total_produtos' => Produto::find()->where(['usuario_id' => $usuario->id])->count(),
                'total_vendas' => Venda::find()->where(['usuario_id' => $usuario->id])->count(),
                'total_categorias' => Categoria::find()->where(['usuario_id' => $usuario->id])->count(),
            ];

            // Vendas do mês atual - CORRIGIDO para PostgreSQL
            $primeiroDiaMes = date('Y-m-01 00:00:00');
            $ultimoDiaMes = date('Y-m-t 23:59:59');
            
            $vendasMes = Venda::find()
                ->where(['usuario_id' => $usuario->id])
                ->andWhere(['>=', 'data_venda', $primeiroDiaMes])
                ->andWhere(['<=', 'data_venda', $ultimoDiaMes])
                ->all();

            $quantidadeVendasMes = count($vendasMes);
            
            foreach ($vendasMes as $venda) {
                $totalVendasMes += $venda->valor_total;
            }

            // Clientes recentes (últimos 5)
            $clientesRecentes = Cliente::find()
                ->where(['usuario_id' => $usuario->id])
                ->orderBy(['data_criacao' => SORT_DESC])
                ->limit(5)
                ->all();

            // Produtos mais vendidos (top 5)
            $produtosMaisVendidos = $this->getProdutosMaisVendidos($usuario->id, 5);

            // Vendas recentes (últimas 10)
            $vendasRecentes = Venda::find()
                ->where(['usuario_id' => $usuario->id])
                ->orderBy(['data_venda' => SORT_DESC])
                ->limit(10)
                ->all();
        }

        return $this->render('index', [
            'usuario' => $usuario,
            'colaborador' => $colaborador,
            'ehAdministrador' => $ehAdministrador,
            'stats' => $stats,
            'totalVendasMes' => $totalVendasMes,
            'quantidadeVendasMes' => $quantidadeVendasMes,
            'clientesRecentes' => $clientesRecentes,
            'produtosMaisVendidos' => $produtosMaisVendidos,
            'vendasRecentes' => $vendasRecentes,
        ]);
    }

    /**
     * Busca produtos mais vendidos
     * * @param string $usuarioId
     * @param int $limit
     * @return array
     */
    protected function getProdutosMaisVendidos($usuarioId, $limit = 5)
    {
        try {
            // Query SQL para buscar produtos mais vendidos
            // ✅ AJUSTE: Adicionada a coluna p.preco_venda_sugerido
            $sql = "
                SELECT 
                    p.id,
                    p.nome,
                    p.preco_venda_sugerido, -- ✅ CAMPO ADICIONADO
                    COUNT(vi.id) as total_vendas,
                    SUM(vi.quantidade) as quantidade_total
                FROM prest_produtos p
                INNER JOIN prest_venda_itens vi ON vi.produto_id = p.id
                INNER JOIN prest_vendas v ON v.id = vi.venda_id
                WHERE p.usuario_id = :usuario_id
                GROUP BY p.id, p.nome, p.preco_venda_sugerido -- ✅ CAMPO ADICIONADO
                ORDER BY quantidade_total DESC
                LIMIT :limit
            ";

            return Yii::$app->db->createCommand($sql)
                ->bindValue(':usuario_id', $usuarioId)
                ->bindValue(':limit', $limit)
                ->queryAll();
        } catch (\Exception $e) {
            Yii::error("Erro ao buscar produtos mais vendidos: {$e->getMessage()}", __METHOD__);
            return [];
        }
    }
}