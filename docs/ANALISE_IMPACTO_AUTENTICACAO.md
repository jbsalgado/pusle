# 📊 Análise de Impacto: Abordagens de Autenticação

## 🔍 Dados Coletados

### **Estatísticas do Sistema:**

| Métrica | Valor |
|---------|-------|
| **Arquivos que usam `prest_usuarios`/`Usuario`** | **33 arquivos** |
| **Ocorrências de `prest_usuarios`/`Usuario`** | **743 referências** |
| **Foreign Keys que referenciam `prest_usuarios.id`** | **43 FKs** |
| **Arquivos que usam `user`/`Users`** | **3 arquivos** |
| **Ocorrências de `user`/`Users`** | **8 referências** |

### **Estrutura Atual de `prest_usuarios`:**

Campos existentes:
- ✅ `id` (UUID) - PRIMARY KEY
- ✅ `nome`, `cpf`, `telefone`, `email`
- ✅ `hash_senha` (VARCHAR 255)
- ✅ `auth_key` (VARCHAR 32)
- ✅ `data_criacao`, `data_atualizacao`
- ✅ Campos de configuração (gateway, API keys, etc.)

---

## 🎯 ABORDAGEM 1: Usar Tabela `user` (MAIOR IMPACTO)

### **Mudanças Necessárias:**

#### **1. Estrutura de Dados:**
- [ ] Adicionar `user_id` (INTEGER, FK) em `prest_usuarios`
- [ ] Adicionar `user_id` (INTEGER, FK) em `prest_colaboradores`
- [ ] Migrar 3 registros de `prest_usuarios` para `user`
- [ ] Criar registros em `user` para colaboradores
- [ ] **Decidir: manter `prest_usuarios.id` como UUID ou mudar para `user.id` (INTEGER)?**

#### **2. Foreign Keys (IMPACTO CRÍTICO):**
- [ ] **43 FKs** referenciam `prest_usuarios.id`
- [ ] Se mudar para `user.id` (INTEGER): **TODAS as 43 FKs precisam ser atualizadas**
- [ ] Se manter `prest_usuarios.id` (UUID): Precisa de FK dupla (`user_id` + `id`)
- [ ] **Risco alto de quebrar integridade referencial**

#### **3. Código (IMPACTO ALTO):**
- [ ] Mudar `identityClass` em `config/web.php`
- [ ] Atualizar `LoginForm` (buscar em `user` ao invés de `prest_usuarios`)
- [ ] Atualizar `SignupForm` (criar em `user` + `prest_usuarios`)
- [ ] Atualizar **33 arquivos** que usam `Usuario::find()` ou `prest_usuarios`
- [ ] Atualizar queries que usam `usuario_id` (pode precisar mudar para `user_id`)
- [ ] Atualizar relacionamentos em modelos

#### **4. Migração de Dados:**
- [ ] Criar registros em `user` para cada `prest_usuarios` existente
- [ ] Mapear UUIDs de `prest_usuarios` para IDs de `user`
- [ ] Atualizar todas as referências
- [ ] **Risco de perda de dados se migração falhar**

### **Impacto Total:**
- 🔴 **ALTO**: ~50+ arquivos a modificar
- 🔴 **ALTO**: 43 FKs a revisar/atualizar
- 🔴 **ALTO**: Migração de dados complexa
- 🔴 **ALTO**: Risco de quebrar funcionalidades existentes
- 🔴 **ALTO**: Tempo estimado: 2-3 dias de trabalho

---

## ✅ ABORDAGEM 2: Adaptar `prest_usuarios` (MENOR IMPACTO)

### **Mudanças Necessárias:**

#### **1. Estrutura de Dados (APENAS ADICIONAR CAMPOS):**
- [ ] Adicionar `username` (VARCHAR 50, UNIQUE) em `prest_usuarios`
- [ ] Adicionar `eh_dono_loja` (BOOLEAN, DEFAULT false) em `prest_usuarios`
- [ ] Adicionar `blocked_at` (TIMESTAMP, NULL) em `prest_usuarios`
- [ ] Adicionar `confirmed_at` (TIMESTAMP, NULL) em `prest_usuarios`
- [ ] **NÃO precisa mudar FKs existentes**
- [ ] **NÃO precisa migrar dados**

#### **2. Foreign Keys:**
- [ ] **NENHUMA mudança necessária**
- [ ] **43 FKs continuam funcionando normalmente**
- [ ] `prest_usuarios.id` continua sendo UUID (sem mudança)

#### **3. Código (IMPACTO BAIXO):**
- [ ] **NÃO precisa mudar `identityClass`** (continua `app\models\Usuario`)
- [ ] **NÃO precisa mudar `LoginForm`** (continua buscando em `prest_usuarios`)
- [ ] Atualizar `LoginForm` para verificar `blocked_at` (1 linha de código)
- [ ] Atualizar `SignupForm` para definir `eh_dono_loja = true` (1 linha)
- [ ] Atualizar modelo `Usuario` para incluir novos campos (adicionar propriedades)
- [ ] Atualizar gerenciamento de usuários para criar colaboradores em `prest_usuarios`
- [ ] **33 arquivos que usam `Usuario` continuam funcionando sem mudanças**

