# Documentação de Melhorias: Busca e Precificação

Este documento detalha as atualizações implementadas no ecossistema **Pulse / Venda Direta** em 12/04/2026.

---

## 1. 🔍 Busca Inteligente (Smart Search)
Melhoramos drasticamente a capacidade do sistema de encontrar produtos, removendo barreiras linguísticas e de digitação.

### O que mudou?
- **Suporte a Unaccent:** A busca agora é "insensível a acentos". O sistema traduz automaticamente caracteres como `Á, É, Í, Ó, Ú, Ç` durante a consulta. Se o usuário buscar por "VALVULA", ele encontrará "VÁLVULA".
- **Busca por Palavras-Chave (Tokens):** O sistema agora quebra a string de busca em termos individuais. 
  - *Exemplo:* Buscar por "PIA VÁLVULA" agora retorna resultados que contenham as duas palavras em qualquer ordem.
- **Abrangência:** A melhoria foi aplicada no **ERP Administrativo** (ProdutoController) e na **API da Venda Direta**, garantindo que o catálogo no celular seja tão eficiente quanto no computador.

---

## 2. 📉 Preços por Escala (Escala de Atacado)
Implementamos uma funcionalidade de preços progressivos para incentivar vendas em maior volume.

### Estrutura de Dados:
Foram adicionadas 10 novas colunas na tabela `prest_produtos`:
- `qtd_escala_1` até `qtd_escala_5`: Quantidades de gatilho para o novo preço.
- `preco_escala_1` até `preco_escala_5`: Valores de venda diferenciados para cada gatilho.

### Funcionamento no PWA (Venda Direta):
- **Detecção Automática:** O carrinho de compras agora possui inteligência para monitorar a quantidade de cada item.
- **Sugestão de Preço:** Quando o vendedor atinge uma quantidade configurada na escala, o sistema **ajusta o preço unitário automaticamente** no carrinho.
- **Flexibilidade:** O valor total é recalculado na hora, proporcionando um fechamento de venda mais ágil para atacado.

---

## 🛡️ 3. Regras de Segurança e Bloqueio
Para manter a integridade financeira das vendas:

- **Trava de Preço Unitário:** No PWA, o preço unitário de produtos vindos do catálogo não pode ser editado manualmente pelo vendedor. Isso evita erros e descontos não autorizados no valor base.
- **Gestão de Descontos:** O vendedor deve obrigatoriamente usar os campos de **Desconto (R$ ou %)** já existentes para realizar abatimentos.
- **Itens Avulsos:** A edição manual de preço continua permitida exclusivamente para itens adicionados via funcionalidade "Item Avulso" (produtos não cadastrados).

---

## 🧪 Validação Técnica
- **Testes Backend:** Modelos e controladores validados sem erros de sintaxe (PHP 7.4/8.0+).
- **Testes de Interface:** Campos de escala integrados visualmente ao formulário de produtos do ERP.
- **Testes Browser:** Verificado comportamento de busca e travamento de campos no ambiente local.

---
**Status Final:** ✅ Implementado, Testado e Documentado.
