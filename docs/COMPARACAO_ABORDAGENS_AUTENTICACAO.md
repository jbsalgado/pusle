# 🔍 Comparação: Abordagens para Autenticação

## 📋 Duas Abordagens Possíveis

### **Abordagem 1: Usar Tabela `user` Existente**
- Adicionar `user_id` em `prest_usuarios` e `prest_colaboradores`
- Mudar `identityClass` para `app\models\Users`
- Autenticação via tabela `user`

### **Abordagem 2: Adaptar `prest_usuarios` (SUGERIDA)**
- Adicionar flag `eh_dono_loja` em `prest_usuarios`
- Permitir que colaboradores também tenham registro em `prest_usuarios`
- Manter autenticação via `prest_usuarios`

---

## 📊 ANÁLISE DE IMPACTO

### **Estatísticas do Código:**

| Métrica | `prest_usuarios`/`Usuario` | `user`/`Users` |
|---------|---------------------------|----------------|
| **Arquivos PHP que usam** | **43 arquivos** | **3 arquivos** |
| **Referências no código** | **743 ocorrências** | **8 ocorrências** |
| **Tabelas com FK para** | **~20+ tabelas** | **4 tabelas** |
| **Uso atual** | ✅ **ATIVO** | ❌ **NÃO USADO** |

---

## 🎯 ABORDAGEM 1: Usar Tabela `user` (MAIOR IMPACTO)

### **O que precisa mudar:**

#### **1. Estrutura de Dados:**
- [ ] Adicionar `user_id` em `prest_usuarios` (FK para `user.id`)
- [ ] Adicionar `user_id` em `prest_colaboradores` (FK para `user.id`)
- [ ] Migrar dados existentes de `prest_usuarios` para `user`
- [ ] Criar registros em `user` para colaboradores existentes

#### **2. Código (IMPACTO ALTO):**
- [ ] Mudar `identityClass` em `config/web.php` (1 arquivo)
- [ ] Atualizar `LoginForm` para buscar em `user` (1 arquivo)
- [ ] Atualizar `SignupForm` para criar em `user` (1 arquivo)
- [ ] Atualizar **43 arquivos** que usam `Usuario` para buscar dados complementares
- [ ] Atualizar **~20+ tabelas** que referenciam `prest_usuarios.id` (pode precisar mudar para `user.id` ou manter FK dupla)

#### **3. Relacionamentos:**
- [ ] Decidir se `prest_usuarios.id` continua sendo UUID ou muda para `user.id` (integer)
- [ ] Atualizar todas as FKs que referenciam `prest_usuarios.id`
- [ ] Atualizar queries que usam `usuario_id` em outras tabelas

### **Impacto:**
- 🔴 **ALTO**: Muda estrutura fundamental de autenticação
- 🔴 **ALTO**: Requer migração de dados
- 🔴 **ALTO**: Pode quebrar código existente
- 🔴 **ALTO**: Requer atualização de múltiplas tabelas e FKs

---

## ✅ ABORDAGEM 2: Adaptar `prest_usuarios` (MENOR IMPACTO)

### **O que precisa mudar:**

#### **1. Estrutura de Dados:**
- [ ] Adicionar campo `eh_dono_loja` (BOOLEAN) em `prest_usuarios`
- [ ] Adicionar campo `username` (VARCHAR, único) em `prest_usuarios` (se não existir)
- [ ] Adicionar campo `blocked_at` (TIMESTAMP) em `prest_usuarios` (se não existir)
- [ ] Adicionar campo `confirmed_at` (TIMESTAMP) em `prest_usuarios` (se não existir)
- [ ] **NÃO precisa mudar FKs existentes**
- [ ] **NÃO precisa migrar dados para outra tabela**

#### **2. Código (IMPACTO BAIXO):**
- [ ] **NÃO precisa mudar `identityClass`** (continua `app\models\Usuario`)
- [ ] **NÃO precisa mudar `LoginForm`** (continua buscando em `prest_usuarios`)
- [ ] Atualizar `SignupForm` para definir `eh_dono_loja = true` (1 arquivo)
- [ ] Atualizar gerenciamento de usuários para criar registros em `prest_usuarios` para colaboradores (1 arquivo)
- [ ] Adicionar verificação de `blocked_at` no login (1 arquivo)
- [ ] **43 arquivos que usam `Usuario` continuam funcionando normalmente**

#### **3. Relacionamentos:**
- [ ] **NÃO precisa mudar FKs existentes**
- [ ] **NÃO precisa mudar `usuario_id` em outras tabelas**
- [ ] Colaboradores podem ter `usuario_id` apontando para o dono OU ter seu próprio registro em `prest_usuarios`

### **Impacto:**
- 🟢 **BAIXO**: Mantém estrutura atual
- 🟢 **BAIXO**: Não requer migração de dados
- 🟢 **BAIXO**: Código existente continua funcionando
- 🟢 **BAIXO**: Não precisa atualizar FKs

---

## 🔄 ESTRUTURA PROPOSTA (Abordagem 2)

### **`prest_usuarios` Adaptada:**

