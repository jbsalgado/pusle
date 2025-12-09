# Módulo de Precificação Inteligente (Markup Divisor)

## 📋 Resumo da Implementação

Este documento descreve a implementação do módulo de **Precificação Inteligente** usando o método **Markup Divisor** no sistema de gestão de produtos.

## 🎯 Objetivo

Permitir que o gestor calcule automaticamente o preço de venda ideal de um produto considerando:
- Taxas fixas (impostos fixos, taxas de plataforma, etc.)
- Taxas variáveis (comissões, taxas de pagamento, etc.)
- Lucro líquido desejado

## 📐 Fórmula do Markup Divisor

```
Fator Divisor = 1 - ((%Fixas + %Variáveis + %LucroLiq) / 100)
Preço Venda Sugerido = Preço Custo / Fator Divisor
```

## 🗄️ Estrutura do Banco de Dados

### Migration: `m250101_000007_add_precificacao_inteligente_to_prest_produtos.php`

Adiciona três novas colunas na tabela `prest_produtos`:

1. **`taxa_fixa_percentual`** (DECIMAL 5,2)
   - Taxas fixas em percentual
   - Padrão: 0.00
   - Exemplo: Impostos fixos, taxas de plataforma

2. **`taxa_variavel_percentual`** (DECIMAL 5,2)
   - Taxas variáveis em percentual
   - Padrão: 0.00
   - Exemplo: Comissões, taxas de pagamento

3. **`lucro_liquido_percentual`** (DECIMAL 5,2)
   - Lucro líquido desejado em percentual
   - Padrão: 0.00
   - Margem líquida após todos os custos e taxas

## 💻 Backend

### 1. PricingHelper (`modules/vendas/helpers/PricingHelper.php`)

Novos métodos adicionados:

#### `calcularFatorDivisor($taxaFixa, $taxaVariavel, $lucroLiquido)`
Calcula o fator divisor usado na fórmula.

#### `calcularPrecoPorMarkupDivisor($precoCusto, $taxaFixa, $taxaVariavel, $lucroLiquido)`
Calcula o preço de venda sugerido usando o método Markup Divisor.

#### `calcularProvaReal($precoVenda, $precoCusto, $taxaFixa, $taxaVariavel)`
Realiza a engenharia reversa do cálculo, mostrando:
- Impostos fixos
- Impostos variáveis
- Custo total
- Lucro real
- Margem real em percentual

### 2. Model Produto (`modules/vendas/models/Produto.php`)

#### Novos Campos
- `taxa_fixa_percentual`
- `taxa_variavel_percentual`
- `lucro_liquido_percentual`

#### Validações Adicionadas

1. **`validateSomaTaxasLucro()`**
   - Valida que a soma das taxas + lucro não seja >= 100%
   - Impede configurações inválidas

2. **`validatePrejuizo()`**
   - Valida que o preço de venda não resulte em prejuízo
   - Usa o método `calcularProvaReal()` para verificar
   - Exibe mensagem de alerta se detectar prejuízo

## 🎨 Frontend

### Interface da Calculadora

Localização: `modules/vendas/views/produto/_form.php`

#### Layout Mobile-First
- **Mobile**: Layout em coluna única
- **Desktop (lg)**: Grid de 2 colunas
  - **Esquerda**: Inputs (Taxas Fixas, Variáveis, Lucro Líquido)
  - **Direita**: Resultados (Preço Sugerido + Tabela "A Prova Real")

#### Componentes Visuais

1. **Seção de Inputs**
   - Campos para taxas fixas, variáveis e lucro líquido
   - Exibição do Fator Divisor calculado
   - Botão "Calcular Preço de Venda"

2. **Preço Sugerido**
   - Exibido em destaque (verde)
   - Formato: R$ 0,00

3. **Tabela "A Prova Real"**
   - Engenharia reversa do cálculo
   - Mostra: Preço Venda, (-) Taxas Fixas, (-) Taxas Variáveis, (-) Custo Total, (=) Lucro Real
   - Cores semânticas:
     - **Vermelho** (`text-red-500`): Saídas (taxas e custos)
     - **Verde** (`text-green-600`): Lucro positivo
     - **Vermelho** (`text-red-600`): Lucro negativo (prejuízo)

