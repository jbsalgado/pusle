# 📊 Análise: Melhorias no Sistema de Lojas e Usuários

**Data da Análise:** 2025-01-27  
**Versão:** 1.0  
**Objetivo:** Redesenhar arquitetura de lojas e usuários para permitir múltiplas lojas por dono e melhorar autenticação/autorização

---

## 🔍 SITUAÇÃO ATUAL - PROBLEMAS IDENTIFICADOS

### **1. Arquitetura Atual (Problemas)**

#### **Estrutura Atual:**
```
prest_usuarios (Mistura Dono + Loja)
    ├── id (UUID) - Identifica tanto o dono quanto a loja
    ├── cpf (VARCHAR 20) - ÚNICO no sistema (impede múltiplas lojas)
    ├── nome - Nome do dono (mas representa a loja)
    ├── eh_dono_loja (BOOLEAN) - true = é dono e representa uma loja
    ├── hash_senha - Autenticação
    └── ... (dados misturados de dono e loja)
```

#### **Problemas Identificados:**

1. **❌ Confusão Conceitual:**
   - `prest_usuarios` serve tanto como **pessoa (dono)** quanto como **loja (empresa)**
   - Não há separação entre entidades físicas (pessoa) e jurídicas (loja)
   - Campo `nome` representa o dono, mas é usado como identificador da loja

2. **❌ Limitação: Um CPF = Uma Loja**
   - CPF é **único no sistema** (`[['cpf'], 'unique']`)
   - **Impossível** um dono ter múltiplas lojas/filiais
   - Se João Silva tem 3 lojas, precisa criar 3 registros com CPFs diferentes (impossível)

3. **❌ Autenticação Inadequada:**
   - Sistema usa `prest_usuarios` diretamente para autenticação
   - Tabela `user` existe mas **não é utilizada**
   - Não há separação entre credenciais de acesso e dados do negócio
   - Colaboradores podem ter login próprio em `prest_usuarios` com `eh_dono_loja = false`, mas estrutura é confusa

4. **❌ Dados Misturados:**
   - Dados do dono (CPF, nome, telefone) misturados com dados da loja (configurações, gateways)
   - Não há clareza sobre o que pertence ao dono vs. o que pertence à loja

5. **❌ Relacionamentos Ambíguos:**
   - `prest_colaboradores.usuario_id` aponta para o dono (que também é a loja)
   - Não fica claro se é relacionamento com pessoa ou com loja

---

### **2. Impacto no Sistema Atual**

#### **Tabelas Afetadas (23 tabelas com `usuario_id`):**

| Tabela | Campo | Problema Atual | Impacto da Mudança |
|--------|-------|----------------|-------------------|
| `prest_clientes` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_produtos` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_vendas` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_parcelas` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_colaboradores` | `usuario_id` | Aponta para dono (identifica loja) | ⚠️ ALTO - Lógica precisa ser revisada |
| `prest_caixa` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_contas_pagar` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_compras` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_configuracoes` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ ALTO - Configurações são da loja |
| `prest_categorias` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_formas_pagamento` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_fornecedores` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_rotas_cobranca` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_periodos_cobranca` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_regioes` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_orcamentos` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_comissoes` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_estoque_movimentacoes` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_carteira_cobranca` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_historico_cobranca` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_regras_parcelamento` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_comissao_config` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |
| `prest_vendedores` | `usuario_id` | Aponta para dono/loja misturado | ⚠️ MÉDIO - Precisa apontar para loja |

**Total:** 23 tabelas precisam ser migradas

---

### **3. Código Afetado**

#### **Controllers:**
- ✅ `AuthController` - Login/signup precisa usar nova estrutura
- ✅ `DashboardController` - Identificação de loja atual
- ✅ Todos os controllers do módulo `vendas` - Filtros por `usuario_id`
- ✅ `CaixaController` - Identificação de loja
- ✅ `ContaPagarController` - Identificação de loja
- ✅ Todos os controllers da API - Identificação de loja

#### **Models:**
- ✅ `Usuario` - Precisa ser separado em `Dono` e `Loja`
- ✅ `Colaborador` - Relacionamento precisa ser revisado
- ✅ Todos os models que usam `usuario_id` - Precisa apontar para loja

#### **Helpers/Services:**
- ✅ `CaixaHelper` - Identificação de loja
- ✅ Qualquer código que filtra por `usuario_id`

#### **Views:**
- ✅ Formulários de cadastro
- ✅ Dashboards que mostram dados da loja
- ✅ Seleção de loja (se dono tiver múltiplas)

---

## 🎯 PROPOSTA DE MELHORIA - NOVA ARQUITETURA

### **1. Estrutura Proposta**

#### **Separação de Responsabilidades:**

```
┌─────────────────────────────────────────────────────────────┐
│                    TABELA: user                               │
│  (Autenticação Central - Já existe, mas não é usada)          │
├─────────────────────────────────────────────────────────────┤
│  id (INTEGER, PK)                                            │
│  username (VARCHAR 25, UNIQUE)                              │
│  email (VARCHAR 255, UNIQUE)                                │
│  password_hash (VARCHAR 60)                                 │
│  auth_key (VARCHAR 32)                                       │
│  blocked_at (INTEGER, NULL)                                 │
│  confirmed_at (INTEGER, NULL)                               │
│  created_at, updated_at, last_login_at                       │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ 1:N
                            │
        ┌───────────────────┴───────────────────┐
        │                                       │
        ▼                                       ▼
