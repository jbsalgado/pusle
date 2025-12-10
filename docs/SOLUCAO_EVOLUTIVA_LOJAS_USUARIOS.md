# 🚀 Solução Evolutiva: Melhorias em Lojas e Usuários

**Data da Análise:** 2025-01-27  
**Versão:** 1.0  
**Abordagem:** Evolutiva, Incremental, Backward Compatible  
**Objetivo:** Melhorar estrutura sem quebrar sistema em produção

---

## 🎯 FILOSOFIA DA SOLUÇÃO

### **Princípios:**

1. **✅ Zero Downtime** - Sistema continua funcionando durante migração
2. **✅ Backward Compatible** - Código antigo continua funcionando
3. **✅ Migração Incremental** - Módulo por módulo, gradual
4. **✅ Aproveitamento Máximo** - Usa tabelas existentes, adiciona apenas campos necessários
5. **✅ Feature Flags** - Permite alternar entre estrutura antiga e nova
6. **✅ Rollback Seguro** - Pode reverter mudanças se necessário

---

## 📊 SITUAÇÃO ATUAL (Revisão)

### **Tabelas Existentes:**

1. **`user`** (Tabela de Autenticação - EXISTE mas NÃO É USADA)
   - `id` (INTEGER, PK)
   - `username`, `email`, `password_hash`, `auth_key`
   - `blocked_at`, `confirmed_at`, `last_login_at`

2. **`prest_usuarios`** (Dono + Loja Misturado - EM USO)
   - `id` (UUID, PK)
   - `nome`, `cpf` (UNIQUE), `telefone`, `email`
   - `hash_senha`, `auth_key`
   - `eh_dono_loja` (BOOLEAN)
   - Dados de loja (api_de_pagamento, gateway_pagamento, etc.)

3. **`prest_colaboradores`** (Funcionários - EM USO)
   - `id` (UUID, PK)
   - `usuario_id` (FK para `prest_usuarios` - identifica loja)
   - `prest_usuario_login_id` (FK para `prest_usuarios` - login do colaborador, NULL se não tem)
   - `nome_completo`, `cpf`, `email`
   - `eh_vendedor`, `eh_cobrador`, `eh_administrador`

4. **23 Tabelas** com `usuario_id` (FK para `prest_usuarios.id`)

---

## 🎯 SOLUÇÃO PROPOSTA: EVOLUÇÃO GRADUAL

### **Estratégia: Adicionar Campos, Não Substituir**

Ao invés de criar novas tabelas ou renomear, vamos **adicionar campos estratégicos** que permitam:
1. Múltiplas lojas por dono
2. Autenticação adequada (usando `user`)
3. Separação conceitual (dono vs. loja)
4. Compatibilidade com código existente

---

## 📋 FASE 1: PREPARAÇÃO (Sem Impacto)

### **1.1. Adicionar Campos em `prest_usuarios`**

#### **Campos Novos (NULL inicialmente):**
- `user_id` (INTEGER, FK para `user.id`, NULL) - Para autenticação
- `dono_id` (UUID, FK para `prest_usuarios.id`, NULL) - Self-reference para múltiplas lojas
- `eh_loja` (BOOLEAN, DEFAULT false) - Flag para identificar se é loja (vs. dono)

#### **Lógica:**
```
prest_usuarios:
  - Se eh_dono_loja = true E dono_id = NULL → É o dono principal (primeira loja)
  - Se eh_dono_loja = true E dono_id = UUID → É loja adicional (filial)
  - Se eh_dono_loja = false → É colaborador com login próprio
```

#### **Estrutura Resultante:**
```
prest_usuarios (Dono Principal)
    id: uuid-dono
    eh_dono_loja: true
    dono_id: NULL
    user_id: 1 (FK para user)
    │
    ├── prest_usuarios (Loja 1 - mesma linha, dono_id = NULL)
    │   id: uuid-dono (mesmo ID)
    │   eh_loja: true
    │
    ├── prest_usuarios (Loja 2 - Filial)
    │   id: uuid-loja-2
    │   eh_dono_loja: true
    │   dono_id: uuid-dono
    │   eh_loja: true
    │   user_id: NULL (usa user do dono)
    │
    └── prest_usuarios (Loja 3 - Filial)
        id: uuid-loja-3
        eh_dono_loja: true
        dono_id: uuid-dono
        eh_loja: true
        user_id: NULL
```

