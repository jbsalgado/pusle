<?php

namespace app\modules\vendas\helpers;

/**
 * Helper para cálculos de precificação
 * 
 * Implementa as fórmulas corretas para:
 * - Margem de Lucro (sobre o preço de venda)
 * - Markup (sobre o custo)
 * - Cálculo de preço de venda baseado na margem desejada
 */
class PricingHelper
{
    /**
     * Calcula a margem de lucro em percentual
     * Margem = (Preço de Venda - Custo) / Preço de Venda * 100
     * 
     * @param float|string|null $custo O custo total do produto (incluindo frete se aplicável)
     * @param float|string|null $precoVenda O preço de venda
     * @return float Margem de lucro em percentual (0 a 99.99)
     */
    public static function calcularMargemLucro($custo, $precoVenda)
    {
        // Converte para float, tratando strings vazias e null
        $custo = $custo === null || $custo === '' ? 0 : (float) $custo;
        $precoVenda = $precoVenda === null || $precoVenda === '' ? 0 : (float) $precoVenda;
        
        if ($precoVenda <= 0) {
            return 0;
        }
        
        $margem = (($precoVenda - $custo) / $precoVenda) * 100;
        
        // Limitar entre 0 e 99.99 (nunca pode ser 100% ou mais)
        return max(0, min(99.99, round($margem, 2)));
    }

    /**
     * Calcula o markup em percentual
     * Markup = (Preço de Venda - Custo) / Custo * 100
     * 
     * @param float|string|null $custo O custo total do produto (incluindo frete se aplicável)
     * @param float|string|null $precoVenda O preço de venda
     * @return float Markup em percentual
     */
    public static function calcularMarkup($custo, $precoVenda)
    {
        // Converte para float, tratando strings vazias e null
        $custo = $custo === null || $custo === '' ? 0 : (float) $custo;
        $precoVenda = $precoVenda === null || $precoVenda === '' ? 0 : (float) $precoVenda;
        
        if ($custo <= 0) {
            return 0;
        }
        
        $markup = (($precoVenda - $custo) / $custo) * 100;
        
        return max(0, round($markup, 2));
    }

    /**
     * Calcula o preço de venda necessário para atingir uma margem alvo.
     * Esta é a fórmula correta: Preço = Custo / (1 - (Margem / 100))
     * 
     * @param float|string|null $custo O custo total do produto (incluindo frete se aplicável)
     * @param float|string|null $margemPercentual A margem desejada (ex: 30 para 30%)
     * @return float Preço de venda necessário
     * @throws \Exception Se a margem for >= 100%
     */
    public static function calcularPrecoPorMargem($custo, $margemPercentual)
    {
        // Converte para float, tratando strings vazias e null
        $custo = $custo === null || $custo === '' ? 0 : (float) $custo;
        $margemPercentual = $margemPercentual === null || $margemPercentual === '' ? 0 : (float) $margemPercentual;
        
        if ($margemPercentual >= 100) {
            throw new \Exception("A margem não pode ser 100% ou mais.");
        }
        
        if ($margemPercentual < 0) {
            throw new \Exception("A margem não pode ser negativa.");
        }
        
        if ($custo <= 0) {
            return 0;
        }
        
        // Fórmula: Custo / (1 - (Margem / 100))
        $preco = $custo / (1 - ($margemPercentual / 100));
        
        return round($preco, 2);
    }

    /**
     * Calcula o preço de venda baseado no markup desejado
     * Preço = Custo * (1 + (Markup / 100))
     * 
     * @param float|string|null $custo O custo total do produto (incluindo frete se aplicável)
     * @param float|string|null $markupPercentual O markup desejado (ex: 50 para 50%)
     * @return float Preço de venda necessário
     */
    public static function calcularPrecoPorMarkup($custo, $markupPercentual)
    {
        // Converte para float, tratando strings vazias e null
        $custo = $custo === null || $custo === '' ? 0 : (float) $custo;
        $markupPercentual = $markupPercentual === null || $markupPercentual === '' ? 0 : (float) $markupPercentual;
        
        if ($markupPercentual < 0) {
            throw new \Exception("O markup não pode ser negativo.");
        }
        
        if ($custo <= 0) {
            return 0;
        }
        
        // Fórmula: Custo * (1 + (Markup / 100))
        $preco = $custo * (1 + ($markupPercentual / 100));
        
        return round($preco, 2);
    }

    /**
     * Calcula o custo total incluindo frete
     * 
     * @param float|string|null $precoCusto Preço de custo do produto
     * @param float|string|null $valorFrete Valor do frete (opcional)
     * @return float Custo total
     */
    public static function calcularCustoTotal($precoCusto, $valorFrete = 0)
    {
        // Converte para float, tratando strings vazias e null
        $precoCusto = $precoCusto === null || $precoCusto === '' ? 0 : (float) $precoCusto;
        $valorFrete = $valorFrete === null || $valorFrete === '' ? 0 : (float) $valorFrete;
        
        return round($precoCusto + $valorFrete, 2);
    }