```sql
prest_usuarios (
    id UUID PRIMARY KEY,
    username VARCHAR(50) UNIQUE,        -- NOVO: Para login
    email VARCHAR(100),
    cpf VARCHAR(20),
    telefone VARCHAR(30),
    hash_senha VARCHAR(255),            -- JÁ EXISTE
    auth_key VARCHAR(32),               -- JÁ EXISTE
    eh_dono_loja BOOLEAN DEFAULT false, -- NOVO: Flag para identificar dono
    blocked_at TIMESTAMP,               -- NOVO: Para bloquear usuário
    confirmed_at TIMESTAMP,             -- NOVO: Para confirmar email
    nome VARCHAR(100),
    -- ... outros campos existentes
)
```

### **`prest_colaboradores` Adaptada:**

```sql
prest_colaboradores (
    id UUID PRIMARY KEY,
    usuario_id UUID,                    -- Pode apontar para dono OU para próprio registro
    prest_usuario_id UUID,              -- NOVO: FK para prest_usuarios (se colaborador tem login próprio)
    nome_completo VARCHAR(150),
    eh_vendedor BOOLEAN,
    eh_cobrador BOOLEAN,
    eh_administrador BOOLEAN,
    ativo BOOLEAN,
    -- ... outros campos existentes
)
```

### **Dois Cenários Possíveis:**

#### **Cenário A: Colaborador com Login Próprio**
```
prest_usuarios (id: uuid-1, username: "maria", eh_dono_loja: false)
    └── prest_colaboradores (prest_usuario_id: uuid-1, usuario_id: uuid-dono)
```

#### **Cenário B: Colaborador sem Login (usa login do dono)**
```
prest_usuarios (id: uuid-dono, username: "joao", eh_dono_loja: true)
    └── prest_colaboradores (usuario_id: uuid-dono, prest_usuario_id: NULL)
```

---

## 📊 COMPARAÇÃO DETALHADA

| Aspecto | Abordagem 1 (user) | Abordagem 2 (prest_usuarios) |
|---------|-------------------|------------------------------|
| **Arquivos a modificar** | ~50+ arquivos | ~5 arquivos |
| **Tabelas a modificar** | 2 tabelas + FKs | 1 tabela (adicionar campos) |
| **Migração de dados** | ✅ Necessária | ❌ Não necessária |
| **Mudança identityClass** | ✅ Sim | ❌ Não |
| **Mudança LoginForm** | ✅ Sim | ❌ Não |
| **Risco de quebrar código** | 🔴 Alto | 🟢 Baixo |
| **Tempo de implementação** | 🔴 Alto | 🟢 Baixo |
| **Compatibilidade com código atual** | ❌ Baixa | ✅ Alta |

---

## ✅ RECOMENDAÇÃO: Abordagem 2 (Adaptar `prest_usuarios`)

### **Vantagens:**
1. ✅ **Menor impacto**: Apenas adiciona campos, não muda estrutura
2. ✅ **Compatibilidade**: Código existente continua funcionando
3. ✅ **Sem migração**: Dados atuais permanecem válidos
4. ✅ **Flexibilidade**: Permite colaboradores com ou sem login próprio
5. ✅ **Simplicidade**: Mantém lógica atual de autenticação

### **Mudanças Necessárias:**

#### **1. SQL (Adicionar campos):**
```sql
-- Adicionar campos em prest_usuarios
ALTER TABLE prest_usuarios 
ADD COLUMN IF NOT EXISTS username VARCHAR(50) UNIQUE,
ADD COLUMN IF NOT EXISTS eh_dono_loja BOOLEAN DEFAULT false,
ADD COLUMN IF NOT EXISTS blocked_at TIMESTAMP,
ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP;

-- Atualizar registros existentes
UPDATE prest_usuarios SET eh_dono_loja = true WHERE eh_dono_loja IS NULL;
UPDATE prest_usuarios SET username = email WHERE username IS NULL;
```

#### **2. Código (Mínimo):**
- Atualizar modelo `Usuario` para incluir novos campos
- Atualizar `SignupForm` para definir `eh_dono_loja = true`
- Atualizar `LoginForm` para verificar `blocked_at`
- Atualizar gerenciamento de usuários para criar colaboradores em `prest_usuarios`

#### **3. Views (Mínimo):**
- Adicionar campo `eh_dono_loja` nos formulários
- Adicionar campo `blocked_at` na listagem

---

## 🎯 CONCLUSÃO

### **Abordagem 2 (Adaptar `prest_usuarios`) tem:**
- ✅ **90% menos impacto** que Abordagem 1
- ✅ **Compatibilidade total** com código existente
- ✅ **Implementação mais rápida**
- ✅ **Menor risco de quebrar funcionalidades**

### **Recomendação Final:**
**Usar Abordagem 2** - Adaptar `prest_usuarios` com flag `eh_dono_loja` e campos de controle (`blocked_at`, `confirmed_at`, `username`).

---

**Data:** 2024-12-08
**Status:** ✅ RECOMENDAÇÃO FINAL