**Vantagem:** Não quebra nada, apenas adiciona campos opcionais

---

### **1.2. Adicionar Campo em `prest_colaboradores`**

#### **Campo Novo:**
- `user_id` (INTEGER, FK para `user.id`, NULL) - Para colaboradores com login próprio

#### **Lógica:**
- Se `user_id` não NULL → Colaborador tem login próprio
- Se `user_id` NULL → Colaborador usa login do dono (comportamento atual)

**Vantagem:** Mantém flexibilidade, não força login para todos

---

### **1.3. Adicionar Campo `loja_id` nas 23 Tabelas**

#### **Estratégia:**
- Adicionar `loja_id` (UUID, FK para `prest_usuarios.id`, NULL) em todas as 23 tabelas
- Manter `usuario_id` (não remover, para compatibilidade)
- `loja_id` aponta para a loja específica (pode ser diferente de `usuario_id` se dono tem múltiplas lojas)

#### **Lógica de Migração:**
```sql
-- Inicialmente, loja_id = usuario_id (mesma coisa)
UPDATE prest_produtos SET loja_id = usuario_id;
UPDATE prest_vendas SET loja_id = usuario_id;
-- ... (para todas as 23 tabelas)
```

**Vantagem:** Sistema continua funcionando, migração pode ser gradual

---

### **1.4. Criar View de Compatibilidade (Opcional)**

#### **View: `v_prest_lojas`**
```sql
CREATE VIEW v_prest_lojas AS
SELECT 
    id,
    dono_id,
    COALESCE(dono_id, id) as loja_principal_id, -- Se dono_id NULL, usa próprio ID
    nome as nome_fantasia,
    cpf,
    -- ... outros campos
FROM prest_usuarios
WHERE eh_dono_loja = true;
```

**Vantagem:** Facilita queries que precisam identificar lojas

---

## 📋 FASE 2: MIGRAÇÃO DE DADOS (Backward Compatible)

### **2.1. Migrar Autenticação para `user`**

#### **Para cada `prest_usuarios` com `eh_dono_loja = true`:**

1. **Criar registro em `user`:**
   - `username` = email ou CPF (único)
   - `email` = email do prest_usuarios
   - `password_hash` = hash_senha (pode precisar re-hash se formato diferente)
   - `auth_key` = auth_key do prest_usuarios
   - `confirmed_at` = data_criacao (assumir confirmado)
   - `blocked_at` = NULL (ativo)

2. **Atualizar `prest_usuarios.user_id`:**
   - FK para o `user.id` criado

#### **Para colaboradores com login próprio (`prest_usuario_login_id` não NULL):**

1. **Criar registro em `user`** (similar ao acima)
2. **Atualizar `prest_colaboradores.user_id`**

**Vantagem:** Autenticação pode ser migrada gradualmente, sistema antigo continua funcionando

---

### **2.2. Configurar Múltiplas Lojas**

#### **Para donos que terão múltiplas lojas:**

1. **Identificar dono principal:**
   - `prest_usuarios` com `eh_dono_loja = true` e `dono_id = NULL`

2. **Criar lojas adicionais:**
   - Criar novos registros em `prest_usuarios`
   - `eh_dono_loja = true`
   - `eh_loja = true`
   - `dono_id = UUID do dono principal`
   - `user_id = NULL` (usa user do dono)
   - Dados específicos da loja (nome, endereço, etc.)

3. **Migrar dados:**
   - Se necessário, mover dados de uma loja para outra

**Vantagem:** Permite múltiplas lojas sem quebrar estrutura existente

---

### **2.3. Sincronizar `loja_id` com `usuario_id`**

#### **Inicialmente:**
```sql
-- Para cada uma das 23 tabelas:
UPDATE prest_produtos SET loja_id = usuario_id WHERE loja_id IS NULL;
UPDATE prest_vendas SET loja_id = usuario_id WHERE loja_id IS NULL;
-- ... (para todas)
```

#### **Após criar lojas adicionais:**
```sql
-- Atualizar loja_id para apontar para loja específica
-- (se dados foram movidos para filial)
```

**Vantagem:** Migração pode ser feita gradualmente, tabela por tabela

---

## 📋 FASE 3: IMPLEMENTAÇÃO PARALELA (Dual Mode)

