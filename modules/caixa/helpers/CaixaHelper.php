<?php

namespace app\modules\caixa\helpers;

use Yii;
use app\modules\caixa\models\Caixa;
use app\modules\caixa\models\CaixaMovimentacao;

/**
 * Helper para operações relacionadas ao Caixa
 * 
 * Este helper fornece métodos estáticos para registrar movimentações
 * no caixa de forma automática, integrando com outros módulos do sistema.
 */
class CaixaHelper
{
    /**
     * Registra entrada no caixa quando uma venda direta é finalizada
     * 
     * @param string $vendaId ID da venda
     * @param float $valor Valor da venda
     * @param string|null $formaPagamentoId ID da forma de pagamento (opcional)
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @return bool|CaixaMovimentacao Retorna a movimentação criada ou false em caso de erro
     */
    public static function registrarEntradaVenda($vendaId, $valor, $formaPagamentoId = null, $usuarioId = null)
    {
        try {
            $usuarioId = $usuarioId ?: Yii::$app->user->id;

            if (!$usuarioId) {
                Yii::warning("Tentativa de registrar venda no caixa sem usuário identificado", 'caixa');
                return false;
            }

            // Busca caixa aberto do usuário
            $caixa = Caixa::find()
                ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
                ->orderBy(['data_abertura' => SORT_DESC])
                ->one();

            if (!$caixa) {
                Yii::warning("⚠️ VENDA REALIZADA COM CAIXA FECHADO. Venda ID: {$vendaId}, Usuário ID: {$usuarioId}, Valor: R$ {$valor}. A venda foi processada, mas não foi registrada no caixa. É necessário abrir um caixa e registrar a movimentação manualmente.", 'caixa');
                // Não lança exceção, apenas registra no log
                // O sistema pode funcionar sem caixa aberto (vendas podem ser registradas depois)
                return false;
            }

            // Verifica se o caixa é do dia anterior
            if ($caixa->isAbertoDiaAnterior()) {
                // Fecha automaticamente o caixa do dia anterior
                $caixa->fecharAutomaticamente("Fechado automaticamente: caixa do dia anterior detectado ao registrar venda #{$vendaId}.");
                Yii::warning("⚠️ VENDA REALIZADA COM CAIXA DO DIA ANTERIOR. O caixa foi fechado automaticamente. Venda ID: {$vendaId}, Usuário ID: {$usuarioId}, Valor: R$ {$valor}. É necessário abrir um novo caixa para registrar esta e futuras vendas.", 'caixa');
                // Não registra a movimentação no caixa fechado
                return false;
            }

            // Verifica se o caixa é do dia atual
            if (!$caixa->isAbertoHoje()) {
                // Caso raro: caixa aberto mas não é de hoje nem de ontem (pode ser bug)
                Yii::error("ERRO: Caixa aberto com data inválida. Caixa ID: {$caixa->id}, Data Abertura: {$caixa->data_abertura}, Venda ID: {$vendaId}", 'caixa');
                return false;
            }

            // Cria a movimentação
            $movimentacao = new CaixaMovimentacao();
            $movimentacao->caixa_id = $caixa->id;
            // Nota: usuario_id não existe na tabela movimentacoes, o usuário é obtido através do caixa
            $movimentacao->tipo = CaixaMovimentacao::TIPO_ENTRADA;
            $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_VENDA;
            $movimentacao->valor = $valor;
            $movimentacao->descricao = "Venda #" . substr($vendaId, 0, 8);
            $movimentacao->venda_id = $vendaId;
            $movimentacao->forma_pagamento_id = $formaPagamentoId;
            $movimentacao->data_movimento = date('Y-m-d H:i:s');

            if (!$movimentacao->save()) {
                $erros = $movimentacao->getFirstErrors();
                Yii::error("Erro ao registrar movimentação no caixa: " . implode(', ', $erros), 'caixa');
                return false;
            }

            Yii::info("✅ Movimentação registrada no caixa: Venda #{$vendaId}, Valor: R$ {$valor}, Caixa: {$caixa->id}", 'caixa');

            return $movimentacao;
        } catch (\Exception $e) {
            Yii::error("Exceção ao registrar entrada de venda no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }

    /**
     * Registra uma entrada genérica no caixa aberto do usuário
     * 
     * @param float $valor Valor da entrada
     * @param string $descricao Descrição da movimentação
     * @param string|null $formaPagamentoId ID da forma de pagamento
     * @param string|null $usuarioId ID do usuário
     * @return bool|CaixaMovimentacao
     */
    public static function registrarEntrada($valor, $descricao = 'Entrada Diversa', $formaPagamentoId = null, $usuarioId = null)
    {
        try {
            $usuarioId = $usuarioId ?: Yii::$app->user->id;
            if (!$usuarioId) return false;

            $caixa = self::getCaixaAberto($usuarioId);
            if (!$caixa) {
                Yii::warning("⚠️ ENTRADA REGISTRADA COM CAIXA FECHADO. Valor: R$ {$valor}, Descrição: {$descricao}.", 'caixa');
                return false;
            }

            $movimentacao = new CaixaMovimentacao();
            $movimentacao->caixa_id = $caixa->id;
            $movimentacao->tipo = CaixaMovimentacao::TIPO_ENTRADA;
            $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_VENDA;
            $movimentacao->valor = (float)$valor;
            $movimentacao->descricao = $descricao;
            $movimentacao->forma_pagamento_id = $formaPagamentoId;
            $movimentacao->data_movimento = date('Y-m-d H:i:s');

            if (!$movimentacao->save()) {
                Yii::error("Erro ao salvar entrada no caixa: " . implode(', ', $movimentacao->getFirstErrors()), 'caixa');
                return false;
            }

            return $movimentacao;
        } catch (\Exception $e) {
            Yii::error("Exceção ao registrar entrada no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }

    /**
     * Busca o caixa aberto atual do usuário (do dia atual)
     * 
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @param bool $fecharDiaAnterior Se true, fecha automaticamente caixas do dia anterior
     * @return Caixa|null Retorna o caixa aberto do dia atual ou null se não houver
     */
    public static function getCaixaAberto($usuarioId = null, $fecharDiaAnterior = true)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        if (!$usuarioId) {
            return null;
        }

        $caixasAbertos = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
            ->orderBy(['data_abertura' => SORT_DESC])
            ->all();

        if (empty($caixasAbertos)) {
            return null;
        }

        // Se há múltiplos caixas abertos, fecha os do dia anterior
        if (count($caixasAbertos) > 1 && $fecharDiaAnterior) {
            foreach ($caixasAbertos as $caixa) {
                if ($caixa->isAbertoDiaAnterior()) {
                    $caixa->fecharAutomaticamente('Fechado automaticamente: múltiplos caixas abertos detectados.');
                    Yii::warning("Caixa do dia anterior fechado automaticamente: {$caixa->id}", 'caixa');
                }
            }
        }

        // Retorna o primeiro caixa do dia atual
        foreach ($caixasAbertos as $caixa) {
            if ($caixa->isAbertoHoje()) {
                return $caixa;
            }
        }

        // Se não há caixa do dia atual, retorna null
        return null;
    }

    /**
     * Fecha automaticamente todos os caixas do dia anterior para um usuário
     * 
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @return int Número de caixas fechados
     */
    public static function fecharCaixasDiaAnterior($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        if (!$usuarioId) {
            return 0;
        }

        $caixasDiaAnterior = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
            ->all();

        $fechados = 0;
        foreach ($caixasDiaAnterior as $caixa) {
            if ($caixa->isAbertoDiaAnterior()) {
                if ($caixa->fecharAutomaticamente('Fechado automaticamente: limpeza de caixas do dia anterior.')) {
                    $fechados++;
                }
            }
        }

        if ($fechados > 0) {
            Yii::info("Fechados {$fechados} caixa(s) do dia anterior para usuário {$usuarioId}", 'caixa');
        }

        return $fechados;
    }

    /**
     * Registra entrada no caixa quando uma parcela é paga
     * 
     * @param string $parcelaId ID da parcela
     * @param float $valor Valor da parcela
     * @param string|null $formaPagamentoId ID da forma de pagamento (opcional)
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário da parcela)
     * @return bool|CaixaMovimentacao Retorna a movimentação criada ou false em caso de erro
     */
    public static function registrarEntradaParcela($parcelaId, $valor, $formaPagamentoId = null, $usuarioId = null)
    {
        try {
            // Busca a parcela para obter o usuario_id se não foi informado
            $parcela = \app\modules\vendas\models\Parcela::findOne($parcelaId);
            if (!$parcela) {
                Yii::warning("Tentativa de registrar parcela no caixa: parcela não encontrada. Parcela ID: {$parcelaId}", 'caixa');
                return false;
            }

            $usuarioId = $usuarioId ?: $parcela->usuario_id ?: Yii::$app->user->id;

            if (!$usuarioId) {
                Yii::warning("Tentativa de registrar parcela no caixa sem usuário identificado. Parcela ID: {$parcelaId}", 'caixa');
                return false;
            }

            // Verifica se já existe movimentação para esta parcela (evita duplicação)
            $movimentacaoExistente = CaixaMovimentacao::find()
                ->where(['parcela_id' => $parcelaId])
                ->one();

            if ($movimentacaoExistente) {
                Yii::info("Movimentação já existe para parcela {$parcelaId}. Evitando duplicação. Movimentação ID: {$movimentacaoExistente->id}", 'caixa');
                return $movimentacaoExistente;
            }

            // Busca caixa aberto do dia atual
            $caixa = self::getCaixaAberto($usuarioId);

            if (!$caixa) {
                Yii::warning("⚠️ PARCELA PAGA COM CAIXA FECHADO. Parcela ID: {$parcelaId}, Usuário ID: {$usuarioId}, Valor: R$ {$valor}. A parcela foi marcada como paga, mas não foi registrada no caixa. É necessário abrir um caixa e registrar a movimentação manualmente.", 'caixa');
                // Não lança exceção, apenas registra no log
                // O sistema pode funcionar sem caixa aberto (parcelas podem ser registradas depois)
                return false;
            }

            // Cria a movimentação
            $movimentacao = new CaixaMovimentacao();
            $movimentacao->caixa_id = $caixa->id;
            $movimentacao->tipo = CaixaMovimentacao::TIPO_ENTRADA;
            $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_PAGAMENTO;
            $movimentacao->valor = $valor;
            $movimentacao->descricao = "Pagamento de parcela #" . substr($parcelaId, 0, 8);
            $movimentacao->parcela_id = $parcelaId;
            $movimentacao->forma_pagamento_id = $formaPagamentoId;
            $movimentacao->data_movimento = date('Y-m-d H:i:s');

            if (!$movimentacao->save()) {
                $erros = $movimentacao->getFirstErrors();
                Yii::error("Erro ao registrar movimentação de parcela no caixa: " . implode(', ', $erros), 'caixa');
                return false;
            }

            Yii::info("✅ Movimentação registrada no caixa: Parcela #{$parcelaId}, Valor: R$ {$valor}, Caixa: {$caixa->id}", 'caixa');

            return $movimentacao;
        } catch (\Exception $e) {
            Yii::error("Exceção ao registrar entrada de parcela no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }

    /**
     * Verifica se há saldo suficiente no caixa para uma saída
     * 
     * @param string $caixaId ID do caixa
     * @param float $valor Valor da saída
     * @return bool Retorna true se há saldo suficiente
     */
    public static function verificarSaldoSuficiente($caixaId, $valor)
    {
        $caixa = Caixa::findOne($caixaId);

        if (!$caixa || !$caixa->isAberto()) {
            return false;
        }

        $saldoAtual = $caixa->calcularValorEsperado();
        return $saldoAtual >= $valor;
    }
    /**
     * Registra saída no caixa quando uma conta é paga
     * 
     * @param string $contaPagarId ID da conta a pagar
     * @param float $valor Valor pago
     * @param string|null $formaPagamentoId ID da forma de pagamento
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @param bool $validarSaldo Se true, valida se há saldo suficiente antes de registrar
     * @return bool|CaixaMovimentacao Retorna a movimentação criada ou false em caso de erro
     */
    public static function registrarSaidaContaPagar($contaPagarId, $valor, $formaPagamentoId = null, $usuarioId = null, $validarSaldo = false, $dataPagamento = null)
    {
        try {
            $usuarioId = $usuarioId ?: Yii::$app->user->id;

            // Busca caixa aberto do dia atual
            $caixa = self::getCaixaAberto($usuarioId);

            if (!$caixa) {
                Yii::warning("⚠️ CONTA PAGA COM CAIXA FECHADO. Conta ID: {$contaPagarId}. Valor: R$ {$valor}. A conta foi marcada como paga, mas não debitada do caixa.", 'caixa');
                return false;
            }

            // Validação de saldo se solicitado
            if ($validarSaldo) {
                $saldoAtual = $caixa->calcularValorEsperado();
                if ($saldoAtual < $valor) {
                    Yii::warning("⚠️ SALDO INSUFICIENTE. Conta ID: {$contaPagarId}. Saldo: R$ {$saldoAtual}, Valor: R$ {$valor}", 'caixa');
                    return false;
                }
            }

            $conta = \app\modules\contas_pagar\models\ContaPagar::findOne($contaPagarId);
            $refDataInfo = '';
            if ($dataPagamento && date('Y-m-d', strtotime($dataPagamento)) !== date('Y-m-d')) {
                $refDataInfo = ' [Ref. ' . date('d/m/Y', strtotime($dataPagamento)) . ']';
            }
            $desc = ($conta ? "Pagamento: " . substr($conta->descricao, 0, 50) : "Pagamento Conta #{$contaPagarId}") . $refDataInfo;

            // Cria a movimentação
            $movimentacao = new CaixaMovimentacao();
            $movimentacao->caixa_id = $caixa->id;
            $movimentacao->tipo = CaixaMovimentacao::TIPO_SAIDA;
            $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_CONTA_PAGAR;
            $movimentacao->valor = $valor;
            $movimentacao->descricao = $desc;
            $movimentacao->conta_pagar_id = $contaPagarId;
            $movimentacao->forma_pagamento_id = $formaPagamentoId ?: ($conta->forma_pagamento_id ?? null);
            $movimentacao->data_movimento = date('Y-m-d H:i:s');
            if ($refDataInfo) {
                $movimentacao->observacoes = "Quitação contábil retroativa ref. a " . date('d/m/Y', strtotime($dataPagamento)) . ".";
            }

            if (!$movimentacao->save()) {
                $erros = $movimentacao->getFirstErrors();
                Yii::error("Erro ao registrar saída de conta no caixa: " . implode(', ', $erros), 'caixa');
                return false;
            }

            Yii::info("✅ Saída registrada no caixa: Conta #{$contaPagarId}, Valor: R$ {$valor}", 'caixa');

            return $movimentacao;
        } catch (\Exception $e) {
            Yii::error("Exceção ao registrar saída de conta no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }

    /**
     * Registra duplo lançamento no caixa para pagamento de conta sem saldo prévio (Aporte + Quitação)
     * Isso impede saldo negativo no caixa físico ao utilizar Cartão, Débito em Conta ou PIX.
     * 
     * @param string $contaPagarId ID da conta a pagar
     * @param float $valor Valor pago
     * @param string|null $formaPagamentoId ID da forma de pagamento
     * @param string|null $usuarioId ID do usuário (se null, usa o usuário logado)
     * @param string|null $dataPagamento Data do pagamento
     * @return array|false Retorna array com ['entrada' => $movEntrada, 'saida' => $movSaida] ou false
     */
    public static function registrarPagamentoContaPagarComAporte($contaPagarId, $valor, $formaPagamentoId = null, $usuarioId = null, $dataPagamento = null)
    {
        try {
            $usuarioId = $usuarioId ?: Yii::$app->user->id;

            // Busca caixa aberto do dia atual
            $caixa = self::getCaixaAberto($usuarioId);

            if (!$caixa) {
                Yii::warning("⚠️ CONTA PAGA COM APORTE COM CAIXA FECHADO. Conta ID: {$contaPagarId}. Valor: R$ {$valor}.", 'caixa');
                return false;
            }

            $conta = \app\modules\contas_pagar\models\ContaPagar::findOne($contaPagarId);
            $forma = $formaPagamentoId ? \app\modules\vendas\models\FormaPagamento::findOne($formaPagamentoId) : null;
            $formaNome = $forma ? $forma->nome : 'Cartão / Débito em Conta';

            // O movimento no caixa DEVE sempre refletir a linha do tempo real da sessão do caixa aberto
            $dataHoraAtual = date('Y-m-d H:i:s');
            $refDataInfo = '';
            if ($dataPagamento && date('Y-m-d', strtotime($dataPagamento)) !== date('Y-m-d')) {
                $refDataInfo = ' [Ref. ' . date('d/m/Y', strtotime($dataPagamento)) . ']';
            }
            $descBase = ($conta ? substr($conta->descricao, 0, 50) : "Conta #{$contaPagarId}") . $refDataInfo;

            // 1. REGISTRO DE ENTRADA (Aporte para cobertura do pagamento - Não Operacional)
            $movEntrada = new CaixaMovimentacao();
            $movEntrada->caixa_id = $caixa->id;
            $movEntrada->tipo = CaixaMovimentacao::TIPO_ENTRADA;
            $movEntrada->categoria = CaixaMovimentacao::CATEGORIA_APORTE_CONTA;
            $movEntrada->valor = $valor;
            $movEntrada->descricao = "Aporte Não Operacional p/ Pagamento: {$descBase} ({$formaNome})";
            $movEntrada->conta_pagar_id = $contaPagarId;
            $movEntrada->forma_pagamento_id = $formaPagamentoId ?: ($conta->forma_pagamento_id ?? null);
            $movEntrada->data_movimento = $dataHoraAtual;
            $movEntrada->observacoes = "Lançamento contábil de contrapartida (cobertura externa). Não compõe faturamento ou receita de vendas do dia." . ($refDataInfo ? " Quitação contábil retroativa ref. a " . date('d/m/Y', strtotime($dataPagamento)) . "." : "");

            if (!$movEntrada->save()) {
                $erros = $movEntrada->getFirstErrors();
                Yii::error("Erro ao registrar entrada de aporte no caixa: " . implode(', ', $erros), 'caixa');
                return false;
            }

            // 2. REGISTRO DE SAÍDA (Quitação da conta a pagar)
            $movSaida = new CaixaMovimentacao();
            $movSaida->caixa_id = $caixa->id;
            $movSaida->tipo = CaixaMovimentacao::TIPO_SAIDA;
            $movSaida->categoria = CaixaMovimentacao::CATEGORIA_CONTA_PAGAR;
            $movSaida->valor = $valor;
            $movSaida->descricao = "Pagamento: {$descBase} ({$formaNome})";
            $movSaida->conta_pagar_id = $contaPagarId;
            $movSaida->forma_pagamento_id = $formaPagamentoId ?: ($conta->forma_pagamento_id ?? null);
            $movSaida->data_movimento = $dataHoraAtual;
            $movSaida->observacoes = "Pagamento com cobertura externa ({$formaNome}). Impacto líquido no caixa: R$ 0,00." . ($refDataInfo ? " Quitação contábil retroativa ref. a " . date('d/m/Y', strtotime($dataPagamento)) . "." : "");

            if (!$movSaida->save()) {
                $erros = $movSaida->getFirstErrors();
                Yii::error("Erro ao registrar saída de conta com aporte no caixa: " . implode(', ', $erros), 'caixa');
                $movEntrada->delete(); // Rollback da entrada para não ficar desbalanceado
                return false;
            }

            Yii::info("✅ Duplo registro efetuado no caixa: Entrada R$ {$valor} + Saída R$ {$valor} para Conta #{$contaPagarId} ({$formaNome})", 'caixa');

            return [
                'entrada' => $movEntrada,
                'saida' => $movSaida,
            ];
        } catch (\Exception $e) {
            Yii::error("Exceção ao registrar duplo pagamento no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }

    /**
     * Estorna movimentações de conta a pagar no caixa (tanto saídas simples quanto duplo registro)
     * 
     * @param string $contaPagarId ID da conta a pagar
     * @return bool Retorna true se o estorno foi bem-sucedido
     */
    public static function estornarSaidaContaPagar($contaPagarId)
    {
        try {
            // Busca todas as movimentações relacionadas à conta (incluindo possíveis entradas de aporte)
            $movimentacoes = CaixaMovimentacao::find()
                ->where(['conta_pagar_id' => $contaPagarId])
                ->all();

            if (empty($movimentacoes)) {
                Yii::warning("Tentativa de estornar conta sem movimentação no caixa. Conta ID: {$contaPagarId}", 'caixa');
                return true; // Não há movimentação para estornar
            }

            $sucesso = true;
            foreach ($movimentacoes as $movimentacao) {
                if (!$movimentacao->delete()) {
                    Yii::error("Erro ao deletar movimentação #{$movimentacao->id} para estorno.", 'caixa');
                    $sucesso = false;
                }
            }

            if ($sucesso) {
                Yii::info("✅ Estorno de movimentações realizado com sucesso para Conta #{$contaPagarId}", 'caixa');
            }

            return $sucesso;
        } catch (\Exception $e) {
            Yii::error("Exceção ao estornar movimentações de conta no caixa: " . $e->getMessage(), 'caixa');
            return false;
        }
    }
}