┌─────────────────────────────┐    ┌─────────────────────────────┐
│  TABELA: prest_donos        │    │  TABELA: prest_lojas         │
│  (Dados do Dono)            │    │  (Dados da Loja/Filial)     │
├─────────────────────────────┤    ├─────────────────────────────┤
│  id (UUID, PK)              │    │  id (UUID, PK)              │
│  user_id (INTEGER, FK)       │    │  dono_id (UUID, FK)         │
│  nome_completo (VARCHAR 100) │    │  nome_fantasia (VARCHAR 150)│
│  cpf (VARCHAR 20, UNIQUE)    │    │  razao_social (VARCHAR 150)  │
│  telefone (VARCHAR 30)       │    │  cnpj (VARCHAR 20, NULL)    │
│  email (VARCHAR 100)         │    │  telefone (VARCHAR 30)     │
│  endereco, bairro, cidade   │    │  email (VARCHAR 100)        │
│  estado, cep                │    │  endereco, bairro, cidade   │
│  data_criacao, data_atualiz  │    │  estado, cep                │
│                              │    │  logo_path (VARCHAR 500)    │
│                              │    │  catalogo_path (VARCHAR 100)│
│                              │    │  api_de_pagamento (BOOLEAN) │
│                              │    │  gateway_pagamento (VARCHAR)│
│                              │    │  mercadopago_public_key     │
│                              │    │  mercadopago_access_token   │
│                              │    │  asaas_api_key              │
│                              │    │  ativo (BOOLEAN)            │
│                              │    │  data_criacao, data_atualiz │
└─────────────────────────────┘    └─────────────────────────────┘
        │                                       │
        │ 1:N                                    │ 1:N
        │                                       │
        ▼                                       ▼
┌─────────────────────────────┐    ┌─────────────────────────────┐
│  TABELA: prest_colaboradores│    │  Todas as outras tabelas     │
│  (Funcionários)             │    │  (23 tabelas)                │
├─────────────────────────────┤    ├─────────────────────────────┤
│  id (UUID, PK)              │    │  ...                         │
│  loja_id (UUID, FK)         │    │  loja_id (UUID, FK)          │
│  user_id (INTEGER, FK, NULL)│    │  (substitui usuario_id)      │
│  nome_completo (VARCHAR 100)│    │  ...                         │
│  cpf (VARCHAR 20)           │    │                              │
│  email (VARCHAR 100)        │    │                              │
│  eh_vendedor, eh_cobrador    │    │                              │
│  eh_administrador (BOOLEAN) │    │                              │
│  ativo (BOOLEAN)            │    │                              │
└─────────────────────────────┘    └─────────────────────────────┘
```

---

### **2. Conceitos da Nova Arquitetura**

#### **Hierarquia:**
```
user (Autenticação)
    │
    ├── prest_donos (Dono - Pessoa Física)
    │   │
    │   └── prest_lojas (Lojas/Filiais - 1:N)
    │       │
    │       ├── prest_colaboradores (Funcionários da loja)
    │       │   └── user_id (FK) - Login próprio (opcional)
    │       │
    │       └── prest_* (23 tabelas com loja_id)
    │
    └── prest_colaboradores (Colaborador com login próprio)
        └── loja_id (FK) - Loja onde trabalha
