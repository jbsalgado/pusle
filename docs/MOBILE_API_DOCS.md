# 📱 Pulse Mobile API – Documentação dos Endpoints

> **Base URL:** `https://seu-dominio.com/api/mobile/`  
> **Auth:** `Authorization: Bearer <JWT_TOKEN>`  
> **JWT:** Obtido via `POST /api/auth/login`

---

## Autenticação

```http
POST /api/auth/login
Content-Type: application/json

{
  "username": "seu_login",
  "password": "sua_senha"
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "token": "eyJ...",
    "usuario": { "id": "uuid", "nome": "..." },
    "colaborador": null
  }
}
```

> ⚠️ O token tem validade de **30 dias**. Renove antes de expirar.

---

## 1. Info do Tenant

```http
GET /api/mobile/info
Authorization: Bearer {TOKEN}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "tenant_id": "uuid-do-dono",
    "nome_usuario": "Alex Pedro",
    "loja": {
      "nome_loja": "Minha Loja",
      "logo_url": "https://...",
      "telefone_loja": "...",
      "cidade_loja": "Recife",
      "estado_loja": "PE"
    },
    "configuracao": {
      "pix_chave": "...@...",
      "pix_tipo": "EMAIL"
    },
    "servidor_timestamp": "2026-09-24T08:00:00-03:00"
  }
}
```

---

## 2. Sync de Produtos (delta)

```http
GET /api/mobile/sync/produtos
Authorization: Bearer {TOKEN}

Parâmetros opcionais:
  ?since=2026-09-01T00:00:00   ← só retorna produtos atualizados após essa data
  ?page=1
  ?per_page=200                 ← máx: 500
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "uuid",
        "nome": "Produto X",
        "codigo_barras": "7891234567890",
        "codigo_referencia": "REF-001",
        "categoria_id": "uuid",
        "categoria_nome": "Eletrônicos",
        "preco_venda_sugerido": 150.00,
        "preco_promocional": 120.00,
        "preco_vigente": 120.00,
        "em_promocao": true,
        "estoque_atual": 50,
        "unidade_medida": "UN",
        "venda_fracionada": false,
        "foto_url": "https://...",
        "variantes": [
          {
            "id": "uuid",
            "nome_formatado": "Azul / G",
            "preco_venda": 155.00,
            "estoque_atual": 10,
            "codigo_barras": "7891234567891"
          }
        ],
        "data_atualizacao": "2026-09-24T08:00:00"
      }
    ],
    "meta": {
      "total": 450,
      "page": 1,
      "per_page": 200,
      "total_pages": 3,
      "has_more": true
    },
    "sync_timestamp": "2026-09-24T08:00:00-03:00"
  }
}
```

> 💡 **Estratégia delta:** Salve o `sync_timestamp` após cada sync. Na próxima chamada, envie `?since={sync_timestamp_anterior}`.

---

## 3. Sync de Categorias

```http
GET /api/mobile/sync/categorias
Authorization: Bearer {TOKEN}
?since=2026-09-01T00:00:00   (opcional)
```

---

## 4. Sync de Clientes

```http
GET /api/mobile/sync/clientes
Authorization: Bearer {TOKEN}
?since=2026-09-01T00:00:00
?page=1
?per_page=200
```

---

## 5. Sync de Formas de Pagamento

```http
GET /api/mobile/sync/formas-pagamento
Authorization: Bearer {TOKEN}
```

**Resposta:**
```json
{
  "data": {
    "items": [
      {
        "id": "uuid",
        "nome": "Dinheiro",
        "tipo": "DINHEIRO",
        "aceita_parcelamento": false
      },
      {
        "id": "uuid",
        "nome": "PIX",
        "tipo": "PIX_ESTATICO",
        "aceita_parcelamento": false
      }
    ]
  }
}
```

---

## 6. Status de Sync

```http
GET /api/mobile/sync/status
Authorization: Bearer {TOKEN}
```

**Resposta** – Retorna contagens para o app saber quantos registros baixar:
```json
{
  "data": {
    "tabelas": {
      "produtos":         { "total": 450,  "ultima_atualizacao": "2026-09-24T08:00:00" },
      "categorias":       { "total": 12,   "ultima_atualizacao": null },
      "clientes":         { "total": 1200, "ultima_atualizacao": "2026-09-24T07:30:00" },
      "formas_pagamento": { "total": 5,    "ultima_atualizacao": null }
    },
    "servidor_timestamp": "2026-09-24T08:05:00-03:00"
  }
}
```

---

## 7. Registrar Venda (individual)

```http
POST /api/mobile/venda/registrar
Authorization: Bearer {TOKEN}
Content-Type: application/json
```

