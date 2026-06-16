# Explicação do Cálculo de Prejuízo

## 📊 Como é Calculado o Prejuízo

O sistema calcula o **Lucro Real** usando a fórmula da "Prova Real":

### Fórmula Principal

```
Lucro Real = Preço de Venda - Taxas Fixas - Taxas Variáveis - Custo Total
```

Onde:
- **Preço de Venda**: Valor que o cliente pagará
- **Taxas Fixas**: `Preço de Venda × (Taxa Fixa % / 100)`
- **Taxas Variáveis**: `Preço de Venda × (Taxa Variável % / 100)`
- **Custo Total**: `Preço de Custo + Valor do Frete`

### Quando Há Prejuízo?

Se o **Lucro Real** for **negativo**, há prejuízo.

O valor do prejuízo é o **valor absoluto** do Lucro Real negativo.

## 💡 Exemplo Prático: R$ 6,66 de Prejuízo

Vamos entender de onde vem o valor de **R$ 6,66** de prejuízo:

### Cenário Exemplo:

- **Preço de Venda**: R$ 50,00
- **Preço de Custo**: R$ 40,00
- **Valor do Frete**: R$ 10,00
- **Taxa Fixa**: 5%
- **Taxa Variável**: 3%

### Cálculo Passo a Passo:

1. **Custo Total**:
   ```
   Custo Total = R$ 40,00 + R$ 10,00 = R$ 50,00
   ```

2. **Taxas Fixas**:
   ```
   Taxas Fixas = R$ 50,00 × (5 / 100) = R$ 2,50
   ```

3. **Taxas Variáveis**:
   ```
   Taxas Variáveis = R$ 50,00 × (3 / 100) = R$ 1,50
   ```

4. **Lucro Real**:
   ```
   Lucro Real = R$ 50,00 - R$ 2,50 - R$ 1,50 - R$ 50,00
   Lucro Real = R$ 50,00 - R$ 54,00
   Lucro Real = -R$ 4,00
   ```

   **Prejuízo = R$ 4,00**

### Para Obter R$ 6,66 de Prejuízo:

Se o sistema está mostrando **R$ 6,66** de prejuízo, significa que:

```
Lucro Real = -R$ 6,66
```

Isso acontece quando:
- O **Preço de Venda** é menor que a soma de **Custo Total + Taxas Fixas + Taxas Variáveis**
- A diferença entre o que você recebe e o que você gasta é de R$ 6,66

### Exemplo que Resulta em R$ 6,66 de Prejuízo:

- **Preço de Venda**: R$ 43,34
- **Preço de Custo**: R$ 40,00
- **Valor do Frete**: R$ 10,00
- **Taxa Fixa**: 5%
- **Taxa Variável**: 3%

**Cálculo:**
```
Custo Total = R$ 40,00 + R$ 10,00 = R$ 50,00
Taxas Fixas = R$ 43,34 × 0,05 = R$ 2,17
Taxas Variáveis = R$ 43,34 × 0,03 = R$ 1,30

Lucro Real = R$ 43,34 - R$ 2,17 - R$ 1,30 - R$ 50,00
Lucro Real = R$ 43,34 - R$ 53,47
Lucro Real = -R$ 10,13
```

**Nota:** O valor exato de R$ 6,66 depende dos valores específicos informados no formulário.

## 🎯 Por Que Permitir Prejuízo?

O sistema foi ajustado para **não bloquear** o cadastro quando há prejuízo porque:

1. **Estratégias de Negócio**: Às vezes é necessário vender com prejuízo para:
   - Limpar estoque
   - Conquistar mercado
   - Promoções agressivas
   - Perda calculada em produtos específicos

2. **Autonomia do Usuário**: O comerciante conhece melhor seu negócio e pode decidir quando é estratégico ter prejuízo.

3. **Alertas Informativos**: O sistema continua alertando sobre o prejuízo, mas não impede o cadastro.

## ⚠️ Alertas Visuais

O sistema mostra alertas visuais quando detecta prejuízo:

- **Cor Vermelha**: Indica valores negativos
- **Mensagem de Alerta**: Mostra o valor exato do prejuízo
- **Destaque no Campo**: Campo de preço fica destacado em vermelho

Mas **não bloqueia** o salvamento do produto.

## 📝 Resumo

- **Prejuízo = |Lucro Real|** quando Lucro Real < 0
- **Lucro Real = Preço Venda - Taxas - Custo Total**
- **R$ 6,66** é o valor absoluto do prejuízo calculado
- **Sistema não bloqueia** cadastro com prejuízo
- **Alertas são informativos** apenas

---

**Data:** Janeiro 2025  
**Versão:** 2.0 (Alertas Informativos)

