# 📱 Plano: Pulse Sales – App Flutter Nativo (Online/Offline + Multi-Tenant)

> Gerado em: 2026-09-24 | Análise do projeto Pulse (Yii2/PHP + Flutter WebView)

---

## 1. 🔍 Diagnóstico do Estado Atual

### 1.1 Stack existente
| Camada | Tecnologia | Estado |
|---|---|---|
| Backend | Yii2 (PHP) | ✅ Produção |
| API REST | `/modules/api/` | ✅ JWT Bearer Auth |
| Frontend Web | PWA (HTML/JS modular) | ✅ Funcional |
| App Mobile | Flutter WebView (`pulse_app`) | ⚠️ Shell – só WebView |
| Banco | PostgreSQL (multi-tenant por `usuario_id`) | ✅ |
| Auth | JWT gerado pelo `Usuario::generateJwt()` | ✅ |

### 1.2 O App Flutter atual: o que existe
O `pulse_app` atual é um **shell de WebView** (`flutter_inappwebview`) que encapsula o site web. Ele já tem:
- Bridge JS ↔ Dart (`PulseBridge`) para impressão e scanner
- Scanner de câmera (`mobile_scanner`)
- Scanner de hardware (USB/BT via eventos de teclado)
- Compartilhamento de imagens nativas (WhatsApp)
- Serviço em foreground para notificações (polling sem Firebase)

**O que NÃO existe:** lógica nativa de vendas, SQLite local, sincronização offline/online.

### 1.3 Endpoints de API já disponíveis (relevantes para vendas)
| Endpoint | Função |
|---|---|
| `POST /api/auth/login` | Autenticação JWT |
| `GET /api/produto?usuario_id=X` | Lista produtos do tenant |
| `GET /api/produto/marcas` | Lista marcas |
| `GET /api/categoria` | Lista categorias |
| `POST /api/pedido/create` | Registra nova venda |
| `GET /api/pedido?cliente_id=X` | Lista vendas do cliente |
| `GET /api/cliente/buscar-cpf` | Busca cliente por CPF |
| `POST /api/cliente/create` | Cadastra cliente |
| `GET /api/forma-pagamento/ativas` | Lista formas de pagamento ativas |

### 1.4 Tabelas principais mapeadas
| Tabela (PostgreSQL) | Equivalente SQLite local |
|---|---|
| `prest_produtos` | `produtos` |
| `prest_venda_itens` | `venda_itens` |
| `prest_vendas` | `vendas` |
| `prest_parcelas` | `parcelas` |
| `prest_clientes` | `clientes` |
| `prest_formas_pagamento` | `formas_pagamento` |
| `prest_categorias` | `categorias` |
| `prest_estoque_movimentacoes` | `estoque_movimentacoes` |
| `prest_usuarios` | `usuarios` (apenas dados do tenant local) |

---

## 2. ✅ Análise de Viabilidade

### Veredicto: **TOTALMENTE VIÁVEL** ✅

| Critério | Avaliação |
|---|---|
| Flutter Android + iOS | ✅ Já tem projeto Flutter configurado |
| SQLite local | ✅ `sqflite` + `drift` são maduras para Flutter |
| Sync online/offline transparente | ✅ Padrão bem estabelecido (local-first) |
| Multi-tenant | ✅ `usuario_id` já é a chave de isolamento |
| API REST existente | ✅ JWT já funciona, endpoints essenciais existentes |
| Sincronização automática | ✅ `connectivity_plus` detecta mudanças de rede |
| Sync inicial/final obrigatório | ✅ Simples de implementar como gate de fluxo |

### Risco principal: **Conflito de dados em sync**
Vendas realizadas offline precisam de estratégia clara de merge (UUID local → servidor). O modelo já usa UUID (`gen_random_uuid()`), o que facilita muito.

---

## 3. 🏗️ Arquitetura Proposta

### Filosofia: **Local-First com Sync Transparente**

