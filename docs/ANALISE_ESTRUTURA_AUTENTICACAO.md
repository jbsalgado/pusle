# 🔍 Análise: Estrutura de Autenticação do Sistema

## 📋 Situação Atual

### **Tabelas Encontradas:**

1. **`user`** (Tabela de Autenticação Central)
   - `id` (integer, auto-increment)
   - `username` (VARCHAR 25, único)
   - `email` (VARCHAR 255, único)
   - `password_hash` (VARCHAR 60)
   - `auth_key` (VARCHAR 32)
   - `blocked_at` (integer) - Para bloquear usuário
   - `confirmed_at` (integer)
   - `created_at`, `updated_at` (integer)
   - `last_login_at` (integer)

2. **`prest_usuarios`** (Dados do Dono da Loja)
   - `id` (UUID)
   - `nome`, `cpf`, `telefone`, `email`
   - `hash_senha` (VARCHAR 255)
   - `auth_key` (VARCHAR 32)
   - **NÃO tem FK para `user`**

3. **`prest_colaboradores`** (Funcionários)
   - `id` (UUID)
   - `usuario_id` (FK para `prest_usuarios`)
   - `nome_completo`, `cpf`, `email`
   - `eh_vendedor`, `eh_cobrador`, `eh_administrador`
   - `ativo` (boolean)
   - **NÃO tem FK para `user`**

---

## ⚠️ PROBLEMA IDENTIFICADO

### **Situação Atual:**
- ✅ Tabela `user` existe no banco
- ✅ Modelo `Users` existe (`app\models\Users`)
- ❌ **Sistema NÃO usa a tabela `user` para autenticação**
- ❌ **Sistema usa `prest_usuarios` diretamente** (`identityClass => 'app\models\Usuario'`)
- ❌ **Não há relacionamento entre `user` e `prest_usuarios`**
- ❌ **Não há relacionamento entre `user` e `prest_colaboradores`**

### **Como o Sistema Funciona Atualmente:**
```
Login → Busca em prest_usuarios (CPF/Email + Senha)
     → Se encontrado, cria sessão
     → NÃO verifica tabela user
```

### **Como DEVERIA Funcionar (segundo sua explicação):**
```
Login → Busca em user (username/email + password_hash)
     → Verifica se blocked_at é NULL (não bloqueado)
     → Verifica se confirmed_at não é NULL (confirmado)
     → Se válido, busca dados complementares em:
        - prest_usuarios (se for dono)
        - prest_colaboradores (se for funcionário)
     → Cria sessão
```

---

## 🔗 ESTRUTURA CORRETA (Como Deveria Ser)

### **Relacionamento Proposto:**

```
user (Tabela Central de Autenticação)
    │
    ├── prest_usuarios (Dados do Dono)
    │   └── user_id (FK para user.id) ← FALTA ESTE CAMPO
    │
    └── prest_colaboradores (Funcionários)
        └── user_id (FK para user.id) ← FALTA ESTE CAMPO
```

### **Fluxo Correto:**

1. **Cada pessoa que acessa o sistema tem registro em `user`:**
   - Dono da loja → `user` + `prest_usuarios`
   - Colaborador → `user` + `prest_colaboradores`
   - Cliente → `user` + `prest_clientes` (ou não, se usar outra autenticação)

2. **Autenticação sempre verifica `user` primeiro:**
   - Verifica `username` ou `email`
   - Valida `password_hash`
   - Verifica `blocked_at` (se não NULL, usuário bloqueado)
   - Verifica `confirmed_at` (se NULL, usuário não confirmado)

3. **Após autenticação, busca dados complementares:**
   - Se existe `prest_usuarios.user_id = user.id` → É dono
   - Se existe `prest_colaboradores.user_id = user.id` → É colaborador
   - Aplica permissões baseadas no tipo

---

## ❓ PERGUNTAS PARA ESCLARECER

### **1. Relacionamento entre tabelas:**
- `prest_usuarios` deve ter campo `user_id` (FK para `user.id`)?
- `prest_colaboradores` deve ter campo `user_id` (FK para `user.id`)?
- Ou ambos podem ter `user_id` (um usuário pode ser dono E colaborador)?

### **2. Autenticação:**
- O sistema DEVE verificar `user` antes de permitir login?
- Se não existir em `user`, o login DEVE ser negado?
- O campo `blocked_at` em `user` deve bloquear o acesso?

### **3. Colaboradores:**
- Cada colaborador DEVE ter seu próprio registro em `user`?
- Cada colaborador tem seu próprio `username` e `password_hash`?
- Ou colaboradores compartilham login do dono?

### **4. Migração:**
- Existem dados antigos em `prest_usuarios` que precisam ser migrados para `user`?
- Como relacionar registros existentes?

---

## 🔧 O QUE PRECISA SER FEITO

### **1. Estrutura de Dados:**
- [ ] Adicionar campo `user_id` em `prest_usuarios` (FK para `user.id`)
- [ ] Adicionar campo `user_id` em `prest_colaboradores` (FK para `user.id`)
- [ ] Criar índices e constraints

### **2. Autenticação:**
- [ ] Mudar `identityClass` de `app\models\Usuario` para `app\models\Users`
- [ ] Atualizar `LoginForm` para buscar em `user` primeiro
- [ ] Verificar `blocked_at` e `confirmed_at` antes de permitir login
- [ ] Após login, buscar dados complementares em `prest_usuarios` ou `prest_colaboradores`

### **3. Gerenciamento de Usuários:**
- [ ] Ao criar Usuario, criar também registro em `user`
- [ ] Ao criar Colaborador, criar também registro em `user`
- [ ] Bloquear usuário = atualizar `blocked_at` em `user`
- [ ] Mudar senha = atualizar `password_hash` em `user`

---

## 📊 COMPARAÇÃO: Atual vs Correto

| Aspecto | Atual (Incorreto) | Correto (Proposto) |
|---------|-------------------|-------------------|
| **Tabela de Auth** | `prest_usuarios` | `user` |
| **IdentityClass** | `app\models\Usuario` | `app\models\Users` |
| **Login Colaborador** | Usa login do dono | Login próprio em `user` |
| **Bloqueio** | `prest_colaboradores.ativo` | `user.blocked_at` |
| **Senha** | `prest_usuarios.hash_senha` | `user.password_hash` |
| **Relacionamento** | Não há FK para `user` | FK `user_id` em ambas |

---

## ⚠️ IMPACTO

### **Se o sistema NÃO verifica `user`:**
- ❌ Qualquer registro em `prest_usuarios` pode fazer login
- ❌ Não há controle centralizado de bloqueio
- ❌ Colaboradores não têm login próprio
- ❌ Não há verificação de confirmação de email

### **Se o sistema DEVE verificar `user`:**
- ✅ Controle centralizado de autenticação
- ✅ Cada usuário tem login próprio
- ✅ Bloqueio unificado via `blocked_at`
- ✅ Confirmação de email via `confirmed_at`
- ✅ Histórico de login via `last_login_at`

---

**Data:** 2024-12-08
**Status:** ⚠️ ANÁLISE - Aguardando confirmação

