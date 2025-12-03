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
     * @param float $custo O custo total do produto (incluindo frete se aplicável)
     * @param float $precoVenda O preço de venda
     * @return float Margem de lucro em percentual (0 a 99.99)
     */
    public static function calcularMargemLucro($custo, $precoVenda)
    {
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
     * @param float $custo O custo total do produto (incluindo frete se aplicável)
     * @param float $precoVenda O preço de venda
     * @return float Markup em percentual
     */
    public static function calcularMarkup($custo, $precoVenda)
    {
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
     * @param float $custo O custo total do produto (incluindo frete se aplicável)
     * @param float $margemPercentual A margem desejada (ex: 30 para 30%)
     * @return float Preço de venda necessário
     * @throws \Exception Se a margem for >= 100%
     */
    public static function calcularPrecoPorMargem($custo, $margemPercentual)
    {
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
     * @param float $custo O custo total do produto (incluindo frete se aplicável)
     * @param float $markupPercentual O markup desejado (ex: 50 para 50%)
     * @return float Preço de venda necessário
     */
    public static function calcularPrecoPorMarkup($custo, $markupPercentual)
    {
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
     * @param float $precoCusto Preço de custo do produto
     * @param float $valorFrete Valor do frete (opcional)
     * @return float Custo total
     */
    public static function calcularCustoTotal($precoCusto, $valorFrete = 0)
    {
        return round($precoCusto + $valorFrete, 2);
    }
}