```
┌─────────────────────────────────────────────────┐
│               App Flutter (Dart)                │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │        UI Layer (Screens/Widgets)        │   │
│  └──────────────────────────────────────────┘   │
│                       │                          │
│  ┌──────────────────────────────────────────┐   │
│  │        Repository Layer (abstração)      │   │
│  │  decide: SQLite local  OU  API remota    │   │
│  └──────────────────────────────────────────┘   │
│           │                        │             │
│  ┌─────────────────┐   ┌─────────────────────┐  │
│  │  LocalDataSource│   │  RemoteDataSource   │  │
│  │  (drift/SQLite) │   │  (http + JWT)       │  │
│  └─────────────────┘   └─────────────────────┘  │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │        SyncService (background)          │   │
│  │  ConnectivityPlus → auto-sync            │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

### Regras de decisão online/offline (transparente):
1. App verifica `connectivity_plus` a cada ação sensível
2. **Se online** → usa API diretamente, atualiza cache local
3. **Se offline** → usa SQLite local, marca registros com `sync_status = 'pending'`
4. Quando voltar online → `SyncService` envia pendentes automaticamente

---

## 4. 📦 Banco de Dados Local: `drift` (recomendado sobre `sqflite` puro)

### Por que `drift` e não `sqflite` puro?
| Critério | sqflite | drift |
|---|---|---|
| Type safety | ❌ Maps sem tipo | ✅ Classes geradas |
| Migrations | Manual e frágil | ✅ Automático e versionado |
| Streams reativos | ❌ Não tem | ✅ `watchSingleOrNull()` |
| Transactions | Manual | ✅ Integrado |
| Legibilidade | Baixa | ✅ Alta |

### Esquema SQLite local (tabelas essenciais)

```sql
-- Tenants/Lojas (multi-tenant)
CREATE TABLE tenants (
  id TEXT PRIMARY KEY,           -- usuario_id do servidor
  nome TEXT NOT NULL,
  token_jwt TEXT NOT NULL,
  url_servidor TEXT NOT NULL,
  logo_url TEXT,
  ultima_sync TEXT,
  ativo INTEGER DEFAULT 1
);

-- Produtos (sincronizados do servidor)
CREATE TABLE produtos (
  id TEXT PRIMARY KEY,           -- UUID do servidor
  usuario_id TEXT NOT NULL,      -- tenant isolamento
  nome TEXT NOT NULL,
  codigo_barras TEXT,
  codigo_referencia TEXT,
  categoria_id TEXT,
  preco_venda_sugerido REAL,
  preco_custo REAL,
  preco_promocional REAL,
  data_inicio_promocao TEXT,
  data_fim_promocao TEXT,
  estoque_atual INTEGER DEFAULT 0,
  estoque_minimo INTEGER DEFAULT 0,
  unidade_medida TEXT DEFAULT 'UN',
  venda_fracionada INTEGER DEFAULT 0,
  ativo INTEGER DEFAULT 1,
  foto_url TEXT,
  marca TEXT,
  ultima_sync TEXT
);

-- Categorias
CREATE TABLE categorias (
  id TEXT PRIMARY KEY,
  usuario_id TEXT NOT NULL,
  nome TEXT NOT NULL,
  ativa INTEGER DEFAULT 1
);

-- Clientes
CREATE TABLE clientes (
  id TEXT PRIMARY KEY,
  usuario_id TEXT NOT NULL,
  nome_completo TEXT NOT NULL,
  cpf TEXT,
  telefone TEXT,
  email TEXT,
  endereco_logradouro TEXT,
  endereco_numero TEXT,
  endereco_bairro TEXT,
  endereco_cidade TEXT,
  endereco_estado TEXT,
  ativo INTEGER DEFAULT 1,
  sync_status TEXT DEFAULT 'synced',  -- 'synced' | 'pending' | 'error'
  ultima_sync TEXT
);

-- Formas de Pagamento
CREATE TABLE formas_pagamento (
  id TEXT PRIMARY KEY,
  usuario_id TEXT NOT NULL,
  nome TEXT NOT NULL,
  tipo TEXT NOT NULL,
  ativo INTEGER DEFAULT 1,
  aceita_parcelamento INTEGER DEFAULT 0,
  ultima_sync TEXT
);

-- Vendas (coração do sistema offline)
CREATE TABLE vendas (
  id TEXT PRIMARY KEY,           -- UUID gerado localmente
  usuario_id TEXT NOT NULL,
  cliente_id TEXT,
  colaborador_vendedor_id TEXT,
  forma_pagamento_id TEXT,
  data_venda TEXT NOT NULL,
  valor_total REAL NOT NULL,
  numero_parcelas INTEGER DEFAULT 1,
  status_venda_codigo TEXT DEFAULT 'PENDENTE',
  observacoes TEXT,
  acrescimo_valor REAL DEFAULT 0,
  acrescimo_tipo TEXT,
  desconto_global_valor REAL DEFAULT 0,
  desconto_global_tipo TEXT,
  cpf_consumidor TEXT,
  tipo_venda TEXT DEFAULT 'BALCAO',
  sync_status TEXT DEFAULT 'pending',  -- 'pending' | 'syncing' | 'synced' | 'error'
  sync_error TEXT,
  data_criacao TEXT NOT NULL,
  data_atualizacao TEXT
);

