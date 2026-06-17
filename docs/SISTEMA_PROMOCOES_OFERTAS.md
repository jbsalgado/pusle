# Sistema de Promoções e Ofertas

## 📋 Visão Geral

Sistema completo para gerenciar promoções e ofertas de produtos, com tags visuais, cálculos automáticos de desconto e validações.

## ✨ Funcionalidades Implementadas

### 1. **Campos de Promoção**

- **Preço Promocional (R$)**: Preço com desconto durante o período da promoção
- **Data de Início**: Quando a promoção começa
- **Data de Fim**: Quando a promoção termina

### 2. **Tags Visuais de Promoção**

- **Badge de Status**: Indica o status atual da promoção
  - 🟢 **Em Promoção**: Promoção ativa no momento
  - 🔵 **Agendada**: Promoção agendada para o futuro
  - 🔴 **Expirada**: Promoção que já terminou
  - ⚪ **Inativa**: Sem promoção configurada

### 3. **Cálculos Automáticos**

- **Desconto Percentual**: Calculado automaticamente
  ```
  Desconto = ((Preço Normal - Preço Promocional) / Preço Normal) * 100
  ```

- **Economia**: Valor economizado pelo cliente
  ```
  Economia = Preço Normal - Preço Promocional
  ```

### 4. **Preview em Tempo Real**

- Exibe preço normal (riscado) e preço promocional
- Mostra desconto percentual e economia em R$
- Atualiza automaticamente conforme o usuário digita

### 5. **Validações**

- ✅ Preço promocional deve ser menor que preço normal
- ✅ Data de fim deve ser posterior à data de início
- ✅ Datas são obrigatórias quando há preço promocional
- ✅ Feedback visual em tempo real (bordas vermelhas para erros)

## 🎨 Interface do Usuário

### Seção de Promoções

Localizada no formulário de produto, após a calculadora rápida e antes do estoque.

**Características:**
- Design mobile-first com Tailwind CSS
- Gradiente vermelho/laranja para destaque
- Ícone de tag de preço
- Layout responsivo (1 coluna mobile, 2 colunas desktop)

### Componentes Visuais

1. **Status da Promoção** (quando existe)
   - Badge colorido com status
   - Período da promoção
   - Desconto atual

2. **Campos de Entrada**
   - Preço Promocional
   - Data de Início (datetime-local)
   - Data de Fim (datetime-local)

3. **Preview da Promoção**
   - Preço Normal (riscado)
   - Preço Promocional (destaque)
   - Desconto percentual
   - Economia em R$

4. **Tag de Promoção**
   - Aviso de que o produto terá tag "PROMOÇÃO" visível

## 🔧 Implementação Técnica

### Model (Produto.php)

**Campos:**
- `preco_promocional` (float)
- `data_inicio_promocao` (datetime)
- `data_fim_promocao` (datetime)

**Métodos:**
- `getEmPromocao()`: Verifica se está em promoção ativa
- `getPrecoFinal()`: Retorna preço promocional ou normal
- `getDescontoPromocional()`: Calcula desconto percentual

**Validações:**
- `validatePromocao()`: Valida datas e preço promocional

### View (_form.php)

**Seção HTML:**
```php
<!-- Promoções e Ofertas -->
<div class="bg-gradient-to-r from-red-50 to-orange-50...">
    <!-- Status, Campos, Preview, Tag -->
</div>
```

**JavaScript:**
- `atualizarPreviewPromocao()`: Atualiza preview em tempo real
- `calcularDescontoPercentual()`: Calcula desconto
- `calcularEconomia()`: Calcula economia
- `validarDatasPromocao()`: Valida datas

### Banco de Dados

**Tabela: `prest_produtos`**

```sql
preco_promocional       numeric(10,2)
data_inicio_promocao    timestamp with time zone
data_fim_promocao       timestamp with time zone
```

## 📱 Mobile-First Design

### Responsividade

- **Mobile**: 1 coluna, textos menores, padding reduzido
- **Tablet**: 2 colunas para datas
- **Desktop**: Layout completo com espaçamentos maiores

### Classes Tailwind Utilizadas

- `grid-cols-1 sm:grid-cols-2`: Grid responsivo
- `text-xs sm:text-sm`: Textos responsivos
- `p-3 sm:p-4`: Padding responsivo
- `gap-3 sm:gap-4`: Espaçamento responsivo

## 🎯 Fluxo de Uso

### Criar Promoção

1. Acesse o formulário de produto (criar ou editar)
2. Role até a seção **"Promoções e Ofertas"**
3. Preencha:
   - Preço Promocional (menor que preço normal)
   - Data de Início
   - Data de Fim
4. Visualize o preview automático
5. Salve o produto

### Visualizar Status

- **Em Promoção**: Badge verde, desconto visível
- **Agendada**: Badge azul, período futuro
- **Expirada**: Badge vermelho, período passado

## ✅ Validações Implementadas

### Backend (Model)

```php
public function validatePromocao($attribute, $params)
{
    // Se tem preço promocional, deve ter datas
    if (!empty($this->preco_promocional)) {
        if (empty($this->data_inicio_promocao) || empty($this->data_fim_promocao)) {
            $this->addError($attribute, 'Datas são obrigatórias.');
        }
        
        // Preço promocional deve ser menor
        if ($this->preco_promocional >= $this->preco_venda_sugerido) {
            $this->addError($attribute, 'Preço promocional deve ser menor.');
        }
    }
}
```

### Frontend (JavaScript)

- Validação de preço promocional vs preço normal
- Validação de datas (fim > início)
- Feedback visual em tempo real

## 🚀 Próximos Passos (Opcional)

### Melhorias Futuras

1. **Listagem de Produtos**
   - Adicionar tag "PROMOÇÃO" na listagem
   - Destacar produtos em promoção

2. **Relatórios**
   - Produtos em promoção
   - Promoções expiradas
   - Performance de promoções

3. **Notificações**
   - Alertar sobre promoções que vão expirar
   - Notificar quando promoção começar

4. **Histórico**
   - Histórico de promoções do produto
   - Estatísticas de vendas em promoção

## 📊 Exemplo de Uso

### Cenário: Produto em Promoção

**Produto:**
- Preço Normal: R$ 100,00
- Preço Promocional: R$ 80,00
- Data Início: 01/01/2025 00:00
- Data Fim: 31/01/2025 23:59

**Resultado:**
- Desconto: 20%
- Economia: R$ 20,00
- Status: Em Promoção (badge verde)
- Tag: "PROMOÇÃO" visível

## 🔍 Verificação de Status

### Lógica de Status

```php
$agora = new \DateTime();
$inicio = new \DateTime($data_inicio);
$fim = new \DateTime($data_fim);

if ($agora < $inicio) {
    // Agendada
} elseif ($agora >= $inicio && $agora <= $fim) {
    // Em Promoção
} else {
    // Expirada
}
```

## 📝 Notas Importantes

1. **Formato de Data**: Usa `datetime-local` no HTML, convertido automaticamente
2. **Timezone**: Considera timezone do servidor
3. **Validação**: Backend e frontend validam
4. **Performance**: Cálculos são feitos em JavaScript (client-side)

---

**Data:** Janeiro 2025  
**Versão:** 1.0

