<?php
/**
 * DashboardController - Página inicial do prestanista
 * Localização: app/modules/vendas/controllers/DashboardController.php
 */

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\db\Expression;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\FormaPagamento;

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

        // Verifica se é dono da loja (acesso completo automático)
        $ehDonoLoja = $usuario && $usuario->eh_dono_loja === true;
        
        // Busca o colaborador associado ao usuário (se houver)
        $colaborador = null;
        $ehAdministrador = false;
        
        if ($usuario) {
            // Se é dono da loja, tem acesso completo
            if ($ehDonoLoja) {
                $ehAdministrador = true;
            } else {
                // Se não é dono, verifica se é colaborador administrador
                // Usa o método helper do modelo Colaborador que suporta ambos os cenários
                $colaborador = \app\modules\vendas\models\Colaborador::getColaboradorLogado();
            
            if ($colaborador) {
                $ehAdministrador = (bool)$colaborador->eh_administrador;
                }
            }
        }

        // Estatísticas gerais (apenas para administradores)
        $stats = [];
        $kpis = [];
        $graficos = [];
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

            // KPIs detalhados
            $kpis = $this->calcularKPIs($usuario->id);

            // Dados para gráficos
            $graficos = [
                'vendas_por_dia' => $this->getVendasPorDia($usuario->id, 30),
                'vendas_por_mes' => $this->getVendasPorMes($usuario->id, 12),
                'vendas_por_forma_pagamento' => $this->getVendasPorFormaPagamento($usuario->id),
                'produtos_mais_vendidos' => $this->getProdutosMaisVendidos($usuario->id, 10),
                'vendas_parceladas_vs_vista' => $this->getVendasParceladasVsVista($usuario->id),
            ];

            // Rank de vendedores
            $rankVendedores = $this->getRankVendedores($usuario->id);

            // Produtos com estoque baixo (próximo do mínimo)
            $resultadoEstoque = $this->getProdutosEstoqueBaixo($usuario->id);
            $produtosEstoqueBaixo = $resultadoEstoque['produtos'];
            $limiteEstoque = $resultadoEstoque['limite'];

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
            'kpis' => $kpis,
            'graficos' => $graficos,
            'rankVendedores' => $rankVendedores ?? [],
            'produtosEstoqueBaixo' => $produtosEstoqueBaixo ?? [],
            'limiteEstoque' => $limiteEstoque ?? 10,
            'clientesRecentes' => $clientesRecentes,
            'produtosMaisVendidos' => $produtosMaisVendidos,
            'vendasRecentes' => $vendasRecentes,
        ]);
    }

    /**
     * Calcula KPIs importantes
     */
    protected function calcularKPIs($usuarioId)
    {
        $hoje = date('Y-m-d');
        $primeiroDiaSemana = date('Y-m-d', strtotime('monday this week'));
        $ultimoDiaSemana = date('Y-m-d', strtotime('sunday this week'));
        $primeiroDiaMes = date('Y-m-01');
        $ultimoDiaMes = date('Y-m-t');

        // Receita hoje
        $receitaHoje = Venda::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'data_venda', $hoje . ' 00:00:00'])
            ->andWhere(['<=', 'data_venda', $hoje . ' 23:59:59'])
            ->sum('valor_total') ?: 0;

        // Receita semana
        $receitaSemana = Venda::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'data_venda', $primeiroDiaSemana . ' 00:00:00'])
            ->andWhere(['<=', 'data_venda', $ultimoDiaSemana . ' 23:59:59'])
            ->sum('valor_total') ?: 0;

        // Receita mês
        $receitaMes = Venda::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'data_venda', $primeiroDiaMes . ' 00:00:00'])
            ->andWhere(['<=', 'data_venda', $ultimoDiaMes . ' 23:59:59'])
            ->sum('valor_total') ?: 0;

        // Quantidade vendas mês
        $qtdVendasMes = Venda::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'data_venda', $primeiroDiaMes . ' 00:00:00'])
            ->andWhere(['<=', 'data_venda', $ultimoDiaMes . ' 23:59:59'])
            ->count();

        // Ticket médio
        $ticketMedio = $qtdVendasMes > 0 ? ($receitaMes / $qtdVendasMes) : 0;

        // Parcelas pendentes
        $parcelasPendentes = Parcela::find()
            ->alias('p')
            ->innerJoin('prest_vendas v', 'v.id = p.venda_id')
            ->where(['v.usuario_id' => $usuarioId])
            ->andWhere(['p.status_parcela_codigo' => StatusParcela::PENDENTE])
            ->count();

        // Valor total pendente (parcelas)
        $valorPendente = Parcela::find()
            ->alias('p')
            ->innerJoin('prest_vendas v', 'v.id = p.venda_id')
            ->where(['v.usuario_id' => $usuarioId])
            ->andWhere(['p.status_parcela_codigo' => StatusParcela::PENDENTE])
            ->sum('p.valor_parcela') ?: 0;

        // Produtos com estoque baixo (menor que 10)
        $produtosEstoqueBaixo = Produto::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['ativo' => true])
            ->andWhere(['<', 'estoque_atual', 10])
            ->count();

        // Vendas parceladas vs à vista
        $vendasParceladas = Venda::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['>', 'numero_parcelas', 1])
            ->count();

        $vendasAVista = Venda::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['numero_parcelas' => 1])
            ->count();

        return [
            'receita_hoje' => $receitaHoje,
            'receita_semana' => $receitaSemana,
            'receita_mes' => $receitaMes,
            'qtd_vendas_mes' => $qtdVendasMes,
            'ticket_medio' => $ticketMedio,
            'parcelas_pendentes' => $parcelasPendentes,
            'valor_pendente' => $valorPendente,
            'produtos_estoque_baixo' => $produtosEstoqueBaixo,
            'vendas_parceladas' => $vendasParceladas,
            'vendas_a_vista' => $vendasAVista,
        ];
    }

    /**
     * Busca vendas por dia (últimos N dias)
     */
    protected function getVendasPorDia($usuarioId, $dias = 30)
    {
        $dataInicio = date('Y-m-d', strtotime("-{$dias} days"));
        
        $sql = "
            SELECT 
                DATE(data_venda) as dia,
                COUNT(*) as quantidade,
                COALESCE(SUM(valor_total), 0) as valor_total
            FROM prest_vendas
            WHERE usuario_id = :usuario_id
            AND data_venda >= :data_inicio
            GROUP BY DATE(data_venda)
            ORDER BY dia ASC
        ";

        return Yii::$app->db->createCommand($sql)
            ->bindValue(':usuario_id', $usuarioId)
            ->bindValue(':data_inicio', $dataInicio)
            ->queryAll();
    }

    /**
     * Busca vendas por mês (últimos N meses)
     */
    protected function getVendasPorMes($usuarioId, $meses = 12)
    {
        $dataInicio = date('Y-m-01', strtotime("-{$meses} months"));
        
        $sql = "
            SELECT 
                TO_CHAR(data_venda, 'YYYY-MM') as mes,
                COUNT(*) as quantidade,
                COALESCE(SUM(valor_total), 0) as valor_total
            FROM prest_vendas
            WHERE usuario_id = :usuario_id
            AND data_venda >= :data_inicio
            GROUP BY TO_CHAR(data_venda, 'YYYY-MM')
            ORDER BY mes ASC
        ";

        return Yii::$app->db->createCommand($sql)
            ->bindValue(':usuario_id', $usuarioId)
            ->bindValue(':data_inicio', $dataInicio)
            ->queryAll();
    }

    /**
     * Busca vendas por forma de pagamento
     */
    protected function getVendasPorFormaPagamento($usuarioId)
    {
        $primeiroDiaMes = date('Y-m-01');
        $ultimoDiaMes = date('Y-m-t');
        
        $sql = "
            SELECT 
                fp.nome as forma_pagamento,
                COUNT(v.id) as quantidade,
                COALESCE(SUM(v.valor_total), 0) as valor_total
            FROM prest_vendas v
            LEFT JOIN prest_formas_pagamento fp ON fp.id = v.forma_pagamento_id
            WHERE v.usuario_id = :usuario_id
            AND v.data_venda >= :data_inicio
            AND v.data_venda <= :data_fim
            GROUP BY fp.id, fp.nome
            ORDER BY valor_total DESC
        ";

        return Yii::$app->db->createCommand($sql)
            ->bindValue(':usuario_id', $usuarioId)
            ->bindValue(':data_inicio', $primeiroDiaMes)
            ->bindValue(':data_fim', $ultimoDiaMes)
            ->queryAll();
    }

    /**
     * Busca vendas parceladas vs à vista
     */
    protected function getVendasParceladasVsVista($usuarioId)
    {
        $primeiroDiaMes = date('Y-m-01');
        $ultimoDiaMes = date('Y-m-t');
        
        $sql = "
            SELECT 
                CASE 
                    WHEN numero_parcelas > 1 THEN 'Parcelada'
                    ELSE 'À Vista'
                END as tipo,
                COUNT(*) as quantidade,
                COALESCE(SUM(valor_total), 0) as valor_total
            FROM prest_vendas
            WHERE usuario_id = :usuario_id
            AND data_venda >= :data_inicio
            AND data_venda <= :data_fim
            GROUP BY CASE 
                WHEN numero_parcelas > 1 THEN 'Parcelada'
                ELSE 'À Vista'
            END
        ";

        return Yii::$app->db->createCommand($sql)
            ->bindValue(':usuario_id', $usuarioId)
            ->bindValue(':data_inicio', $primeiroDiaMes)
            ->bindValue(':data_fim', $ultimoDiaMes)
            ->queryAll();
    }

    /**
     * Busca rank de vendedores
     */
    protected function getRankVendedores($usuarioId)
    {
        $primeiroDiaMes = date('Y-m-01 00:00:00');
        $ultimoDiaMes = date('Y-m-t 23:59:59');
        
        $sql = "
            SELECT 
                c.id,
                c.nome_completo,
                COUNT(v.id) as total_vendas,
                COALESCE(SUM(v.valor_total), 0) as valor_total,
                CASE 
                    WHEN COUNT(v.id) > 0 THEN COALESCE(SUM(v.valor_total), 0) / COUNT(v.id)
                    ELSE 0
                END as ticket_medio
            FROM prest_colaboradores c
            INNER JOIN prest_vendas v ON v.colaborador_vendedor_id = c.id 
            WHERE c.usuario_id = :usuario_id
            AND c.eh_vendedor = true
            AND c.ativo = true
            AND v.usuario_id = :usuario_id
            AND v.data_venda >= :data_inicio
            AND v.data_venda <= :data_fim
            GROUP BY c.id, c.nome_completo
            ORDER BY valor_total DESC, total_vendas DESC
            LIMIT 10
        ";

        try {
            return Yii::$app->db->createCommand($sql)
                ->bindValue(':usuario_id', $usuarioId)
                ->bindValue(':data_inicio', $primeiroDiaMes)
                ->bindValue(':data_fim', $ultimoDiaMes)
                ->queryAll();
        } catch (\Exception $e) {
            Yii::error("Erro ao buscar rank de vendedores: {$e->getMessage()}", __METHOD__);
            return [];
        }
    }

    /**
     * Busca produtos com estoque baixo (próximo do mínimo)
     * Considera produtos com estoque atual menor que o estoque mínimo configurado
     * @return array ['produtos' => [], 'limite' => int]
     */
    protected function getProdutosEstoqueBaixo($usuarioId)
    {
        // Busca produtos onde estoque_atual < estoque_minimo
        // Usa o estoque_minimo de cada produto individualmente
        // Usa Expression para garantir compatibilidade com PostgreSQL
        $produtos = Produto::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['ativo' => true])
            ->andWhere(new Expression('estoque_atual < COALESCE(estoque_minimo, 10)')) // Usa estoque_minimo do produto ou 10 como padrão
            ->with('categoria')
            ->orderBy(['estoque_atual' => SORT_ASC])
            ->limit(20)
            ->all();

        return [
            'produtos' => $produtos,
            'limite' => null // Não há mais um limite global, cada produto tem seu próprio
        ];
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
                    SUM(vi.quantidade) as quantidade_total,
                    COALESCE(SUM(vi.valor_total_item), 0) as receita_total
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