-- Itens da Venda
CREATE TABLE venda_itens (
  id TEXT PRIMARY KEY,
  venda_id TEXT NOT NULL,
  produto_id TEXT,
  variante_id TEXT,
  nome_item_manual TEXT,
  quantidade REAL NOT NULL,
  preco_unitario_venda REAL NOT NULL,
  desconto_percentual REAL DEFAULT 0,
  desconto_valor REAL DEFAULT 0,
  valor_total_item REAL NOT NULL,
  sync_status TEXT DEFAULT 'pending'
);

-- Parcelas
CREATE TABLE parcelas (
  id TEXT PRIMARY KEY,
  venda_id TEXT NOT NULL,
  usuario_id TEXT NOT NULL,
  numero_parcela INTEGER NOT NULL,
  valor_parcela REAL NOT NULL,
  data_vencimento TEXT NOT NULL,
  status_parcela_codigo TEXT DEFAULT 'ABERTA',
  sync_status TEXT DEFAULT 'pending'
);

-- Fila de Sincronização (log de operações pendentes)
CREATE TABLE sync_queue (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tabela TEXT NOT NULL,          -- 'vendas', 'clientes', etc.
  registro_id TEXT NOT NULL,     -- UUID do registro
  operacao TEXT NOT NULL,        -- 'INSERT' | 'UPDATE' | 'DELETE'
  payload TEXT NOT NULL,         -- JSON serializado
  tentativas INTEGER DEFAULT 0,
  ultimo_erro TEXT,
  criado_em TEXT NOT NULL
);
```

---

## 5. 🔄 Fluxo de Sincronização

### 5.1 Sync Inicial (obrigatório para 1º uso)
```
App instala → Tela de login
→ POST /api/auth/login → JWT salvo no Keystore
→ Tela "Sincronização Inicial"
→ Baixar: produtos, categorias, clientes, formas_pagamento
→ Salvar tudo no SQLite local
→ Liberar uso do app
```

### 5.2 Sync Automático (quando online detectado)
```
ConnectivityPlus detecta internet
→ SyncService.syncPendingVendas()
  → SELECT * FROM vendas WHERE sync_status = 'pending'
  → Para cada venda: POST /api/pedido/create
  → Se 200: UPDATE sync_status = 'synced'
  → Se erro: tentativas++ / sync_status = 'error'
→ SyncService.pullUpdates()
  → Baixar produtos/clientes atualizados (desde ultima_sync)
