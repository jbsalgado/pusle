# Análise do Módulo "Venda Direta" (PWA)

**URL de Entrada:** `http://localhost/pulse/web/venda-direta`
**Localização no Projeto:** `/srv/http/pulse/web/venda-direta/`

## 1. Visão Geral

O módulo "Venda Direta" não é uma rota tradicional do Yii2 (Controller/View), mas sim uma **Progressive Web Application (PWA) independente**, hospedada dentro do diretório público (`web`) do ERP. Ela atua como um PDV (Ponto de Venda) móvel ou simplificado.

## 2. Arquitetura Técnica

- **Tipo:** Single Page Application (SPA) / PWA.
- **Linguagem:** Javascript Puro (Vanilla JS) com ES Modules (`import/export`).
- **Estilização:** Tailwind CSS (carregado via CDN em desenvolvimento, ver `index.html` e `style.css`).
- **Armazenamento Offline:** IndexedDB (via `idb-keyval.js`) para persistência local de carrinho e vendas.
- **Service Worker:** `sw.js` para suporte offline e cache de ativos.

## 3. Funcionalidades Analisadas

A aplicação `js/app.js` e seus módulos (`cart.js`, `order.js`, etc.) revelam as seguintes capacidades:

1.  **Catálogo de Produtos:**

    - Carrega produtos da API (`API_ENDPOINTS.PRODUTO`).
    - Paginação real implementada.
    - Busca com _debounce_ para filtrar produtos.
    - Suporte a imagens de produtos com placeholders.

2.  **Carrinho de Compras:**

    - Persistente (salvo localmente).
    - Gestão de quantidade e remoção de itens.
    - Cálculo automático de subtotais e totais.

3.  **Processo de Venda (Checkout):**

    - **Autenticação:** Verifica sessão do colaborador (`auth.js`).
    - **Pagamento:** Dinheiro (comprovante simples) e PIX (estático/gerado).
    - **Modo Offline:** Vendas realizadas sem internet são salvas localmente e sincronizadas (`SYNC_SUCCESS`) quando a conexão retorna.
    - **Comprovantes:** Geração de comprovante em imagem (`html2canvas`) para impressão ou compartilhamento (WhatsApp/Telegram).

4.  **Integrações:**
    - **Cliente:** Possibilidade de vincular ou cadastrar cliente na venda (obrigatório para parcelamento).
    - **Vendedor:** Identificação opcional do vendedor por CPF para comissões.

## 4. Pontos de Atenção

- **Dependência de CDN:** O uso do Tailwind via CDN (`cdn.tailwindcss.com`) em `index.html` não é recomendado para produção devido à performance e dependência externa.
- **Segurança:** A autenticação parece depender de tokens ou sessão do browser compartilhada com o ERP principal.
- **API:** A aplicação consome a API REST do próprio Pulse (`modules/api`).

## 5. Conclusão

O "Venda Direta" é um componente moderno e desacoplado do monolito PHP, focado em agilidade e operação em condições de rede instáveis (funciona offline). Ele complementa o ERP servindo como interface de frente de caixa.
