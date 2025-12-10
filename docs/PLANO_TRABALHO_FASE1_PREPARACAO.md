# 📋 Plano de Trabalho - Fase 1: Preparação

**Data de Início:** 2025-01-27  
**Fase:** 1 - Preparação  
**Objetivo:** Adicionar campos necessários sem quebrar sistema  
**Duração Estimada:** 1 semana  
**Impacto:** Zero (campos NULL, sistema continua funcionando)

---

## 🎯 OBJETIVO DA FASE 1

Adicionar os campos estratégicos nas tabelas existentes para permitir:
1. Múltiplas lojas por dono (`dono_id`, `eh_loja`)
2. Autenticação adequada (`user_id`)
3. Separação loja vs. dono (`loja_id` nas 23 tabelas)

**Importante:** Todos os campos serão NULL inicialmente, então o sistema atual continua funcionando normalmente.

---

## ✅ CHECKLIST DE PREPARAÇÃO

### **Pré-requisitos (Antes de Começar):**

- [ ] **Backup completo do banco de dados**
  - Fazer dump completo antes de qualquer alteração
  - Guardar backup em local seguro
  - Testar restauração do backup (validar que funciona)

- [ ] **Ambiente de desenvolvimento/teste configurado**
  - Banco de dados de teste idêntico à produção
  - Ou usar staging se disponível
  - **NÃO fazer alterações direto em produção**

- [ ] **Documentação da estrutura atual**
  - Listar todas as tabelas com `usuario_id`
  - Documentar relacionamentos atuais
  - Anotar queries críticas que usam `usuario_id`

- [ ] **Plano de rollback**
  - Script para reverter migrations se necessário
  - Procedimento de restauração de backup
  - Documentar como desabilitar feature flags

---

## 📝 PASSO 1: ANÁLISE E DOCUMENTAÇÃO

### **1.1. Listar Todas as Tabelas com `usuario_id`**

**Objetivo:** Ter certeza de quais tabelas precisam do campo `loja_id`

**Ação:**
1. Executar query SQL para listar todas as tabelas com coluna `usuario_id`:
```sql
SELECT 
    table_name,
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'public'
    AND column_name = 'usuario_id'
ORDER BY table_name;
```

2. Documentar resultado em arquivo (ex: `tabelas_com_usuario_id.txt`)

3. Validar que são exatamente 23 tabelas (conforme análise)

**Tempo estimado:** 30 minutos

**Entregável:** Lista completa de tabelas com `usuario_id`

---

### **1.2. Documentar Relacionamentos Atuais**

**Objetivo:** Entender como as FKs estão configuradas atualmente

**Ação:**
1. Executar query para listar todas as foreign keys:
```sql
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name,
    tc.constraint_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
    AND ccu.table_name = 'prest_usuarios'
ORDER BY tc.table_name;
```

2. Documentar resultado

**Tempo estimado:** 30 minutos

**Entregável:** Documento com relacionamentos atuais

---

### **1.3. Identificar Queries Críticas**

**Objetivo:** Saber quais queries precisarão ser atualizadas depois

**Ação:**
1. Buscar no código por padrões:
   - `usuario_id`
   - `Yii::$app->user->id`
   - `->where(['usuario_id' =>`

2. Listar arquivos que usam esses padrões

3. Priorizar por criticidade (módulos principais primeiro)

**Tempo estimado:** 1-2 horas

**Entregável:** Lista de arquivos e queries que precisarão atualização

---

## 📝 PASSO 2: CRIAR MIGRATIONS SQL

### **2.1. Migration: Adicionar Campos em `prest_usuarios`**

**Arquivo:** `sql/postgres/XXX_add_campos_prest_usuarios.sql`

**Campos a adicionar:**
1. `user_id` (INTEGER, NULL, FK para `user.id`)
2. `dono_id` (UUID, NULL, FK para `prest_usuarios.id` - self-reference)
3. `eh_loja` (BOOLEAN, DEFAULT false)

