<?php

namespace app\modules\admin\services;

use Yii;
use app\models\Usuario;
use app\modules\admin\models\SaasPlano;
use app\modules\admin\models\SaasLojaConfig;
use app\modules\admin\models\SaasFatura;
use app\modules\admin\models\SaasConfigGlobal;
use app\modules\marketplace\models\MarketplacePedido;
use app\modules\vendas\models\Venda;

/**
 * SaasBillingService - Motor de Cálculo e Faturamento do SaaS Pulse
 */
class SaasBillingService
{
    /**
     * Calcula e fecha a fatura mensal para uma loja específica
     *
     * @param string $usuarioId
     * @param string|null $mes 'AAAA-MM' (ex: '2026-08'). Se nulo, usa o mês anterior.
     * @return SaasFatura
     */
    public function fecharMesLoja(string $usuarioId, ?string $mes = null): SaasFatura
    {
        if (!$mes) {
            $mes = date('Y-m', strtotime('first day of last month'));
        }

        $inicioMes = "{$mes}-01 00:00:00";
        $fimMes = date('Y-m-t 23:59:59', strtotime($inicioMes));

        $config = SaasLojaConfig::getOrCreateForUser($usuarioId);
        $plano = $config->plano;

        // 1. Apurar vendas de Marketplaces
        $pedidosMktp = MarketplacePedido::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['between', 'data_pedido', $inicioMes, $fimMes])
            ->andWhere(['not in', 'status', ['CANCELADO', 'cancelled', 'DEVOLVIDO']])
            ->all();

        $gmvMarketplace = 0.0;
        $totalPedidosMktp = count($pedidosMktp);
        foreach ($pedidosMktp as $p) {
            $gmvMarketplace += (float) $p->valor_total;
        }

        // 2. Apurar vendas do Catálogo / PDV Direto
        $vendasCatalogo = Venda::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['between', 'data_venda', $inicioMes, $fimMes])
            ->andWhere(['not in', 'status_venda_codigo', ['CANCELADA', 'ORCAMENTO']])
            ->all();

        $gmvCatalogo = 0.0;
        $totalPedidosCatalogo = count($vendasCatalogo);
        foreach ($vendasCatalogo as $v) {
            $gmvCatalogo += (float) $v->valor_total;
        }

        // 3. Cálculos de taxas e mensalidade
        $mensalidade = $config->getMensalidadeEfetiva();
        $taxaMktp = $config->getTaxaMarketplaceEfetiva();
        $comissaoMktp = round($gmvMarketplace * ($taxaMktp / 100), 2);

        // Se o lojista for isento
        if ($config->status_cobranca === SaasLojaConfig::STATUS_ISENTO) {
            $mensalidade = 0.0;
            $comissaoMktp = 0.0;
        }

        // 4. Franquia de pedidos
        $totalPedidos = $totalPedidosMktp + $totalPedidosCatalogo;
        $pedidosExcedentes = 0;
        $valorExcedentes = 0.0;
        if ($plano && $plano->limite_pedidos_inclusos > 0 && $totalPedidos > $plano->limite_pedidos_inclusos) {
            $pedidosExcedentes = $totalPedidos - $plano->limite_pedidos_inclusos;
            $valorExcedentes = round($pedidosExcedentes * (float) $plano->valor_pedido_excedente, 2);
        }

        $valorTotal = $mensalidade + $comissaoMktp + $valorExcedentes;

        // 5. Data de Vencimento
        $diaVenc = str_pad((string) $config->dia_vencimento, 2, '0', STR_PAD_LEFT);
        $mesSeguinte = date('Y-m', strtotime("{$inicioMes} +1 month"));
        $dataVencimento = "{$mesSeguinte}-{$diaVenc}";

        // 6. Criar ou Atualizar Fatura
        $fatura = SaasFatura::findOne(['usuario_id' => $usuarioId, 'mes_referencia' => $mes]) ?? new SaasFatura();
        $fatura->usuario_id = $usuarioId;
        $fatura->mes_referencia = $mes;
        $fatura->data_fechamento = date('Y-m-d');
        $fatura->data_vencimento = $dataVencimento;
        $fatura->gmv_marketplace = $gmvMarketplace;
        $fatura->gmv_catalogo = $gmvCatalogo;
        $fatura->total_pedidos_marketplace = $totalPedidosMktp;
        $fatura->total_pedidos_catalogo = $totalPedidosCatalogo;
        $fatura->valor_mensalidade = $mensalidade;
        $fatura->valor_comissao_marketplace = $comissaoMktp;
        $fatura->valor_comissao_catalogo = 0.00; // Já coletado via split se ativo
        $fatura->valor_pedidos_excedentes = $valorExcedentes;
        $fatura->valor_total = $valorTotal;
        
        if (!$fatura->status || $fatura->status === SaasFatura::STATUS_PENDENTE) {
            $fatura->status = ($valorTotal == 0) ? SaasFatura::STATUS_PAGA : SaasFatura::STATUS_PENDENTE;
            if ($valorTotal == 0) {
                $fatura->data_pagamento = date('Y-m-d H:i:s');
                $fatura->metodo_pagamento = 'ISENCAO_OU_ZERO';
            }
        }

        $fatura->detalhes_json = [
            'aliquota_marketplace' => $taxaMktp,
            'pedidos_inclusos' => $plano ? $plano->limite_pedidos_inclusos : 0,
            'pedidos_excedentes' => $pedidosExcedentes,
            'gerado_em' => date('Y-m-d H:i:s'),
        ];

        $fatura->save(false);
        return $fatura;
    }

    /**
     * Executa o fechamento mensal para todas as lojas donas de conta
     *
     * @param string|null $mes
     * @return array
     */
    public function fecharMesGlobal(?string $mes = null): array
    {
        $lojas = Usuario::find()
            ->where(['eh_dono_loja' => true])
            ->all();

        $faturas = [];
        foreach ($lojas as $loja) {
            $faturas[] = $this->fecharMesLoja($loja->id, $mes);
        }
        return $faturas;
    }

    /**
     * Verifica faturas atrasadas e atualiza o status de cobrança dos lojistas
     */
    public function processarInadimplencia(): array
    {
        $hoje = date('Y-m-d');
        $faturasAtrasadas = SaasFatura::find()
            ->where(['status' => SaasFatura::STATUS_PENDENTE])
            ->andWhere(['<', 'data_vencimento', $hoje])
            ->all();

        $bloqueadas = 0;
        $notificadas = 0;

        foreach ($faturasAtrasadas as $fatura) {
            $fatura->status = SaasFatura::STATUS_ATRASADA;
            $fatura->save(false);

            $config = SaasLojaConfig::getOrCreateForUser($fatura->usuario_id);
            $diasAtraso = (strtotime($hoje) - strtotime($fatura->data_vencimento)) / 86400;

            if ($diasAtraso > $config->dias_carencia_bloqueio && $config->status_cobranca !== SaasLojaConfig::STATUS_ISENTO) {
                $config->status_cobranca = SaasLojaConfig::STATUS_BLOQUEADO;
                $config->save(false);
                $bloqueadas++;
            } else {
                if ($config->status_cobranca === SaasLojaConfig::STATUS_ADIMPLENTE) {
                    $config->status_cobranca = SaasLojaConfig::STATUS_INADIMPLENTE;
                    $config->save(false);
                }
                $notificadas++;
            }
        }

        return [
            'total_atrasadas' => count($faturasAtrasadas),
            'bloqueadas' => $bloqueadas,
            'notificadas' => $notificadas,
        ];
    }
}
