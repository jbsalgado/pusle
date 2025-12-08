# ✅ Implementação: Abordagem 2 - Adaptar `prest_usuarios`

## 📋 Resumo

Implementada a **Abordagem 2** para autenticação, adaptando a tabela `prest_usuarios` para ter as mesmas finalidades de `user`, permitindo que todos (donos e colaboradores) tenham seu próprio usuário e senha, com flag `eh_dono_loja` para identificar o dono da loja.

---

## 🎯 Objetivo

- ✅ Cada usuário (dono ou colaborador) tem seu próprio login em `prest_usuarios`
- ✅ Flag `eh_dono_loja` identifica o dono da loja
- ✅ Controle de bloqueio via `blocked_at`
- ✅ Controle de confirmação via `confirmed_at`
- ✅ **Mínimo impacto** no código existente (apenas adiciona campos)

---

## 📝 Mudanças Realizadas

### **1. Estrutura de Dados (SQL Migration)**

**Arquivo:** `sql/postgres/011_adaptar_prest_usuarios_autenticacao.sql`

**Campos adicionados em `prest_usuarios`:**
- `username` (VARCHAR 50, UNIQUE) - Nome de usuário para login
- `eh_dono_loja` (BOOLEAN, DEFAULT false) - Flag: true = dono, false = colaborador
- `blocked_at` (TIMESTAMP, NULL) - NULL = ativo, não NULL = bloqueado
- `confirmed_at` (TIMESTAMP, NULL) - NULL = não confirmado, não NULL = confirmado

**Índices criados:**
- `idx_prest_usuarios_username` - Para melhorar busca por username
- `idx_prest_usuarios_eh_dono_loja` - Para melhorar busca por tipo

**Migração automática:**
- Registros existentes recebem `eh_dono_loja = true`
- `username` é gerado automaticamente a partir de `email` ou `cpf`
- `confirmed_at` é definido como `data_criacao` para registros existentes

---

### **2. Modelo Usuario**

**Arquivo:** `models/Usuario.php`

**Mudanças:**
- ✅ Adicionados campos `username`, `eh_dono_loja`, `blocked_at`, `confirmed_at` em `rules()`
- ✅ Adicionados labels para novos campos em `attributeLabels()`
- ✅ Atualizado `findByLogin()` para buscar também por `username`
- ✅ Novos métodos:
  - `isDonoLoja()` - Verifica se é dono da loja
  - `isBlocked()` - Verifica se está bloqueado
  - `isConfirmed()` - Verifica se está confirmado
  - `bloquear()` - Bloqueia o usuário
  - `desbloquear()` - Desbloqueia o usuário
  - `confirmar()` - Confirma o email

---

### **3. LoginForm**

**Arquivo:** `models/LoginForm.php`

**Mudanças:**
- ✅ Atualizado `validatePassword()` para verificar:
  - Se usuário está bloqueado (`blocked_at` não NULL)
  - Se usuário está confirmado (opcional, comentado por padrão)
  - Validação de senha

**Mensagens de erro:**
- "Usuário não encontrado."
- "Usuário bloqueado. Entre em contato com o administrador."
- "CPF/E-mail ou senha incorretos."

---

### **4. SignupForm**

**Arquivo:** `models/SignupForm.php`

**Mudanças:**
- ✅ Gera `username` automaticamente (usa `email` ou `cpf`)
- ✅ Define `eh_dono_loja = true` (cadastro via signup sempre é dono)
- ✅ Define `confirmed_at` automaticamente (pode mudar se implementar confirmação de email)

---

### **5. UsuarioController**

**Arquivo:** `modules/vendas/controllers/UsuarioController.php`

**Mudanças:**
- ✅ `actionCreate()`:
  - Define `eh_dono_loja = false` por padrão (será colaborador)
  - Gera `username` automaticamente se não fornecido
  - Permite definir `eh_dono_loja` via POST
  - Define `confirmed_at` automaticamente

- ✅ `actionBloquear()`:
  - Usa `$model->bloquear()` (atualiza `blocked_at`)
  - Não usa mais `colaborador->ativo`

- ✅ `actionAtivar()`:
  - Usa `$model->desbloquear()` (remove `blocked_at`)
  - Não usa mais `colaborador->ativo`

- ✅ `actionIndex()`:
  - Adicionado filtro por `eh_dono_loja`
  - Adicionado filtro por `bloqueado` (baseado em `blocked_at`)
  - Busca também por `username`

---

### **6. Views**