```

### 5.3 Sync Final (recomendado antes de fechar o dia)
```
Usuário clica "Finalizar dia" OU abre configurações
→ Forçar sync de todos os pendentes
→ Confirmar: "X vendas sincronizadas com sucesso"
→ Alertar se houver erros para ação manual
```

### 5.4 Lógica de conflito (UUID)
- UUIDs são gerados localmente antes do envio (compatível com `gen_random_uuid()` do servidor)
- Servidor aceita ID enviado pelo cliente → sem duplicação
- Se o servidor rejeitar por conflito → app marca como `error` e alerta o usuário

---

## 6. 📐 Estrutura do Novo Projeto Flutter

```
pulse_app/
├── lib/
│   ├── main.dart                    # Entry point
│   ├── app.dart                     # MaterialApp + providers
│   │
│   ├── core/
│   │   ├── database/
│   │   │   ├── app_database.dart    # drift database setup
│   │   │   ├── tables/              # Table classes do drift
│   │   │   └── daos/                # Data Access Objects
│   │   ├── network/
│   │   │   ├── api_client.dart      # http + JWT interceptor
│   │   │   └── connectivity.dart    # ConnectivityPlus wrapper
│   │   └── sync/
│   │       ├── sync_service.dart    # Orchestrator de sync
│   │       └── sync_queue.dart      # Fila de operações pendentes
│   │
│   ├── features/
│   │   ├── auth/
│   │   │   ├── login_screen.dart
│   │   │   └── auth_repository.dart
│   │   ├── sync/
│   │   │   ├── initial_sync_screen.dart
│   │   │   └── sync_repository.dart
│   │   ├── vendas/
│   │   │   ├── nova_venda_screen.dart
│   │   │   ├── carrinho_screen.dart
│   │   │   ├── finalizar_venda_screen.dart
│   │   │   ├── historico_vendas_screen.dart
│   │   │   └── venda_repository.dart
│   │   ├── produtos/
│   │   │   ├── lista_produtos_screen.dart
│   │   │   ├── produto_card.dart
│   │   │   └── produto_repository.dart
│   │   ├── clientes/
│   │   │   ├── busca_cliente_screen.dart
│   │   │   ├── cadastro_cliente_screen.dart
│   │   │   └── cliente_repository.dart
│   │   └── dashboard/
│   │       ├── dashboard_screen.dart  # vendas do dia, pendentes, etc.
│   │       └── dashboard_repository.dart
│   │
│   └── shared/
│       ├── widgets/
│       │   ├── connectivity_banner.dart  # Banner vermelho se offline
│       │   ├── sync_status_badge.dart
│       │   └── price_text.dart
│       └── utils/
│           ├── uuid_generator.dart
│           ├── currency_formatter.dart
│           └── date_utils.dart
```

---

## 7. 📋 Dependências Flutter a adicionar

```yaml
dependencies:
  # Banco de dados local
  drift: ^2.18.0
  drift_flutter: ^0.2.0
  sqlite3_flutter_libs: ^0.5.0
  
  # Rede e conectividade
  connectivity_plus: ^6.0.0
  http: ^1.2.0                  # já existe
  
  # Auth e segurança
  flutter_secure_storage: ^9.0.0  # Armazenar JWT no Keystore/Keychain
  
  # State management
  provider: ^6.1.2               # já existe
  # OU: riverpod: ^2.5.0 (recomendado para projetos novos)
  
  # UUID
  uuid: ^4.4.0
  
  # Existentes (manter)
  flutter_bluetooth_serial: ^0.4.0
  permission_handler: ^11.3.1
  mobile_scanner: ^5.1.0
  share_plus: ^10.1.0
  path_provider: ^2.1.3
  flutter_foreground_task: ^6.4.0

dev_dependencies:
  drift_dev: ^2.18.0
  build_runner: ^2.4.0
```

---

## 8. 🔧 Ajustes necessários no Backend (Pulse PHP)

### 8.1 Novos endpoints para sync mobile

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/mobile/sync/produtos` | Produtos com `updated_at > ?` (delta sync) |
| `GET` | `/api/mobile/sync/clientes` | Clientes com delta sync |
| `GET` | `/api/mobile/sync/formas-pagamento` | Formas de pagamento ativas |
| `GET` | `/api/mobile/sync/categorias` | Categorias ativas |
| `POST` | `/api/mobile/venda/batch` | Envio em lote de vendas offline |
| `GET` | `/api/mobile/info` | Info do tenant (nome, logo, config) |

### 8.2 Criar `MobileController.php` em `/modules/api/controllers/`

```php
// modules/api/controllers/MobileController.php
class MobileController extends BaseController {
    // actionSyncProdutos() - retorna apenas produtos modificados desde data X
    // actionSyncClientes()
    // actionVendaBatch() - aceita array de vendas offline
    // actionInfo() - config do tenant para o app
}
```

### 8.3 Ajuste no `PedidoController` para aceitar UUID externo
- Atualmente, o servidor gera o UUID em `beforeSave`
- Precisa aceitar UUID vindo do cliente (se já foi gerado localmente)
- Adicionar validação: se `id` já existe, atualizar em vez de inserir

### 8.4 Endpoint de Delta Sync
Adicionar campo `data_atualizacao` em todas as tabelas sincronizáveis (já existe em `prest_produtos`). O endpoint filtra: `WHERE data_atualizacao > :ultima_sync AND usuario_id = :tenant`.

---

## 9. 🗺️ Roadmap de Implementação

### Fase 1 – Backend (1-2 semanas)
- [ ] Criar `MobileController.php` com endpoints de sync
- [ ] Endpoint de sync delta para produtos, clientes, formas pagamento, categorias
- [ ] Endpoint `POST /api/mobile/venda/batch` com suporte a UUID externo
- [ ] Testar autenticação JWT para o novo controller

### Fase 2 – App Flutter: Core (2-3 semanas)
- [ ] Adicionar dependências: `drift`, `connectivity_plus`, `flutter_secure_storage`, `uuid`
- [ ] Criar schema `drift` com todas as tabelas
- [ ] Implementar `ApiClient` com interceptor JWT
- [ ] Implementar `ConnectivityService` com stream de status
- [ ] Implementar `SyncService` (pull e push)