**Estrutura da Migration:**
```sql
-- Migration: Adicionar campos para múltiplas lojas e autenticação
-- Data: 2025-01-27
-- Descrição: Adiciona campos user_id, dono_id e eh_loja em prest_usuarios
--            Todos os campos são NULL inicialmente (backward compatible)

BEGIN;

-- 1. Adicionar user_id (FK para user.id)
ALTER TABLE prest_usuarios 
ADD COLUMN IF NOT EXISTS user_id INTEGER;

-- 2. Adicionar dono_id (FK self-reference para múltiplas lojas)
ALTER TABLE prest_usuarios 
ADD COLUMN IF NOT EXISTS dono_id UUID;

-- 3. Adicionar eh_loja (flag para identificar se é loja)
ALTER TABLE prest_usuarios 
ADD COLUMN IF NOT EXISTS eh_loja BOOLEAN DEFAULT false;

-- 4. Adicionar Foreign Key para user
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_name = 'fk_prest_usuarios_user_id'
    ) THEN
        ALTER TABLE prest_usuarios
        ADD CONSTRAINT fk_prest_usuarios_user_id
        FOREIGN KEY (user_id) REFERENCES "user"(id)
        ON DELETE SET NULL;
    END IF;
END $$;

-- 5. Adicionar Foreign Key self-reference (dono_id)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_name = 'fk_prest_usuarios_dono_id'
    ) THEN
        ALTER TABLE prest_usuarios
        ADD CONSTRAINT fk_prest_usuarios_dono_id
        FOREIGN KEY (dono_id) REFERENCES prest_usuarios(id)
        ON DELETE SET NULL;
    END IF;
END $$;

-- 6. Adicionar índices para performance
CREATE INDEX IF NOT EXISTS idx_prest_usuarios_user_id ON prest_usuarios(user_id);
CREATE INDEX IF NOT EXISTS idx_prest_usuarios_dono_id ON prest_usuarios(dono_id);
CREATE INDEX IF NOT EXISTS idx_prest_usuarios_eh_loja ON prest_usuarios(eh_loja);

-- 7. Comentários nas colunas
COMMENT ON COLUMN prest_usuarios.user_id IS 'FK para user.id - Autenticação centralizada';
COMMENT ON COLUMN prest_usuarios.dono_id IS 'FK self-reference - Identifica o dono principal quando é filial';
COMMENT ON COLUMN prest_usuarios.eh_loja IS 'Flag: true se é uma loja/filial, false se é dono principal';

COMMIT;
```

**Validações:**
- [ ] Migration executa sem erros
- [ ] Campos foram adicionados corretamente
- [ ] Foreign keys foram criadas
- [ ] Índices foram criados
- [ ] Sistema continua funcionando normalmente

**Tempo estimado:** 1 hora (criação + testes)

---

### **2.2. Migration: Adicionar Campo em `prest_colaboradores`**

**Arquivo:** `sql/postgres/XXX_add_user_id_prest_colaboradores.sql`

**Campo a adicionar:**
- `user_id` (INTEGER, NULL, FK para `user.id`)

**Estrutura da Migration:**
```sql
-- Migration: Adicionar user_id em prest_colaboradores
-- Data: 2025-01-27
-- Descrição: Adiciona campo user_id para colaboradores com login próprio

BEGIN;

-- 1. Adicionar user_id
ALTER TABLE prest_colaboradores 
ADD COLUMN IF NOT EXISTS user_id INTEGER;

-- 2. Adicionar Foreign Key
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_name = 'fk_prest_colaboradores_user_id'
    ) THEN
        ALTER TABLE prest_colaboradores
        ADD CONSTRAINT fk_prest_colaboradores_user_id
        FOREIGN KEY (user_id) REFERENCES "user"(id)
        ON DELETE SET NULL;
    END IF;
END $$;

-- 3. Adicionar índice
CREATE INDEX IF NOT EXISTS idx_prest_colaboradores_user_id ON prest_colaboradores(user_id);

-- 4. Comentário
COMMENT ON COLUMN prest_colaboradores.user_id IS 'FK para user.id - Login próprio do colaborador (NULL se usa login do dono)';

COMMIT;
```

**Validações:**
- [ ] Migration executa sem erros
- [ ] Campo foi adicionado
- [ ] Foreign key foi criada
- [ ] Sistema continua funcionando

**Tempo estimado:** 30 minutos

---

### **2.3. Migration: Adicionar `loja_id` nas 23 Tabelas**

**Arquivo:** `sql/postgres/XXX_add_loja_id_todas_tabelas.sql`

**Estratégia:** Criar migration que adiciona `loja_id` em todas as tabelas identificadas no Passo 1.1

