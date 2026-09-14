<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\CarteiraCobranca;

/**
 * AtribuicaoController - Distribuição e Atribuição de Vendas/Cartões a Cobradores de Rua
 */
class AtribuicaoController extends Controller
{
    /**
     * Tela de Atribuição com filtros por Bairro, Cidade, Vendedor e Cobrador
     */
    public function actionIndex($cobrador_id = null, $cidade = null, $bairro = null, $vendedor_id = null, $status = 'ABERTO', $sem_cobrador = null)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $cobradores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->andWhere(['or', ['eh_cobrador' => true], ['eh_cobrador' => null]])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        $vendedores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->andWhere(['or', ['eh_vendedor' => true], ['eh_vendedor' => null]])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        $cidades = Cliente::find()
            ->select('endereco_cidade')
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['not', ['endereco_cidade' => null]])
            ->andWhere(['!=', 'trim(endereco_cidade)', ''])
            ->distinct()
            ->orderBy(['endereco_cidade' => SORT_ASC])
            ->column();

        $bairrosQuery = Cliente::find()
            ->select('endereco_bairro')
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['not', ['endereco_bairro' => null]])
            ->andWhere(['!=', 'trim(endereco_bairro)', '']);
        if ($cidade) {
            $bairrosQuery->andWhere(['ilike', 'endereco_cidade', trim($cidade)]);
        }
        $bairros = $bairrosQuery->distinct()->orderBy(['endereco_bairro' => SORT_ASC])->column();

        // Query de vendas prestanistas (estritamente compras a prestação de crediário)
        $query = Venda::findPrestanista($usuarioId)
            ->leftJoin('prest_clientes c', 'c.id = v.cliente_id')
            ->with(['cliente', 'vendedor', 'parcelas'])
            ->andWhere(['v.tipo_venda' => Venda::TIPO_PRESTANISTA])
            ->andWhere(['>', 'v.numero_parcelas', 1])
            ->orderBy(['c.endereco_bairro' => SORT_ASC, 'c.endereco_logradouro' => SORT_ASC, 'v.id' => SORT_DESC]);

        if ($status === 'ABERTO') {
            $query->andWhere(['v.status_venda_codigo' => ['EM_ABERTO', 'PARCIALMENTE_PAGA']]);
        }

        if ($cidade) {
            $query->andWhere(['ilike', 'c.endereco_cidade', trim($cidade)]);
        }

        if ($bairro) {
            $query->andWhere(['ilike', 'c.endereco_bairro', trim($bairro)]);
        }

        if ($vendedor_id) {
            $query->andWhere(['v.colaborador_vendedor_id' => $vendedor_id]);
        }

        if ($sem_cobrador) {
            // Cartões cujas parcelas pendentes ainda NÃO têm cobrador atribuído
            $query->andWhere([
                'exists',
                (new \yii\db\Query())
                    ->from('prest_parcelas pp')
                    ->where('pp.venda_id = v.id')
                    ->andWhere(['pp.status_parcela_codigo' => StatusParcela::PENDENTE])
                    ->andWhere(['pp.cobrador_id' => null])
            ]);
        } elseif ($cobrador_id) {
            // Cartões atribuídos a um cobrador específico
            $query->andWhere([
                'exists',
                (new \yii\db\Query())
                    ->from('prest_parcelas pp')
                    ->where('pp.venda_id = v.id')
                    ->andWhere(['pp.cobrador_id' => $cobrador_id])
            ]);
        }

        $cartoes = $query->all();

        return $this->render('index', [
            'cartoes' => $cartoes,
            'cobradores' => $cobradores,
            'vendedores' => $vendedores,
            'cidades' => $cidades,
            'bairros' => $bairros,
            'cobrador_id' => $cobrador_id,
            'cidade' => $cidade,
            'bairro' => $bairro,
            'vendedor_id' => $vendedor_id,
            'status' => $status,
            'sem_cobrador' => $sem_cobrador,
        ]);
    }

    /**
     * Processa a atribuição de vendas a um cobrador (em lote por bairro/cidade ou individual)
     */
    public function actionVincular()
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        if (!Yii::$app->request->isPost) {
            return $this->redirect(['index']);
        }

        $post = Yii::$app->request->post();
        $cobradorId = $post['cobrador_id'] ?? null;
        $modo = $post['modo'] ?? 'individual'; // 'individual', 'bairro', 'cidade'
        $vendaIds = $post['venda_ids'] ?? [];
        $bairroAlvo = $post['bairro_alvo'] ?? null;
        $cidadeAlvo = $post['cidade_alvo'] ?? null;

        if (!$cobradorId) {
            Yii::$app->session->setFlash('error', 'Selecione o cobrador de destino para realizar a atribuição.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $cobrador = Colaborador::findOne(['id' => $cobradorId, 'usuario_id' => $usuarioId]);
        if (!$cobrador) {
            Yii::$app->session->setFlash('error', 'Cobrador selecionado não encontrado.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $queryVendas = Venda::findPrestanista($usuarioId)
            ->leftJoin('prest_clientes c', 'c.id = v.cliente_id')
            ->andWhere(['v.tipo_venda' => Venda::TIPO_PRESTANISTA])
            ->andWhere(['>', 'v.numero_parcelas', 1])
            ->andWhere(['v.status_venda_codigo' => ['EM_ABERTO', 'PARCIALMENTE_PAGA']]);

        if ($modo === 'bairro' && $bairroAlvo) {
            $queryVendas->andWhere(['ilike', 'c.endereco_bairro', trim($bairroAlvo)]);
        } elseif ($modo === 'cidade' && $cidadeAlvo) {
            $queryVendas->andWhere(['ilike', 'c.endereco_cidade', trim($cidadeAlvo)]);
        } elseif ($modo === 'individual' && !empty($vendaIds)) {
            $queryVendas->andWhere(['v.id' => $vendaIds]);
        } else {
            Yii::$app->session->setFlash('warning', 'Nenhum cartão ou filtro foi selecionado para atribuição.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $vendasAlvo = $queryVendas->all();
        if (empty($vendasAlvo)) {
            Yii::$app->session->setFlash('warning', 'Nenhum cartão em aberto encontrado para os critérios selecionados.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $totalCartoes = 0;
            $totalParcelas = 0;

            foreach ($vendasAlvo as $venda) {
                // Atualiza as parcelas pendentes
                $parcelasAfetadas = Parcela::updateAll(
                    ['cobrador_id' => $cobrador->id],
                    [
                        'venda_id' => $venda->id,
                        'status_parcela_codigo' => StatusParcela::PENDENTE,
                    ]
                );

                $totalParcelas += $parcelasAfetadas;
                $totalCartoes++;

                // Garante carteira de cobrança para o cliente
                $carteira = CarteiraCobranca::find()
                    ->where(['usuario_id' => $usuarioId, 'cliente_id' => $venda->cliente_id])
                    ->one();

                if ($carteira) {
                    $carteira->cobrador_id = $cobrador->id;
                    $carteira->ativo = true;
                    $carteira->save(false);
                } else {
                    $carteira = new CarteiraCobranca();
                    $carteira->usuario_id = $usuarioId;
                    $carteira->cliente_id = $venda->cliente_id;
                    $carteira->cobrador_id = $cobrador->id;
                    $carteira->data_distribuicao = date('Y-m-d H:i:s');
                    $carteira->ativo = true;
                    $carteira->total_parcelas = (int)$venda->numero_parcelas;
                    $carteira->valor_total = (float)$venda->valor_total;
                    $carteira->save(false);
                }
            }

            $transaction->commit();

            Yii::$app->session->setFlash('success', "✓ Sucesso! {$totalCartoes} cartão(ões) ({$totalParcelas} parcelas pendentes) foram atribuídos ao cobrador {$cobrador->nome_completo}.");
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Erro ao processar atribuição: ' . $e->getMessage());
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }
}