### Fase 3 – App Flutter: Telas (2-3 semanas)
- [ ] Tela de Login (multi-tenant: campo de URL do servidor OU código de tenant)
- [ ] Tela de Sync Inicial (obrigatória antes do 1º uso)
- [ ] Dashboard (vendas do dia, total, pendentes de sync)
- [ ] Tela de Nova Venda:
  - Busca de produtos (por nome, código de barras, categoria)
  - Carrinho com descontos por item
  - Seleção de cliente (CPF)
  - Seleção de forma de pagamento
  - Acréscimo/desconto global
  - Geração de parcelas
- [ ] Finalização com comprovante (imagem compartilhável)
- [ ] Histórico de vendas (local + online)
- [ ] Tela de Clientes (busca + cadastro rápido)

### Fase 4 – Recursos Avançados (1-2 semanas)
- [ ] Banner de status de conectividade (vermelho = offline)
- [ ] Badge de vendas pendentes de sync
- [ ] Sync em background quando o app é minimizado
- [ ] Comprovante de venda offline (PDF/imagem sem internet)
- [ ] Impressão térmica (mantendo código existente)
- [ ] Scanner de código de barras para busca de produto

### Fase 5 – Testes e Polimento (1 semana)
- [ ] Testes em Android (mín. Android 8)
- [ ] Testes em iOS (mín. iOS 13)
- [ ] Teste de cenário offline completo: login → sync inicial → venda → fechar internet → mais vendas → abrir internet → sync automático
- [ ] Build APK release + IPA

---

## 10. 🔑 Detalhes Multi-Tenant no App

### Como funciona o isolamento:
1. **Login**: App chama `POST /api/auth/login` e recebe JWT + `usuario_id` (tenant)
2. **Armazenamento**: JWT salvo no `flutter_secure_storage` (Keystore Android / Keychain iOS)
3. **SQLite**: Todos os registros têm coluna `usuario_id`. Queries sempre filtram por tenant.
4. **Múltiplos tenants**: App pode suportar troca de conta (limpar SQLite + novo sync)
5. **Colaboradores**: O endpoint de login já retorna dados do colaborador se aplicável

### Multi-tenant na tela de login:
```dart
// Opção A: URL do servidor configurável (para clientes self-hosted)
// Campo: "Endereço do servidor" (ex: https://minha-loja.pulse.com)

// Opção B: Código da loja (simplificado para SaaS)
// Campo: "Código da loja" → App resolve URL automaticamente
```

---

## 11. 🔒 Segurança

| Aspecto | Solução |
|---|---|
| JWT storage | `flutter_secure_storage` (Keystore/Keychain) |
| Dados SQLite | Considerar `sqlcipher_flutter_libs` para criptografia em repouso |
| Expiração JWT | App detecta 401 e força re-login |
| Dados sensíveis | CPF de clientes nunca armazenados em texto puro no log |
| Vendas pendentes | Retry com exponential backoff (máx 3 tentativas) |

---

## 12. 📊 Estimativa de Esforço

| Fase | Esforço estimado | Responsável |
|---|---|---|
| Backend (MobileController) | 1-2 semanas | PHP/Yii2 |
| Flutter Core (DB + Sync) | 2-3 semanas | Flutter/Dart |
| Flutter Telas | 2-3 semanas | Flutter/Dart |
| Testes + Build | 1 semana | QA/Dev |
| **Total** | **6-9 semanas** | |

---

## 13. 💡 Recomendações Finais

1. **Usar `drift`** em vez de `sqflite` puro — code generation + migrations automáticas valem o esforço inicial.
2. **Manter o app atual (WebView)** em paralelo — o Flutter nativo de vendas pode ser um módulo separado ou substituir gradualmente.
3. **UUID client-side primeiro** — garantir que o servidor aceite IDs vindos do cliente simplifica muito o merge de sync.
4. **Connectivity Banner** — mostrar sempre o status (online/offline) de forma discreta mas clara. O usuário não deve ser surpreendido.
5. **Sync inicial com barra de progresso** — baixar centenas de produtos pode demorar. Mostre progresso real com percentual.
6. **Fotos de produtos**: no modo offline, armazenar apenas a URL (não a imagem em si). Mostrar placeholder quando offline. Isso mantém o app leve.
7. **Considerar Riverpod** para state management — mais moderno e testável que Provider para projetos de longa vida.