4. **Alerta de Prejuízo**
   - Exibido quando o cálculo resulta em prejuízo
   - Mensagem clara sobre o valor do prejuízo
   - Sugestão de ajuste

### JavaScript

#### Funcionalidades

1. **Cálculo em Tempo Real**
   - Atualiza automaticamente enquanto o usuário digita
   - Recalcula Fator Divisor, Preço Sugerido e Prova Real

2. **Aplicação do Preço Sugerido**
   - Botão "Calcular Preço de Venda" aplica o valor calculado ao campo de preço de venda
   - Feedback visual (verde) ao aplicar

3. **Validação Client-Side**
   - Impede soma de taxas + lucro >= 100%
   - Alerta visual de prejuízo

## ✅ Validações Implementadas

### Backend
- ✅ Soma das taxas + lucro não pode ser >= 100%
- ✅ Validação de prejuízo (margem negativa)
- ✅ Campos numéricos com limites (0 a 99.99%)

### Frontend
- ✅ Cálculo em tempo real
- ✅ Alerta visual de prejuízo
- ✅ Validação de soma de percentuais

## 🚀 Como Usar

1. **Cadastrar/Editar Produto**
   - Acesse o formulário de produto
   - Preencha o **Preço de Custo** e **Valor do Frete**

2. **Configurar Precificação Inteligente**
   - Informe as **Taxas Fixas** (%)
   - Informe as **Taxas Variáveis** (%)
   - Informe o **Lucro Líquido Desejado** (%)

3. **Calcular Preço**
   - Clique em **"Calcular Preço de Venda"**
   - O sistema calculará e aplicará o preço sugerido

4. **Verificar "A Prova Real"**
   - A tabela mostrará a engenharia reversa
   - Verifique se o lucro real está positivo (verde)
   - Se estiver negativo (vermelho), ajuste as taxas ou o preço

5. **Salvar Produto**
   - Os valores das taxas serão salvos junto com o produto
   - O sistema validará se não há prejuízo antes de salvar

## 📝 Exemplo Prático

**Cenário:**
- Preço de Custo: R$ 100,00
- Frete: R$ 10,00
- Taxas Fixas: 5%
- Taxas Variáveis: 3%
- Lucro Líquido Desejado: 20%

**Cálculo:**
```
Custo Total = R$ 100,00 + R$ 10,00 = R$ 110,00
Fator Divisor = 1 - ((5 + 3 + 20) / 100) = 1 - 0.28 = 0.72
Preço Venda = R$ 110,00 / 0.72 = R$ 152,78
```

**A Prova Real:**
```
Preço de Venda:     R$ 152,78
(-) Taxas Fixas:    R$   7,64  (5%)
(-) Taxas Variáveis: R$   4,58  (3%)
(-) Custo Total:    R$ 110,00
(=) Lucro Real:     R$  30,56  (20%)
```

## 🔧 Arquivos Modificados/Criados

1. ✅ `migrations/m250101_000007_add_precificacao_inteligente_to_prest_produtos.php` (NOVO)
2. ✅ `modules/vendas/helpers/PricingHelper.php` (ATUALIZADO)
3. ✅ `modules/vendas/models/Produto.php` (ATUALIZADO)
4. ✅ `modules/vendas/views/produto/_form.php` (ATUALIZADO)

## 📌 Próximos Passos (Opcional)

- [ ] Criar tabela de configurações globais para taxas padrão por usuário
- [ ] Adicionar histórico de precificações
- [ ] Exportar relatório de precificação
- [ ] Integração com API de cálculo de impostos

## 🎓 Referências

- **Markup Divisor**: Método de precificação que considera todas as saídas antes de calcular o preço de venda
- **A Prova Real**: Engenharia reversa que valida se o cálculo está correto

---

**Data de Implementação:** Janeiro 2025  
**Versão:** 1.0.0