```

#### **Regras de Negócio:**

1. **Um `user` pode ser:**
   - Apenas um dono (`prest_donos.user_id`)
   - Apenas um colaborador (`prest_colaboradores.user_id`)
   - Ambos (dono que também trabalha como colaborador em outra loja)

2. **Um dono pode ter:**
   - Múltiplas lojas/filiais (`prest_lojas.dono_id`)
   - Cada loja é independente (dados isolados)

3. **Um colaborador:**
   - Pertence a uma loja (`prest_colaboradores.loja_id`)
   - Pode ter login próprio (`prest_colaboradores.user_id` não NULL)
   - Ou usar login do dono (`prest_colaboradores.user_id` NULL)

4. **Dados isolados por loja:**
   - Todas as 23 tabelas usam `loja_id` (substitui `usuario_id`)
   - Cada loja vê apenas seus próprios dados

---

### **3. Autenticação e Autorização**

#### **Fluxo de Autenticação:**

```
1. Login (username/email + senha)
   ↓
2. Busca em `user` (tabela central)
   ↓
3. Valida senha (`password_hash`)
   ↓
4. Verifica se está bloqueado (`blocked_at IS NULL`)
   ↓
5. Verifica se está confirmado (`confirmed_at IS NOT NULL`)
   ↓
6. Identifica tipo de usuário:
   ├── É dono? → Busca em `prest_donos` (user_id)
   │   └── Carrega lojas do dono (prest_lojas.dono_id)
   │
   └── É colaborador? → Busca em `prest_colaboradores` (user_id)
       └── Carrega loja do colaborador (prest_colaboradores.loja_id)
   ↓
7. Define loja ativa (se dono tem múltiplas, permite seleção)
   ↓
8. Cria sessão com:
   - user_id
   - tipo (DONO | COLABORADOR)
   - dono_id (se for dono)
   - loja_id (loja ativa)
   - lojas_disponiveis (se dono tem múltiplas)
```

#### **Autorização (RBAC):**

- **Dono:** Acesso total às suas lojas
- **Colaborador Administrador:** Acesso total à loja onde trabalha
- **Colaborador Vendedor:** Acesso limitado (vendas, produtos)
- **Colaborador Cobrador:** Acesso limitado (cobranças, parcelas)

---

## 📋 IMPACTOS DETALHADOS POR MÓDULO

### **MÓDULO 1: Autenticação e Usuários**

#### **Impacto:** 🔴 **ALTO**

**Arquivos Afetados:**
- `models/Usuario.php` - Precisa ser dividido em `Dono` e `Loja`
- `models/Users.php` - Precisa ser usado como identityClass
- `controllers/AuthController.php` - Login/signup precisa ser reescrito
- `models/SignupForm.php` - Cadastro precisa criar dono + loja
- `models/LoginForm.php` - Login precisa usar `user`
- `config/web.php` - `identityClass` precisa mudar para `Users`

**Mudanças Necessárias:**
1. Criar Model `Dono` (`prest_donos`)
2. Criar Model `Loja` (`prest_lojas`)
3. Migrar `Usuario` para usar `user` como base
4. Atualizar `SignupForm` para criar dono + loja inicial
5. Atualizar `LoginForm` para autenticar via `user`
6. Implementar seleção de loja (se dono tem múltiplas)

**Riscos:**
- ⚠️ Usuários existentes precisam ser migrados
- ⚠️ Sessões ativas serão invalidadas
- ⚠️ URLs de login podem mudar

---

### **MÓDULO 2: Vendas**

#### **Impacto:** 🟡 **MÉDIO**

**Arquivos Afetados:**
- Todos os controllers do módulo `vendas`
- Todos os models do módulo `vendas`
- Views que filtram por `usuario_id`

**Mudanças Necessárias:**
1. Substituir `usuario_id` por `loja_id` em todas as queries
2. Atualizar relacionamentos nos models
3. Atualizar filtros automáticos por loja
4. Atualizar validações que verificam `usuario_id`

**Exemplo de Mudança:**
```php
// ANTES:
$produtos = Produto::find()
    ->where(['usuario_id' => Yii::$app->user->id])
    ->all();

