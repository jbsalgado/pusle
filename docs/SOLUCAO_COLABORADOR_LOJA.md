# 🔗 Solução: Como Colaborador com Login Próprio Pertence a uma Loja

## ❓ Problema Identificado

Com a nova estrutura onde cada colaborador tem seu próprio login em `prest_usuarios` (`eh_dono_loja = false`), precisamos identificar:

1. **O colaborador** (que tem login próprio em `prest_usuarios`)
2. **O dono da loja** (que também está em `prest_usuarios` com `eh_dono_loja = true`)

---

## ✅ Solução Proposta

### **Estrutura de Relacionamento:**

```
prest_usuarios (Dono da Loja)
    id: uuid-dono
    eh_dono_loja: true
    username: "joao@loja.com"
    │
    └── prest_colaboradores
        id: uuid-colab
        usuario_id: uuid-dono          ← FK para DONO da loja (identifica a loja)
        prest_usuario_login_id: uuid-login  ← FK para login do colaborador (NOVO campo)
        nome_completo: "Maria Silva"
        │
        └── prest_usuarios (Login do Colaborador)
            id: uuid-login
            eh_dono_loja: false
            username: "maria"
            nome: "Maria Silva"
```

### **Dois Campos em `prest_colaboradores`:**

1. **`usuario_id`** (já existe)
   - **Aponta para o DONO da loja** (`prest_usuarios.id` onde `eh_dono_loja = true`)
   - **Identifica a qual loja o colaborador pertence**
   - **Mantém compatibilidade** com código existente

2. **`prest_usuario_login_id`** (NOVO campo - opcional)
   - **Aponta para o registro de LOGIN do colaborador** (`prest_usuarios.id` onde `eh_dono_loja = false`)
   - **NULL** se o colaborador não tem login próprio (usa login do dono)
   - **Preenchido** se o colaborador tem login próprio

---

## 📊 Estrutura da Tabela

### **`prest_colaboradores` Atualizada:**

```sql
prest_colaboradores (
    id UUID PRIMARY KEY,
    usuario_id UUID NOT NULL,                    -- FK para DONO da loja (prest_usuarios.id)
    prest_usuario_login_id UUID NULL,            -- NOVO: FK para login do colaborador (opcional)
    nome_completo VARCHAR(150) NOT NULL,
    cpf VARCHAR(11),
    telefone VARCHAR(20),
    email VARCHAR(100),
    eh_vendedor BOOLEAN,
    eh_cobrador BOOLEAN,
    eh_administrador BOOLEAN,
    ativo BOOLEAN,
    -- ... outros campos
    
    FOREIGN KEY (usuario_id) REFERENCES prest_usuarios(id),
    FOREIGN KEY (prest_usuario_login_id) REFERENCES prest_usuarios(id)
)
```

---

## 🎯 Como Funciona

### **Cenário 1: Colaborador com Login Próprio**

```php
// 1. Dono da Loja
$dono = Usuario::findOne(['eh_dono_loja' => true, 'id' => 'uuid-dono']);

// 2. Login do Colaborador
$loginColab = Usuario::findOne(['eh_dono_loja' => false, 'id' => 'uuid-login']);

// 3. Colaborador
$colaborador = Colaborador::findOne(['id' => 'uuid-colab']);
$colaborador->usuario_id = 'uuid-dono';              // Loja do dono
$colaborador->prest_usuario_login_id = 'uuid-login'; // Login próprio
```

**Para identificar a loja:**
```php
$colaborador = Colaborador::findOne($id);
$lojaId = $colaborador->usuario_id; // ID da loja (dono)
$loginId = $colaborador->prest_usuario_login_id; // ID do login do colaborador
```

### **Cenário 2: Colaborador sem Login Próprio (usa login do dono)**

```php
$colaborador = Colaborador::findOne($id);
$colaborador->usuario_id = 'uuid-dono';              // Loja do dono
$colaborador->prest_usuario_login_id = NULL;         // Sem login próprio
```

**Para identificar a loja:**
```php
$colaborador = Colaborador::findOne($id);
$lojaId = $colaborador->usuario_id; // ID da loja (dono)
// Login: usa o mesmo do dono (usuario_id)
```

---

## 💻 Exemplos de Uso

### **1. Obter a loja de um colaborador:**

```php
$colaborador = Colaborador::findOne($id);
$donoLoja = Usuario::findOne($colaborador->usuario_id);
echo "Colaborador pertence à loja: " . $donoLoja->nome;
```

### **2. Obter o login do colaborador:**

```php
$colaborador = Colaborador::findOne($id);

if ($colaborador->prest_usuario_login_id) {
    // Tem login próprio
    $login = Usuario::findOne($colaborador->prest_usuario_login_id);
    echo "Login: " . $login->username;
} else {
    // Usa login do dono
    $dono = Usuario::findOne($colaborador->usuario_id);
    echo "Login: " . $dono->username;
}
```

### **3. Listar colaboradores de uma loja:**

```php
$donoId = Yii::$app->user->id; // ID do dono logado

$colaboradores = Colaborador::find()
    ->where(['usuario_id' => $donoId]) // Filtra por loja
    ->all();
```

### **4. Criar colaborador com login próprio:**

```php
$donoId = Yii::$app->user->id;

// 1. Criar login do colaborador
$loginColab = new Usuario();
$loginColab->id = new Expression('gen_random_uuid()');
$loginColab->username = 'maria';
$loginColab->eh_dono_loja = false;
$loginColab->setPassword('senha123');
$loginColab->save();

// 2. Criar colaborador
$colaborador = new Colaborador();
$colaborador->usuario_id = $donoId; // Loja do dono
$colaborador->prest_usuario_login_id = $loginColab->id; // Login próprio
$colaborador->nome_completo = 'Maria Silva';
$colaborador->save();
```

---

## 🔄 Migração Necessária

### **SQL para adicionar campo:**

```sql
ALTER TABLE prest_colaboradores 
ADD COLUMN prest_usuario_login_id UUID;

-- Adicionar FK
ALTER TABLE prest_colaboradores
ADD CONSTRAINT fk_colaborador_login 
FOREIGN KEY (prest_usuario_login_id) 
REFERENCES prest_usuarios(id) 
ON DELETE SET NULL;

-- Criar índice
CREATE INDEX idx_colaboradores_prest_usuario_login_id 
ON prest_colaboradores(prest_usuario_login_id);
```

---

## ✅ Vantagens da Solução

1. ✅ **Mantém compatibilidade**: `usuario_id` continua apontando para o dono
2. ✅ **Identifica a loja**: `usuario_id` sempre identifica a qual loja pertence
3. ✅ **Suporta login próprio**: `prest_usuario_login_id` aponta para o login do colaborador
4. ✅ **Flexível**: Permite colaboradores com ou sem login próprio
5. ✅ **Não quebra código existente**: Todas as queries que usam `usuario_id` continuam funcionando

---

## 📝 Resumo

### **Para identificar a loja de um colaborador:**

```php
$colaborador = Colaborador::findOne($id);
$lojaId = $colaborador->usuario_id; // SEMPRE aponta para o dono da loja
```

### **Para identificar o login do colaborador:**

```php
$colaborador = Colaborador::findOne($id);

if ($colaborador->prest_usuario_login_id) {
    // Tem login próprio
    $login = Usuario::findOne($colaborador->prest_usuario_login_id);
} else {
    // Usa login do dono
    $login = Usuario::findOne($colaborador->usuario_id);
}
```

---

**Data:** 2024-12-08
**Status:** ✅ SOLUÇÃO PROPOSTA