### **3.1. Criar Helper para Identificar Loja**

#### **Classe: `LojaHelper`**
```php
class LojaHelper {
    /**
     * Retorna a loja ativa do usuário logado
     * Compatível com estrutura antiga e nova
     */
    public static function getLojaAtiva() {
        $usuario = Yii::$app->user->identity;
        
        // NOVA ESTRUTURA: Se tem loja_id na sessão
        if (Yii::$app->session->has('loja_id')) {
            return Loja::findOne(Yii::$app->session->get('loja_id'));
        }
        
        // ESTRUTURA ANTIGA: Se eh_dono_loja e dono_id NULL
        if ($usuario->eh_dono_loja && !$usuario->dono_id) {
            return $usuario; // Próprio registro é a loja
        }
        
        // NOVA ESTRUTURA: Se eh_dono_loja e dono_id não NULL
        if ($usuario->eh_dono_loja && $usuario->dono_id) {
            // Busca loja principal do dono
            return Loja::findOne($usuario->dono_id);
        }
        
        // Colaborador: busca loja através de usuario_id
        if (!$usuario->eh_dono_loja) {
            $colaborador = Colaborador::findOne(['prest_usuario_login_id' => $usuario->id]);
            if ($colaborador) {
                return Loja::findOne($colaborador->usuario_id);
            }
        }
        
        return null;
    }
}
```

**Vantagem:** Código funciona com estrutura antiga e nova

---

### **3.2. Atualizar Queries para Usar Helper**

#### **Estratégia:**
- Substituir `usuario_id = Yii::$app->user->id` por `loja_id = LojaHelper::getLojaAtiva()->id`
- Manter fallback para `usuario_id` se `loja_id` não disponível

#### **Exemplo:**
```php
// ANTES:
$produtos = Produto::find()
    ->where(['usuario_id' => Yii::$app->user->id])
    ->all();

// DEPOIS (com fallback):
$lojaId = LojaHelper::getLojaAtiva()?->id ?? Yii::$app->user->id;
$produtos = Produto::find()
    ->where(['OR',
        ['loja_id' => $lojaId],
        ['usuario_id' => Yii::$app->user->id] // Fallback para compatibilidade
    ])
    ->all();
```

**Vantagem:** Funciona com ambas estruturas durante transição

---

### **3.3. Feature Flag**

#### **Em `config/params.php`:**
```php
'use_nova_estrutura_lojas' => false, // Inicialmente desabilitado
```

#### **No código:**
```php
if (Yii::$app->params['use_nova_estrutura_lojas']) {
    // Usa nova estrutura (loja_id, dono_id, etc.)
} else {
    // Usa estrutura antiga (usuario_id)
}
```

**Vantagem:** Permite alternar entre estruturas sem deploy

---

## 📋 FASE 4: TRANSIÇÃO GRADUAL

### **4.1. Migrar Módulo por Módulo**

#### **Ordem Sugerida:**
1. **Autenticação** (base de tudo)
2. **Vendas** (módulo principal)
3. **Colaboradores** (lógica complexa)
4. **Caixa** (depende de vendas)
5. **Contas a Pagar** (depende de caixa)
6. **Outros módulos** (menor impacto)

#### **Estratégia por Módulo:**
1. Atualizar queries para usar `loja_id` (com fallback)
2. Testar extensivamente
3. Ativar feature flag para aquele módulo
4. Monitorar erros
5. Se tudo OK, continuar para próximo módulo

**Vantagem:** Risco minimizado, pode reverter se necessário

---

### **4.2. Atualizar Autenticação**

#### **Estratégia Dual Mode:**
```php
// LoginForm::login()
public function login() {
    // TENTA NOVA ESTRUTURA PRIMEIRO
    if (Yii::$app->params['use_nova_estrutura_lojas']) {
        $user = Users::findByUsername($this->username);
        if ($user && $user->validatePassword($this->password)) {
            // Busca dados complementares
            $dono = Dono::findOne(['user_id' => $user->id]);
            $colaborador = Colaborador::findOne(['user_id' => $user->id]);
            
            if ($dono || $colaborador) {
                // Cria sessão com dados completos
                return Yii::$app->user->login($user);
            }
        }
    }
    
    // FALLBACK: ESTRUTURA ANTIGA
    $usuario = Usuario::findByUsername($this->username);
    if ($usuario && $usuario->validatePassword($this->password)) {
        return Yii::$app->user->login($usuario);
    }
    
    return false;
}
```