#### **4. Migração de Dados:**
- [ ] Atualizar registros existentes: `UPDATE prest_usuarios SET eh_dono_loja = true`
- [ ] Gerar `username` para registros existentes: `UPDATE prest_usuarios SET username = email`
- [ ] **Sem risco de perda de dados**

### **Impacto Total:**
- 🟢 **BAIXO**: ~5 arquivos a modificar
- 🟢 **BAIXO**: 0 FKs a mudar
- 🟢 **BAIXO**: Migração simples (apenas UPDATEs)
- 🟢 **BAIXO**: Código existente continua funcionando
- 🟢 **BAIXO**: Tempo estimado: 2-3 horas de trabalho

---

## 📊 COMPARAÇÃO LADO A LADO

| Aspecto | Abordagem 1 (`user`) | Abordagem 2 (`prest_usuarios`) |
|---------|---------------------|--------------------------------|
| **Arquivos a modificar** | ~50+ arquivos | ~5 arquivos |
| **FKs a modificar** | 43 FKs (revisar todas) | 0 FKs |
| **Mudança identityClass** | ✅ Sim | ❌ Não |
| **Mudança LoginForm** | ✅ Sim | ❌ Não (apenas adicionar verificação) |
| **Migração de dados** | ✅ Complexa (UUID → INTEGER) | ✅ Simples (UPDATEs) |
| **Risco de quebrar código** | 🔴 Alto | 🟢 Baixo |
| **Compatibilidade** | ❌ Baixa | ✅ Alta |
| **Tempo de implementação** | 🔴 2-3 dias | 🟢 2-3 horas |
| **Manutenção futura** | 🔴 Mais complexa | 🟢 Mais simples |

---

## ✅ RECOMENDAÇÃO: Abordagem 2 (Adaptar `prest_usuarios`)

### **Por que tem MENOR IMPACTO:**

1. ✅ **Mantém estrutura atual**: Não muda FKs, não muda relacionamentos
2. ✅ **Código existente funciona**: 33 arquivos continuam funcionando
3. ✅ **Apenas adiciona campos**: Não remove nem modifica campos existentes
4. ✅ **Sem migração complexa**: Apenas UPDATEs simples
5. ✅ **Menor risco**: Não mexe em estrutura crítica

### **Estrutura Proposta:**

```sql
prest_usuarios (
    -- Campos existentes (mantidos)
    id UUID PRIMARY KEY,
    nome VARCHAR(100),
    email VARCHAR(100),
    cpf VARCHAR(20),
    telefone VARCHAR(30),
    hash_senha VARCHAR(255),
    auth_key VARCHAR(32),
    
    -- Novos campos (adicionar)
    username VARCHAR(50) UNIQUE,        -- Para login (pode ser email ou CPF)
    eh_dono_loja BOOLEAN DEFAULT false, -- Flag: true = dono, false = colaborador
    blocked_at TIMESTAMP,               -- NULL = ativo, não NULL = bloqueado
    confirmed_at TIMESTAMP,             -- NULL = não confirmado, não NULL = confirmado
    
    -- Outros campos existentes...
)
```

### **Como Funcionaria:**

#### **Dono da Loja:**
```
prest_usuarios (
    id: uuid-1,
    username: "joao@loja.com",
    email: "joao@loja.com",
    eh_dono_loja: true,
    blocked_at: NULL,
    confirmed_at: 2024-01-01
)
```

#### **Colaborador (com login próprio):**
```
prest_usuarios (
    id: uuid-2,
    username: "maria",
    email: "maria@loja.com",
    eh_dono_loja: false,
    blocked_at: NULL,
    confirmed_at: 2024-01-01
)

prest_colaboradores (
    id: uuid-3,
    usuario_id: uuid-1,  -- FK para dono (para dados da loja)
    prest_usuario_id: uuid-2  -- FK para próprio login (NOVO campo opcional)
)
```

#### **Colaborador (sem login próprio - usa login do dono):**
```
prest_usuarios (
    id: uuid-1,
    username: "joao@loja.com",
    eh_dono_loja: true
)

prest_colaboradores (
    id: uuid-3,
    usuario_id: uuid-1,  -- FK para dono
    prest_usuario_id: NULL  -- Sem login próprio
)
```

---

## 🎯 CONCLUSÃO

### **Abordagem 2 tem 90% MENOS IMPACTO:**

| Métrica | Abordagem 1 | Abordagem 2 | Redução |
|---------|-------------|-------------|---------|
| Arquivos | 50+ | 5 | **90% menos** |
| FKs | 43 | 0 | **100% menos** |
| Tempo | 2-3 dias | 2-3 horas | **90% menos** |
| Risco | Alto | Baixo | **Muito menor** |

### **Recomendação Final:**
✅ **Usar Abordagem 2** - Adaptar `prest_usuarios` com flag `eh_dono_loja` e campos de controle.

---

**Data:** 2024-12-08
**Status:** ✅ ANÁLISE COMPLETA - Recomendação: Abordagem 2