// DEPOIS:
$produtos = Produto::find()
    ->where(['loja_id' => Yii::$app->user->loja_id])
    ->all();
```

**Riscos:**
- ⚠️ Queries existentes precisam ser atualizadas
- ⚠️ Relacionamentos podem quebrar temporariamente

---

### **MÓDULO 3: Caixa**

#### **Impacto:** 🟡 **MÉDIO**

**Arquivos Afetados:**
- `modules/caixa/models/Caixa.php`
- `modules/caixa/models/CaixaMovimentacao.php`
- `modules/caixa/helpers/CaixaHelper.php`
- `modules/caixa/controllers/CaixaController.php`

**Mudanças Necessárias:**
1. Substituir `usuario_id` por `loja_id` na tabela `prest_caixa`
2. Atualizar `CaixaHelper` para usar `loja_id`
3. Atualizar queries de busca de caixa aberto
4. Atualizar relacionamentos

**Riscos:**
- ⚠️ Caixas abertos precisam ser migrados
- ⚠️ Histórico de movimentações precisa ser preservado

---

### **MÓDULO 4: Contas a Pagar**

#### **Impacto:** 🟡 **MÉDIO**

**Arquivos Afetados:**
- `modules/contas-pagar/models/ContaPagar.php`
- `modules/contas-pagar/controllers/ContaPagarController.php`

**Mudanças Necessárias:**
1. Substituir `usuario_id` por `loja_id`
2. Atualizar queries e relacionamentos

**Riscos:**
- ⚠️ Contas existentes precisam ser migradas

---

### **MÓDULO 5: Colaboradores**

#### **Impacto:** 🔴 **ALTO**

**Arquivos Afetados:**
- `modules/vendas/models/Colaborador.php`
- `modules/vendas/controllers/ColaboradorController.php`

**Mudanças Necessárias:**
1. Substituir `usuario_id` (que aponta para dono) por `loja_id` (que aponta para loja)
2. Adicionar `user_id` (FK para `user`) para colaboradores com login próprio
3. Atualizar lógica de identificação de loja
4. Atualizar validações de CPF (único por loja, não por dono)

**Riscos:**
- ⚠️ Colaboradores existentes precisam ser migrados
- ⚠️ Lógica de acesso precisa ser revisada

---

### **MÓDULO 6: API**

#### **Impacto:** 🟡 **MÉDIO**

**Arquivos Afetados:**
- Todos os controllers da API (`modules/api/controllers/*`)
- Endpoints que recebem `usuario_id` como parâmetro

**Mudanças Necessárias:**
1. Substituir parâmetro `usuario_id` por `loja_id` (ou identificar automaticamente)
2. Atualizar validações de acesso
3. Atualizar filtros de dados

**Riscos:**
- ⚠️ APIs externas podem quebrar se usarem `usuario_id`
- ⚠️ Documentação precisa ser atualizada

---

### **MÓDULO 7: Configurações**

#### **Impacto:** 🟡 **MÉDIO**

**Arquivos Afetados:**
- `modules/vendas/models/Configuracao.php`

**Mudanças Necessárias:**
1. Substituir `usuario_id` por `loja_id`
2. Cada loja tem suas próprias configurações

**Riscos:**
- ⚠️ Configurações existentes precisam ser migradas

---

## 🔄 PLANO DE MIGRAÇÃO (SEM QUEBRAR O SISTEMA)

### **FASE 1: PREPARAÇÃO (Sem Impacto no Sistema Atual)**

#### **1.1. Criar Novas Tabelas**
- ✅ Criar tabela `prest_donos`
- ✅ Criar tabela `prest_lojas`
- ✅ **NÃO** deletar `prest_usuarios` (mantém compatibilidade)

#### **1.2. Criar Novos Models**
- ✅ Criar Model `Dono` (`app\models\Dono`)
- ✅ Criar Model `Loja` (`app\models\Loja`)
- ✅ **NÃO** modificar Model `Usuario` ainda

#### **1.3. Adicionar Campos de Migração**
- ✅ Adicionar `dono_id` em `prest_lojas` (FK para `prest_donos`)
- ✅ Adicionar `loja_id` em todas as 23 tabelas (NULL inicialmente)
- ✅ Adicionar `user_id` em `prest_donos` (FK para `user`)
- ✅ Adicionar `user_id` em `prest_colaboradores` (FK para `user`, NULL)

**Objetivo:** Estrutura pronta, mas sistema continua funcionando com `prest_usuarios`

---

### **FASE 2: MIGRAÇÃO DE DADOS (Backward Compatible)**

#### **2.1. Migrar Donos Existentes**
```sql
-- Para cada prest_usuarios com eh_dono_loja = true:
-- 1. Criar registro em user (se não existir)
-- 2. Criar registro em prest_donos
-- 3. Criar registro em prest_lojas
-- 4. Atualizar prest_usuarios com FK para dono e loja
```

**Script de Migração:**
1. Identificar todos os `prest_usuarios` com `eh_dono_loja = true`
2. Para cada um:
   - Criar `user` (username = email ou CPF, senha = hash_senha atual)
   - Criar `prest_donos` (dados do dono)
   - Criar `prest_lojas` (dados da loja)
   - Atualizar `prest_usuarios` com `dono_id` e `loja_id` (campos novos)

#### **2.2. Migrar Dados das Tabelas**
```sql
-- Para cada tabela com usuario_id:
-- Atualizar loja_id baseado no relacionamento:
--   - Se usuario_id aponta para prest_usuarios com eh_dono_loja = true
--   - Então loja_id = prest_lojas.id correspondente
```

**Script de Migração:**
1. Para cada uma das 23 tabelas:
   - Atualizar `loja_id` baseado no `usuario_id` atual
   - Manter `usuario_id` (para compatibilidade temporária)

#### **2.3. Migrar Colaboradores**
```sql
-- Para cada prest_colaboradores:
-- 1. Identificar loja através de usuario_id (dono)
-- 2. Atualizar loja_id
-- 3. Se colaborador tem login próprio, criar user e atualizar user_id
```

**Objetivo:** Dados migrados, mas sistema ainda usa `prest_usuarios` para compatibilidade

---

### **FASE 3: IMPLEMENTAÇÃO PARALELA (Dual Mode)**

#### **3.1. Implementar Nova Autenticação (Paralela)**
- ✅ Criar `LoginFormNew` que usa `user`
- ✅ Criar endpoint `/auth/login-new` (teste)
- ✅ **Manter** `/auth/login` funcionando (antigo)

#### **3.2. Implementar Novos Controllers (Paralelos)**
- ✅ Criar controllers novos que usam `loja_id`
- ✅ **Manter** controllers antigos funcionando
- ✅ Testar ambos em paralelo

#### **3.3. Feature Flag**
- ✅ Adicionar flag `use_new_structure` em `config/params.php`
- ✅ Sistema pode alternar entre estrutura antiga e nova
- ✅ Permitir testes sem quebrar produção

**Objetivo:** Nova estrutura funcionando em paralelo, sem quebrar a antiga

---

### **FASE 4: TRANSIÇÃO GRADUAL**

#### **4.1. Atualizar Models Gradualmente**
- ✅ Atualizar Model `Usuario` para usar `loja_id` quando disponível
- ✅ Manter fallback para `usuario_id` (compatibilidade)
- ✅ Atualizar queries para preferir `loja_id`, mas aceitar `usuario_id`

#### **4.2. Atualizar Controllers Gradualmente**
- ✅ Atualizar controllers para usar `loja_id`
- ✅ Manter compatibilidade com `usuario_id`
- ✅ Migrar um módulo por vez

#### **4.3. Atualizar Views**
- ✅ Adicionar seletor de loja (se dono tem múltiplas)
- ✅ Atualizar dashboards para mostrar dados da loja ativa

**Objetivo:** Sistema funcionando com nova estrutura, mas mantendo compatibilidade

---

### **FASE 5: LIMPEZA (Após Validação)**

#### **5.1. Remover Compatibilidade**
- ⚠️ **APENAS APÓS VALIDAÇÃO COMPLETA**
- ⚠️ Remover fallback para `usuario_id`
- ⚠️ Remover campos de migração não utilizados
- ⚠️ Deprecar `prest_usuarios` (ou manter apenas para histórico)

#### **5.2. Documentação**
- ✅ Atualizar documentação
- ✅ Atualizar APIs externas
- ✅ Treinar usuários

**Objetivo:** Sistema limpo, apenas com nova estrutura

---

## ✅ VIABILIDADE TÉCNICA

### **Pontos Positivos:**

1. ✅ **Tabela `user` já existe** - Não precisa criar do zero
2. ✅ **Estrutura modular** - Mudanças podem ser feitas por módulo
3. ✅ **PostgreSQL suporta** - UUID, foreign keys, migrations
4. ✅ **Yii2 suporta** - IdentityInterface, relacionamentos
5. ✅ **Dados isolados** - Fácil migrar `usuario_id` → `loja_id`

### **Desafios:**

1. ⚠️ **Volume de dados** - 23 tabelas precisam ser migradas
2. ⚠️ **Tempo de migração** - Pode ser longo dependendo do volume
3. ⚠️ **Risco de quebra** - Se migração falhar, pode afetar produção
4. ⚠️ **Testes extensivos** - Precisa testar todos os módulos
5. ⚠️ **Downtime** - Pode ser necessário para migração completa

### **Mitigações:**

1. ✅ **Migração incremental** - Fazer por fases
2. ✅ **Backup completo** - Antes de qualquer mudança
3. ✅ **Ambiente de teste** - Testar migração completa antes
4. ✅ **Rollback plan** - Plano de reversão se algo der errado
5. ✅ **Feature flags** - Permitir alternar entre estruturas

---

## 📊 ESTIMATIVA DE ESFORÇO

### **Por Fase:**

| Fase | Descrição | Esforço | Risco |
|------|-----------|---------|-------|
| **Fase 1** | Preparação (tabelas, models) | 3-5 dias | 🟢 Baixo |
| **Fase 2** | Migração de dados | 5-7 dias | 🟡 Médio |
| **Fase 3** | Implementação paralela | 10-15 dias | 🟡 Médio |
| **Fase 4** | Transição gradual | 15-20 dias | 🟡 Médio |
| **Fase 5** | Limpeza | 3-5 dias | 🟢 Baixo |
| **TOTAL** | | **36-52 dias** | |

### **Por Módulo:**

| Módulo | Esforço | Prioridade |
|--------|---------|------------|
| Autenticação | 5-7 dias | 🔴 Alta |
| Vendas | 8-10 dias | 🔴 Alta |
| Colaboradores | 3-5 dias | 🔴 Alta |
| Caixa | 2-3 dias | 🟡 Média |
| Contas a Pagar | 2-3 dias | 🟡 Média |
| API | 3-5 dias | 🟡 Média |
| Configurações | 1-2 dias | 🟢 Baixa |
| Outros (12 tabelas) | 5-7 dias | 🟡 Média |

---

## 🎯 RECOMENDAÇÕES

### **1. Abordagem Recomendada:**

✅ **Migração Incremental com Feature Flags**

- Implementar nova estrutura em paralelo
- Testar extensivamente antes de ativar
- Migrar módulo por módulo
- Manter compatibilidade durante transição
- Ativar feature flag apenas após validação completa

### **2. Priorização:**

1. **🔴 ALTA PRIORIDADE:**
   - Autenticação (base de tudo)
   - Vendas (módulo principal)
   - Colaboradores (lógica complexa)

2. **🟡 MÉDIA PRIORIDADE:**
   - Caixa
   - Contas a Pagar
   - API
   - Configurações

3. **🟢 BAIXA PRIORIDADE:**
   - Outras tabelas menores
   - Limpeza final

### **3. Riscos a Mitigar:**

- ✅ **Backup completo** antes de iniciar
- ✅ **Ambiente de teste** idêntico à produção
- ✅ **Migração de dados** testada com dados reais
- ✅ **Rollback plan** documentado
- ✅ **Comunicação** com usuários sobre mudanças

---

## 📝 CHECKLIST DE IMPLEMENTAÇÃO

### **Pré-requisitos:**
- [ ] Backup completo do banco de dados
- [ ] Ambiente de teste configurado
- [ ] Documentação da estrutura atual completa
- [ ] Plano de rollback definido

### **Fase 1 - Preparação:**
- [ ] Criar tabela `prest_donos`
- [ ] Criar tabela `prest_lojas`
- [ ] Criar Model `Dono`
- [ ] Criar Model `Loja`
- [ ] Adicionar campos de migração nas tabelas existentes

### **Fase 2 - Migração:**
- [ ] Script de migração de donos
- [ ] Script de migração de lojas
- [ ] Script de migração de dados (23 tabelas)
- [ ] Script de migração de colaboradores
- [ ] Validação dos dados migrados

### **Fase 3 - Implementação:**
- [ ] Nova autenticação (`LoginFormNew`)
- [ ] Atualizar `identityClass` (com feature flag)
- [ ] Atualizar controllers (com compatibilidade)
- [ ] Atualizar models (com fallback)
- [ ] Implementar seletor de loja

### **Fase 4 - Transição:**
- [ ] Ativar feature flag em ambiente de teste
- [ ] Testes extensivos
- [ ] Correção de bugs
- [ ] Ativar feature flag em produção (gradual)
- [ ] Monitorar erros

### **Fase 5 - Limpeza:**
- [ ] Remover compatibilidade antiga
- [ ] Remover campos não utilizados
- [ ] Atualizar documentação
- [ ] Treinar usuários

---

## 🚨 ALERTAS IMPORTANTES

### **⚠️ NÃO FAZER:**

1. ❌ **NÃO deletar `prest_usuarios`** - Manter para histórico e compatibilidade
2. ❌ **NÃO fazer migração em produção** sem testar em ambiente idêntico
3. ❌ **NÃO remover compatibilidade** antes de validar tudo
4. ❌ **NÃO fazer mudanças** sem backup completo
5. ❌ **NÃO ativar feature flag** sem testes extensivos

### **✅ FAZER:**

1. ✅ **Fazer backup** antes de qualquer mudança
2. ✅ **Testar em ambiente** idêntico à produção
3. ✅ **Migrar incrementalmente** - um módulo por vez
4. ✅ **Manter compatibilidade** durante transição
5. ✅ **Documentar tudo** - cada mudança, cada decisão

---

## 📚 CONCLUSÃO

### **Viabilidade:** ✅ **VIÁVEL**

A migração é tecnicamente viável, mas requer:
- Planejamento cuidadoso
- Testes extensivos
- Migração incremental
- Compatibilidade durante transição

### **Benefícios:**

1. ✅ **Separação clara** entre dono e loja
2. ✅ **Múltiplas lojas** por dono
3. ✅ **Autenticação adequada** usando `user`
4. ✅ **Estrutura escalável** para crescimento
5. ✅ **Manutenção facilitada** com responsabilidades claras

### **Próximos Passos:**

1. Revisar este documento
2. Aprovar abordagem proposta
3. Criar ambiente de teste
4. Iniciar Fase 1 (Preparação)

---

**Documento criado em:** 2025-01-27  
**Próxima revisão:** Após aprovação da abordagem