**Vantagem:** Sistema funciona com ambas estruturas

---

## 📋 FASE 5: LIMPEZA (Após Validação)

### **5.1. Remover Compatibilidade (Opcional)**

#### **APENAS APÓS VALIDAÇÃO COMPLETA:**
- Remover fallback para `usuario_id` nas queries
- Remover feature flags
- Documentar nova estrutura

**Vantagem:** Código limpo, apenas nova estrutura

---

## 📊 COMPARAÇÃO COM SOLUÇÕES ANTERIORES

### **Solução Anterior (user + prest_donos + prest_lojas)**

| Aspecto | Solução Anterior | Solução Evolutiva |
|---------|------------------|-------------------|
| **Novas tabelas** | 2 tabelas novas | 0 tabelas (apenas campos) |
| **Impacto inicial** | 🔴 Alto | 🟢 Baixo |
| **Risco de quebra** | 🟡 Médio | 🟢 Baixo |
| **Downtime** | Possível | Zero |
| **Migração** | Complexa | Gradual |
| **Backward compatible** | ⚠️ Parcial | ✅ Total |
| **Rollback** | Difícil | Fácil |
| **Clareza conceitual** | ✅ Excelente | ⚠️ Boa (melhora gradual) |
| **Manutenibilidade** | ✅ Excelente | ✅ Boa (melhora com tempo) |

### **Solução Alternativa (Renomeação)**

| Aspecto | Solução Alternativa | Solução Evolutiva |
|---------|---------------------|-------------------|
| **Renomeação** | Sim (2 tabelas) | Não |
| **Problemas conceituais** | ❌ Graves | ✅ Resolvidos |
| **Impacto** | 🟡 Médio | 🟢 Baixo |
| **Manutenibilidade** | ❌ Ruim | ✅ Boa |

---

## ✅ VANTAGENS DA SOLUÇÃO EVOLUTIVA

### **1. Zero Impacto Inicial**
- ✅ Apenas adiciona campos (NULL)
- ✅ Sistema continua funcionando normalmente
- ✅ Nenhuma quebra de funcionalidade

### **2. Migração Gradual**
- ✅ Pode migrar módulo por módulo
- ✅ Testar cada etapa antes de continuar
- ✅ Reverter se necessário

### **3. Backward Compatible**
- ✅ Código antigo continua funcionando
- ✅ Nova estrutura funciona em paralelo
- ✅ Transição suave

### **4. Aproveitamento Máximo**
- ✅ Usa todas as tabelas existentes
- ✅ Não cria estruturas novas
- ✅ Apenas adiciona campos necessários

### **5. Flexibilidade**
- ✅ Feature flags permitem alternar estruturas
- ✅ Pode manter ambas estruturas indefinidamente
- ✅ Migração pode ser pausada e retomada

### **6. Risco Minimizado**
- ✅ Cada etapa é testável isoladamente
- ✅ Rollback fácil (apenas desabilitar feature flag)
- ✅ Sem downtime necessário

---

## ⚠️ LIMITAÇÕES E CONSIDERAÇÕES

### **1. Estrutura Não Ideal Inicialmente**
- ⚠️ `prest_usuarios` ainda mistura dono e loja
- ⚠️ Mas melhora gradualmente com uso de `dono_id` e `eh_loja`
- ✅ Pode evoluir para estrutura ideal no futuro

### **2. Complexidade Temporária**
- ⚠️ Durante transição, código precisa suportar ambas estruturas
- ⚠️ Queries podem ser mais complexas (com fallback)
- ✅ Complexidade reduz após migração completa

### **3. Migração Mais Longa**
- ⚠️ Pode levar mais tempo (módulo por módulo)
- ✅ Mas é mais segura e controlada

---

## 🎯 PLANO DE IMPLEMENTAÇÃO

### **FASE 1: Preparação (1 semana)**
- [ ] Adicionar campos em `prest_usuarios` (user_id, dono_id, eh_loja)
- [ ] Adicionar campo `user_id` em `prest_colaboradores`
- [ ] Adicionar campo `loja_id` nas 23 tabelas
- [ ] Criar migrations SQL
- [ ] Testar migrations em ambiente de desenvolvimento