**Estrutura da Migration:**
```sql
-- Migration: Adicionar loja_id em todas as tabelas com usuario_id
-- Data: 2025-01-27
-- Descrição: Adiciona campo loja_id (paralelo a usuario_id) para compatibilidade futura

BEGIN;

-- Lista de tabelas (ajustar conforme resultado do Passo 1.1)
-- Exemplo com algumas tabelas principais:

-- 1. prest_produtos
ALTER TABLE prest_produtos 
ADD COLUMN IF NOT EXISTS loja_id UUID;

-- 2. prest_vendas
ALTER TABLE prest_vendas 
ADD COLUMN IF NOT EXISTS loja_id UUID;

-- 3. prest_parcelas
ALTER TABLE prest_parcelas 
ADD COLUMN IF NOT EXISTS loja_id UUID;

-- 4. prest_clientes
ALTER TABLE prest_clientes 
ADD COLUMN IF NOT EXISTS loja_id UUID;

-- 5. prest_caixa
ALTER TABLE prest_caixa 
ADD COLUMN IF NOT EXISTS loja_id UUID;

-- 6. prest_contas_pagar
ALTER TABLE prest_contas_pagar 
ADD COLUMN IF NOT EXISTS loja_id UUID;

-- ... (adicionar todas as 23 tabelas identificadas)

-- Adicionar Foreign Keys (após adicionar todas as colunas)
DO $$
BEGIN
    -- FK para prest_produtos
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_name = 'fk_prest_produtos_loja_id'
    ) THEN
        ALTER TABLE prest_produtos
        ADD CONSTRAINT fk_prest_produtos_loja_id
        FOREIGN KEY (loja_id) REFERENCES prest_usuarios(id)
        ON DELETE SET NULL;
    END IF;
    
    -- ... (repetir para todas as tabelas)
END $$;

-- Adicionar índices
CREATE INDEX IF NOT EXISTS idx_prest_produtos_loja_id ON prest_produtos(loja_id);
CREATE INDEX IF NOT EXISTS idx_prest_vendas_loja_id ON prest_vendas(loja_id);
-- ... (para todas as tabelas)

COMMIT;
```

**Importante:** 
- Criar script que gera automaticamente a migration baseado na lista do Passo 1.1
- Ou criar manualmente, mas validar todas as 23 tabelas

**Validações:**
- [ ] Todas as 23 tabelas receberam o campo `loja_id`
- [ ] Foreign keys foram criadas
- [ ] Índices foram criados
- [ ] Sistema continua funcionando

**Tempo estimado:** 2-3 horas (criação + validação)

---

## 📝 PASSO 3: TESTAR MIGRATIONS

### **3.1. Testar em Ambiente de Desenvolvimento**

**Ações:**
1. Executar migrations uma por uma
2. Verificar logs de erro
3. Validar que campos foram criados:
```sql
-- Verificar campos em prest_usuarios
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'prest_usuarios'
    AND column_name IN ('user_id', 'dono_id', 'eh_loja');

-- Verificar campos em prest_colaboradores
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'prest_colaboradores'
    AND column_name = 'user_id';

-- Verificar loja_id em algumas tabelas principais
SELECT table_name, column_name
FROM information_schema.columns
WHERE column_name = 'loja_id'
ORDER BY table_name;
```

4. Testar que sistema ainda funciona:
   - Login funciona
   - Listagem de produtos funciona
   - Criação de vendas funciona
   - Dashboard carrega

**Tempo estimado:** 1-2 horas

---

### **3.2. Validar Foreign Keys**

**Ações:**
1. Verificar que FKs foram criadas:
```sql
SELECT
    tc.table_name,
    tc.constraint_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
    AND (ccu.table_name = 'prest_usuarios' OR ccu.table_name = 'user')
ORDER BY tc.table_name;
```

2. Testar integridade referencial (se necessário)

**Tempo estimado:** 30 minutos

---

### **3.3. Testar Rollback (Opcional mas Recomendado)**

**Ações:**
1. Criar script de rollback que remove os campos adicionados
2. Testar rollback em ambiente de desenvolvimento
3. Validar que sistema volta ao estado anterior

**Tempo estimado:** 1 hora

---

## 📝 PASSO 4: ATUALIZAR MODELS (Preparação)

### **4.1. Atualizar Model `Usuario`**

**Arquivo:** `models/Usuario.php`

**Ações:**
1. Adicionar propriedades para novos campos:
   - `user_id`
   - `dono_id`
   - `eh_loja`

2. Adicionar nas `rules()` (todos opcionais/NULL):
```php
[['user_id'], 'integer'],
[['dono_id'], 'string'],
[['eh_loja'], 'boolean'],
[['user_id', 'dono_id'], 'default', 'value' => null],
[['eh_loja'], 'default', 'value' => false],
```

3. Adicionar relacionamentos:
```php
// Relacionamento com user (autenticação)
public function getUser()
{
    return $this->hasOne(Users::class, ['id' => 'user_id']);
}

// Relacionamento com dono (self-reference)
public function getDono()
{
    return $this->hasOne(Usuario::class, ['id' => 'dono_id']);
}

// Relacionamento com lojas (filiais do dono)
public function getLojas()
{
    return $this->hasMany(Usuario::class, ['dono_id' => 'id'])
        ->where(['eh_loja' => true]);
}
```