    /**
     * Calcula o Fator Divisor para precificação inteligente (Markup Divisor)
     * Fator Divisor = 1 - ((%Fixas + %Variáveis + %LucroLíq) / 100)
     * 
     * @param float|string|null $taxaFixaPercentual Taxas fixas em percentual (ex: 5 para 5%)
     * @param float|string|null $taxaVariavelPercentual Taxas variáveis em percentual (ex: 3 para 3%)
     * @param float|string|null $lucroLiquidoPercentual Lucro líquido desejado em percentual (ex: 20 para 20%)
     * @return float Fator divisor (entre 0 e 1)
     * @throws \Exception Se a soma das taxas + lucro for >= 100%
     */
    public static function calcularFatorDivisor($taxaFixaPercentual, $taxaVariavelPercentual, $lucroLiquidoPercentual)
    {
        // Converte todos os valores para float, tratando strings vazias e null
        $taxaFixaPercentual = $taxaFixaPercentual === null || $taxaFixaPercentual === '' ? 0 : (float) $taxaFixaPercentual;
        $taxaVariavelPercentual = $taxaVariavelPercentual === null || $taxaVariavelPercentual === '' ? 0 : (float) $taxaVariavelPercentual;
        $lucroLiquidoPercentual = $lucroLiquidoPercentual === null || $lucroLiquidoPercentual === '' ? 0 : (float) $lucroLiquidoPercentual;
        
        $somaPercentuais = $taxaFixaPercentual + $taxaVariavelPercentual + $lucroLiquidoPercentual;
        
        if ($somaPercentuais >= 100) {
            throw new \Exception("A soma das taxas fixas, variáveis e lucro líquido não pode ser 100% ou mais. Total: {$somaPercentuais}%");
        }
        
        if ($somaPercentuais < 0) {
            throw new \Exception("A soma das taxas e lucro não pode ser negativa.");
        }
        
        $fatorDivisor = 1 - ($somaPercentuais / 100);
        
        return round($fatorDivisor, 4);
    }

    /**
     * Calcula o preço de venda sugerido usando o método Markup Divisor
     * Preço Venda = Preço Custo / Fator Divisor
     * 
     * @param float|string|null $precoCusto Preço de custo do produto (incluindo frete)
     * @param float|string|null $taxaFixaPercentual Taxas fixas em percentual
     * @param float|string|null $taxaVariavelPercentual Taxas variáveis em percentual
     * @param float|string|null $lucroLiquidoPercentual Lucro líquido desejado em percentual
     * @return float Preço de venda sugerido
     * @throws \Exception Se houver erro no cálculo do fator divisor
     */
    public static function calcularPrecoPorMarkupDivisor($precoCusto, $taxaFixaPercentual, $taxaVariavelPercentual, $lucroLiquidoPercentual)
    {
        // Converte preço de custo para float
        $precoCusto = $precoCusto === null || $precoCusto === '' ? 0 : (float) $precoCusto;
        
        if ($precoCusto <= 0) {
            return 0;
        }
        
        $fatorDivisor = self::calcularFatorDivisor($taxaFixaPercentual, $taxaVariavelPercentual, $lucroLiquidoPercentual);
        
        if ($fatorDivisor <= 0) {
            throw new \Exception("O fator divisor não pode ser zero ou negativo. Verifique as taxas e o lucro configurados.");
        }
        
        $precoVenda = $precoCusto / $fatorDivisor;
        
        return round($precoVenda, 2);
    }

    /**
     * Realiza a engenharia reversa do cálculo (A Prova Real)
     * Calcula: Preço Venda - Impostos - Custos = Lucro Real
     * 
     * @param float|string|null $precoVenda Preço de venda
     * @param float|string|null $precoCusto Preço de custo (incluindo frete)
     * @param float|string|null $taxaFixaPercentual Taxas fixas em percentual
     * @param float|string|null $taxaVariavelPercentual Taxas variáveis em percentual
     * @return array ['impostos_fixos' => float, 'impostos_variaveis' => float, 'custo_total' => float, 'lucro_real' => float, 'lucro_percentual' => float]
     */
    public static function calcularProvaReal($precoVenda, $precoCusto, $taxaFixaPercentual, $taxaVariavelPercentual)
    {
        // Converte todos os valores para float, tratando strings vazias e null
        $precoVenda = $precoVenda === null || $precoVenda === '' ? 0 : (float) $precoVenda;
        $precoCusto = $precoCusto === null || $precoCusto === '' ? 0 : (float) $precoCusto;
        $taxaFixaPercentual = $taxaFixaPercentual === null || $taxaFixaPercentual === '' ? 0 : (float) $taxaFixaPercentual;
        $taxaVariavelPercentual = $taxaVariavelPercentual === null || $taxaVariavelPercentual === '' ? 0 : (float) $taxaVariavelPercentual;
        
        if ($precoVenda <= 0) {
            return [
                'impostos_fixos' => 0,
                'impostos_variaveis' => 0,
                'custo_total' => $precoCusto,
                'lucro_real' => 0,
                'lucro_percentual' => 0,
            ];
        }
        
        $impostosFixos = ($precoVenda * $taxaFixaPercentual) / 100;
        $impostosVariaveis = ($precoVenda * $taxaVariavelPercentual) / 100;
        $custoTotal = $precoCusto;
        $lucroReal = $precoVenda - $impostosFixos - $impostosVariaveis - $custoTotal;
        $lucroPercentual = ($lucroReal / $precoVenda) * 100;
        
        return [
            'impostos_fixos' => round($impostosFixos, 2),
            'impostos_variaveis' => round($impostosVariaveis, 2),
            'custo_total' => round($custoTotal, 2),
            'lucro_real' => round($lucroReal, 2),
            'lucro_percentual' => round($lucroPercentual, 2),
        ];
    }
}