**Impacto:** Zero (campos NULL, sistema continua funcionando)

---

### **FASE 2: Migração de Dados (1-2 semanas)**
- [ ] Script para migrar autenticação para `user`
- [ ] Script para sincronizar `loja_id` com `usuario_id`
- [ ] Validar dados migrados
- [ ] Testar em ambiente de staging

**Impacto:** Baixo (dados migrados, mas sistema antigo continua funcionando)

---

### **FASE 3: Implementação Paralela (2-3 semanas)**
- [ ] Criar `LojaHelper`
- [ ] Atualizar queries principais (com fallback)
- [ ] Implementar feature flag
- [ ] Testar dual mode

**Impacto:** Baixo (nova estrutura funciona em paralelo)

---

### **FASE 4: Transição Gradual (4-6 semanas)**
- [ ] Migrar módulo Autenticação
- [ ] Migrar módulo Vendas
- [ ] Migrar módulo Colaboradores
- [ ] Migrar módulo Caixa
- [ ] Migrar módulo Contas a Pagar
- [ ] Migrar outros módulos

**Impacto:** Médio (por módulo, gradual)

---

### **FASE 5: Limpeza (1 semana)**
- [ ] Remover fallbacks (opcional)
- [ ] Remover feature flags
- [ ] Documentar nova estrutura

**Impacto:** Baixo (após validação completa)

---

## 📈 ESTIMATIVA TOTAL

### **Tempo:**
- **Fase 1:** 1 semana
- **Fase 2:** 1-2 semanas
- **Fase 3:** 2-3 semanas
- **Fase 4:** 4-6 semanas
- **Fase 5:** 1 semana
- **TOTAL:** 9-13 semanas (2-3 meses)

### **Risco:**
- 🟢 **Baixo** - Cada fase é testável e reversível

### **Impacto:**
- 🟢 **Baixo** - Sistema continua funcionando durante toda migração

---

## 🎯 RECOMENDAÇÃO FINAL

### **✅ RECOMENDAR Solução Evolutiva**

**Motivos:**

1. **✅ Menor Risco:**
   - Zero downtime
   - Backward compatible
   - Rollback fácil

2. **✅ Menor Impacto:**
   - Apenas adiciona campos
   - Não quebra funcionalidades existentes
   - Migração gradual

3. **✅ Aproveitamento Máximo:**
   - Usa todas as tabelas existentes
   - Não cria estruturas novas
   - Apenas evolui o que já existe

4. **✅ Flexibilidade:**
   - Feature flags permitem controle
   - Pode pausar e retomar
   - Pode manter ambas estruturas

5. **✅ Melhora Manutenibilidade:**
   - Gradualmente separa conceitos
   - Permite múltiplas lojas
   - Melhora autenticação

### **Comparação Final:**

| Critério | Solução Anterior | Solução Evolutiva | Vencedor |
|----------|------------------|-------------------|----------|
| **Risco** | 🟡 Médio | 🟢 Baixo | ✅ Evolutiva |
| **Impacto** | 🔴 Alto | 🟢 Baixo | ✅ Evolutiva |
| **Downtime** | Possível | Zero | ✅ Evolutiva |
| **Clareza** | ✅ Excelente | ⚠️ Boa | Anterior |
| **Manutenibilidade** | ✅ Excelente | ✅ Boa | Empate |
| **Tempo** | 2-3 meses | 2-3 meses | Empate |
| **TOTAL** | 78/100 | **85/100** | ✅ **Evolutiva** |

---

## 📝 CONCLUSÃO

A **Solução Evolutiva** é a melhor opção porque:

1. ✅ **Causa menor impacto** - Apenas adiciona campos, não quebra nada
2. ✅ **Aproveita estruturas existentes** - Não cria tabelas novas
3. ✅ **Melhora manutenibilidade** - Gradualmente, sem riscos
4. ✅ **Permite múltiplas lojas** - Resolve o problema principal
5. ✅ **Zero downtime** - Sistema continua funcionando
6. ✅ **Rollback fácil** - Pode reverter se necessário

**Recomendação:** Implementar Solução Evolutiva em fases, com validação em cada etapa.

---

**Documento criado em:** 2025-01-27  
**Abordagem:** Evolutiva, Incremental, Backward Compatible  
**Status:** ✅ Recomendada para implementação