4. Adicionar métodos helper:
```php
// Verifica se é dono principal
public function isDonoPrincipal()
{
    return $this->eh_dono_loja && $this->dono_id === null;
}

// Verifica se é loja/filial
public function isLoja()
{
    return $this->eh_loja === true;
}

// Retorna loja principal (se for filial)
public function getLojaPrincipal()
{
    if ($this->dono_id) {
        return self::findOne($this->dono_id);
    }
    return $this;
}
```

**Validações:**
- [ ] Model carrega sem erros
- [ ] Novos campos são acessíveis
- [ ] Relacionamentos funcionam
- [ ] Sistema continua funcionando

**Tempo estimado:** 1 hora

---

### **4.2. Atualizar Model `Colaborador`**

**Arquivo:** `modules/vendas/models/Colaborador.php`

**Ações:**
1. Adicionar propriedade `user_id`
2. Adicionar nas `rules()`
3. Adicionar relacionamento com `Users`

**Tempo estimado:** 30 minutos

---

### **4.3. Atualizar Models das 23 Tabelas**

**Estratégia:** Atualizar apenas os models principais primeiro (produtos, vendas, parcelas, clientes, caixa, contas_pagar)

**Para cada model:**
1. Adicionar propriedade `loja_id`
2. Adicionar nas `rules()` (opcional/NULL)
3. Adicionar relacionamento com `Usuario` (loja)

**Tempo estimado:** 2-3 horas (para models principais)

---

## 📝 PASSO 5: DOCUMENTAÇÃO E VALIDAÇÃO FINAL

### **5.1. Documentar Mudanças**

**Ações:**
1. Criar documento resumindo:
   - Campos adicionados
   - Tabelas afetadas
   - Foreign keys criadas
   - Índices criados

2. Atualizar documentação do projeto

**Tempo estimado:** 30 minutos

---

### **5.2. Validação Final**

**Checklist:**
- [ ] Todas as migrations executaram sem erro
- [ ] Todos os campos foram adicionados
- [ ] Foreign keys foram criadas
- [ ] Índices foram criados
- [ ] Models foram atualizados
- [ ] Sistema funciona normalmente (login, vendas, produtos, etc.)
- [ ] Nenhuma funcionalidade quebrou
- [ ] Backup está seguro
- [ ] Rollback foi testado (opcional)

**Tempo estimado:** 1 hora

---

## 📊 RESUMO DA FASE 1

### **Tempo Total Estimado:**
- Passo 1 (Análise): 2-3 horas
- Passo 2 (Migrations): 4-5 horas
- Passo 3 (Testes): 2-3 horas
- Passo 4 (Models): 3-4 horas
- Passo 5 (Documentação): 1-2 horas
- **TOTAL: 12-17 horas (2-3 dias úteis)**

### **Entregáveis:**
1. ✅ Campos adicionados em todas as tabelas
2. ✅ Foreign keys criadas
3. ✅ Índices criados
4. ✅ Models atualizados
5. ✅ Documentação atualizada
6. ✅ Sistema funcionando normalmente

### **Próxima Fase:**
Após validação completa da Fase 1, iniciar **Fase 2: Migração de Dados**

---

## 🚨 ALERTAS IMPORTANTES

### **⚠️ NÃO FAZER:**
1. ❌ Executar migrations em produção sem testar
2. ❌ Remover campos antigos (`usuario_id`)
3. ❌ Popular campos novos com dados ainda (aguardar Fase 2)
4. ❌ Atualizar queries para usar novos campos (aguardar Fase 3)

### **✅ FAZER:**
1. ✅ Backup completo antes de qualquer alteração
2. ✅ Testar tudo em ambiente de desenvolvimento primeiro
3. ✅ Validar que sistema continua funcionando
4. ✅ Documentar cada passo
5. ✅ Manter rollback disponível

---

## 📝 PRÓXIMOS PASSOS APÓS FASE 1

1. **Revisar e aprovar** mudanças da Fase 1
2. **Aplicar em staging** (se disponível)
3. **Validar em staging** por alguns dias
4. **Aplicar em produção** (após validação)
5. **Iniciar Fase 2** (Migração de Dados)

---

**Documento criado em:** 2025-01-27  
**Status:** ✅ Pronto para execução  
**Próximo passo:** Executar Passo 1.1 (Análise e Documentação)