#### **6.1. index.php**

**Arquivo:** `modules/vendas/views/usuario/index.php`

**Mudanças:**
- ✅ Mostra `username` e `email` na coluna "Usuário / Email"
- ✅ Mostra se é "Dono da Loja" ou "Colaborador"
- ✅ Status baseado em `blocked_at` (não mais `colaborador->ativo`)
- ✅ Mostra badge "Não confirmado" se `confirmed_at` é NULL
- ✅ Filtros adicionados:
  - Tipo (Dono da Loja / Colaborador)
  - Status (Ativo / Bloqueado)
- ✅ Ações atualizadas:
  - Bloquear/Ativar baseado em `blocked_at`
  - Criar Colaborador apenas se não for dono e não tiver colaborador

#### **6.2. _form.php**

**Arquivo:** `modules/vendas/views/usuario/_form.php`

**Mudanças:**
- ✅ Adicionado campo `username` (obrigatório)
- ✅ Adicionado checkbox `eh_dono_loja` (para definir se é dono)

---

## 🔄 Como Funciona Agora

### **Dono da Loja:**
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

### **Colaborador (com login próprio):**
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
    prest_usuario_id: uuid-2  -- FK para próprio login (opcional)
)
```

### **Fluxo de Login:**
1. Usuário informa `username`, `email` ou `cpf` + senha
2. Sistema busca em `prest_usuarios`
3. Verifica se `blocked_at` é NULL (não bloqueado)
4. Verifica se `confirmed_at` não é NULL (confirmado - opcional)
5. Valida senha
6. Cria sessão Yii2
7. Acesso baseado em `eh_dono_loja` e permissões de colaborador

---

## 🚀 Como Executar

### **1. Execute a Migration SQL:**
```bash
psql -U postgres -d pulse -f sql/postgres/011_adaptar_prest_usuarios_autenticacao.sql
```

### **2. Verifique se os campos foram adicionados:**
```sql
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'prest_usuarios' 
AND column_name IN ('username', 'eh_dono_loja', 'blocked_at', 'confirmed_at');
```

### **3. Teste o sistema:**
- ✅ Acesse `/vendas/usuario` para gerenciar usuários
- ✅ Crie um novo usuário (será colaborador por padrão)
- ✅ Marque checkbox "É dono da loja" para criar dono
- ✅ Teste bloqueio/desbloqueio
- ✅ Teste login com `username`, `email` ou `cpf`

---

## ✅ Benefícios

1. ✅ **Mínimo impacto**: Apenas adiciona campos, não remove nada
2. ✅ **Compatibilidade**: Código existente continua funcionando
3. ✅ **Flexibilidade**: Permite colaboradores com ou sem login próprio
4. ✅ **Controle centralizado**: Bloqueio via `blocked_at` em `prest_usuarios`
5. ✅ **Sem migração complexa**: Apenas UPDATEs simples

---

## 📊 Comparação: Antes vs Depois

| Aspecto | Antes | Depois |
|---------|-------|--------|
| **Login Colaborador** | Usa login do dono | Login próprio em `prest_usuarios` |
| **Bloqueio** | `colaborador->ativo` | `prest_usuarios->blocked_at` |
| **Identificação Dono** | Implícito (não havia flag) | `eh_dono_loja = true` |
| **Username** | Não existia | Campo `username` único |
| **Confirmação** | Não existia | Campo `confirmed_at` |

---

## ⚠️ Observações

1. **Colaboradores sem login próprio**: Ainda é possível criar colaborador sem registro em `prest_usuarios` (usando apenas `prest_colaboradores`), mas isso não permitirá login próprio.

2. **Migração de dados existentes**: A migration define automaticamente:
   - `eh_dono_loja = true` para todos os registros existentes
   - `username = email` (ou `cpf` se email não existir)
   - `confirmed_at = data_criacao`

3. **Confirmação de email**: Por padrão, `confirmed_at` é definido automaticamente. Se quiser implementar confirmação de email, descomente a verificação em `LoginForm::validatePassword()`.

---

## 🔧 Próximos Passos (Opcional)

1. Implementar confirmação de email (enviar email com link de confirmação)
2. Adicionar campo `prest_usuario_id` em `prest_colaboradores` para relacionar colaborador com seu login
3. Criar interface para colaboradores se cadastrarem (com aprovação do dono)
4. Adicionar histórico de bloqueios/desbloqueios

---

**Data:** 2024-12-08
**Status:** ✅ IMPLEMENTADO