**Payload:**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "usuario_id": "uuid-do-tenant",
  "cliente_id": null,
  "colaborador_vendedor_id": null,
  "forma_pagamento_id": "uuid-da-forma",
  "data_venda": "2026-09-24T10:30:00",
  "valor_total": 150.00,
  "numero_parcelas": 1,
  "tipo_venda": "BALCAO",
  "observacoes": "Venda offline sincronizada",
  "cpf_consumidor": "000.000.000-00",
  "acrescimo_valor": 0,
  "acrescimo_tipo": null,
  "desconto_global_valor": 10.00,
  "desconto_global_tipo": "VALOR",
  "itens": [
    {
      "id": "uuid-do-item",
      "produto_id": "uuid-do-produto",
      "variante_id": null,
      "quantidade": 2,
      "preco_unitario": 80.00,
      "desconto_percentual": 0,
      "desconto_valor": 0,
      "nome_item_manual": null
    }
  ],
  "parcelas": []
}
```

> **Idempotência:** Se a venda com o mesmo `id` já existir no servidor, retorna a venda existente sem criar duplicata. Seguro para reenvio em caso de timeout.

> **UUID:** Gere localmente usando `uuid` package do Flutter. O servidor **aceita e preserva** o UUID gerado pelo app.

> **Parcelas:** Se `parcelas` estiver vazio `[]`, o servidor gera automaticamente baseado em `numero_parcelas`.

**Resposta de sucesso:**
```json
{
  "success": true,
  "message": "Venda registrada com sucesso.",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "valor_total": 150.00,
    "status_venda_codigo": "QUITADA",
    "itens": [...],
    "parcelas": [...]
  }
}
```

---

## 8. Envio em Lote (batch)

```http
POST /api/mobile/venda/batch
Authorization: Bearer {TOKEN}
Content-Type: application/json
```

**Payload:**
```json
{
  "vendas": [
    { "id": "uuid-1", ...mesma estrutura do /venda/registrar... },
    { "id": "uuid-2", ... },
    { "id": "uuid-3", ... }
  ]
}
```

> **Limite:** Máximo de **100 vendas por requisição**.

**Resposta:**
```json
{
  "success": true,
  "message": "Lote processado: 3 com sucesso, 0 com erro.",
  "data": {
    "processadas": 3,
    "com_sucesso": 3,
    "com_erro": 0,
    "resultados": [
      { "id": "uuid-1", "sucesso": true },
      { "id": "uuid-2", "sucesso": true, "ja_existe": true, "mensagem": "Venda já registrada anteriormente." },
      { "id": "uuid-3", "sucesso": false, "erro": "Item #0: produto inválido." }
    ]
  }
}
```

---

## 9. Histórico de Vendas

```http
GET /api/mobile/venda/historico
Authorization: Bearer {TOKEN}
?page=1
?per_page=50
?since=2026-09-01T00:00:00   ← vendas criadas após essa data
```

---

## 10. Busca Rápida de Produto (Scanner)

```http
GET /api/mobile/produto/buscar?q=CODIGO_OU_NOME
Authorization: Bearer {TOKEN}
```

**Exemplos:**
```
?q=7891234567890   ← código de barras exato
?q=REF-001         ← código de referência
?q=camisa          ← busca por nome (textual)
```

**Resposta quando encontrado por código de barras de variante:**
```json
{
  "data": {
    "items": [
      {
        "id": "uuid-produto-mestre",
        "nome": "Camisa Polo",
        ...
        "variante_encontrada": {
          "id": "uuid-variante",
          "nome_formatado": "Azul / M",
          "preco": 89.90,
          "estoque_atual": 5,
          "codigo_barras": "7891234567890"
        }
      }
    ]
  }
}
```

---

## Fluxo recomendado no App Flutter

```
1. SYNC INICIAL (primeira instalação):
   GET /api/mobile/sync/status        → saber quantos registros baixar
   GET /api/mobile/sync/produtos      → baixar página por página até has_more=false
   GET /api/mobile/sync/categorias    → idem
   GET /api/mobile/sync/clientes      → idem
   GET /api/mobile/sync/formas-pagamento
   Salvar sync_timestamp no SQLite

2. USO NORMAL (online):
   Usar API diretamente (produtos, clientes, criar venda)
   Cache local sempre atualizado

3. USO OFFLINE:
   Criar venda com UUID local → salvar no SQLite (sync_status='pending')
   Continuar operando normalmente

4. RECONEXÃO (ConnectivityPlus detecta internet):
   POST /api/mobile/venda/batch com todas as vendas pending
   Atualizar sync_status='synced' para as com_sucesso
   Manter sync_status='error' para as com_erro (alertar usuário)

5. DELTA SYNC PERIÓDICO (a cada 30min ou ao abrir o app):
   GET /api/mobile/sync/produtos?since={ultima_sync}
   → Atualizar apenas produtos novos/alterados
```

---

## Códigos de Erro Comuns

| HTTP | Significado |
|------|-------------|
| 401 | JWT ausente, inválido ou expirado |
| 400 | Dados inválidos no payload |
| 404 | Recurso não encontrado |
| 500 | Erro interno do servidor (ver log) |
