# 🔐 Como Colaborador Sem Login Próprio Acessa o Sistema

## 📋 Situação Atual vs Nova Estrutura

### **ANTES (Estrutura Antiga):**
- Colaborador **sempre** usava login do dono
- `prest_colaboradores.usuario_id` = ID do dono
- Busca colaborador: `WHERE usuario_id = ID do usuário logado`

### **AGORA (Nova Estrutura):**
- Colaborador pode ter login próprio OU usar login do dono
- `prest_colaboradores.usuario_id` = ID do dono (sempre - identifica a loja)
- `prest_colaboradores.prest_usuario_login_id` = ID do login do colaborador (NULL se não tem login próprio)

---

## 🎯 Dois Cenários de Acesso

### **Cenário 1: Colaborador SEM Login Próprio** (usa login do dono)

#### **Estrutura:**
```
prest_usuarios (Dono da Loja)
    id: uuid-dono
    username: "joao@loja.com"
    eh_dono_loja: true
    │
    └── prest_colaboradores
        id: uuid-colab
        usuario_id: uuid-dono              ← FK para dono (identifica loja)
        prest_usuario_login_id: NULL       ← SEM login próprio
        nome_completo: "Maria Silva"
        ativo: true
```

#### **Como Acessa:**
1. Colaborador vai em `/auth/login`
2. Informa as credenciais do DONO:
   - Username: `joao@loja.com` (do dono)
   - Senha: senha do dono
3. Sistema autentica usando `prest_usuarios` (dono)
4. Após login, sistema busca colaborador:
   ```php
   $usuarioLogado = Yii::$app->user->identity; // ID do dono
   $colaborador = Colaborador::find()
       ->where(['usuario_id' => $usuarioLogado->id])
       ->andWhere(['ativo' => true])
       ->one();
   ```
5. Se encontrar colaborador ativo, aplica permissões
6. Acesso baseado em `eh_vendedor`, `eh_cobrador`, `eh_administrador`

---

### **Cenário 2: Colaborador COM Login Próprio**

#### **Estrutura:**
```
prest_usuarios (Dono da Loja)
    id: uuid-dono
    username: "joao@loja.com"
    eh_dono_loja: true
    │
    └── prest_colaboradores
        id: uuid-colab
        usuario_id: uuid-dono              ← FK para dono (identifica loja)
        prest_usuario_login_id: uuid-login ← COM login próprio
        │
        └── prest_usuarios (Login do Colaborador)
            id: uuid-login
            username: "maria"
            eh_dono_loja: false
```

#### **Como Acessa:**
1. Colaborador vai em `/auth/login`
2. Informa suas próprias credenciais:
   - Username: `maria` (próprio)
   - Senha: senha própria
3. Sistema autentica usando `prest_usuarios` (login do colaborador)
4. Após login, sistema busca colaborador:
   ```php
   $usuarioLogado = Yii::$app->user->identity; // ID do login do colaborador
   $colaborador = Colaborador::find()
       ->where(['prest_usuario_login_id' => $usuarioLogado->id])
       ->andWhere(['ativo' => true])
       ->one();
   ```
5. Se encontrar colaborador ativo, aplica permissões
6. Acesso baseado em `eh_vendedor`, `eh_cobrador`, `eh_administrador`

---

## 💻 Implementação no Código

### **Função Helper para Buscar Colaborador Após Login:**

```php
/**
 * Busca colaborador associado ao usuário logado
 * Funciona tanto para colaborador com login próprio quanto sem login próprio
 */
public static function getColaboradorLogado()
{
    $usuarioLogado = Yii::$app->user->identity;
    
    if (!$usuarioLogado) {
        return null;
    }
    
    // Tenta buscar por prest_usuario_login_id (colaborador com login próprio)
    $colaborador = Colaborador::find()
        ->where(['prest_usuario_login_id' => $usuarioLogado->id])
        ->andWhere(['ativo' => true])
        ->one();
    
    // Se não encontrou, tenta buscar por usuario_id (colaborador sem login próprio)
    if (!$colaborador) {
        $colaborador = Colaborador::find()
            ->where(['usuario_id' => $usuarioLogado->id])
            ->andWhere(['ativo' => true])
            ->one();
    }
    
    return $colaborador;
}
```

### **Uso em Controllers:**

```php
public function actionIndex()
{
    $usuario = Yii::$app->user->identity;
    
    // Busca colaborador (funciona para ambos os cenários)
    $colaborador = Colaborador::getColaboradorLogado();
    
    // Verifica se é administrador
    $ehAdministrador = $colaborador ? $colaborador->eh_administrador : false;
    
    // Verifica se é dono da loja
    $ehDono = $usuario->eh_dono_loja === true;
    
    return $this->render('index', [
        'colaborador' => $colaborador,
        'ehAdministrador' => $ehAdministrador || $ehDono,
    ]);
}
```

---

## 🔄 Fluxo Completo de Login

### **Para Colaborador SEM Login Próprio:**

```
1. Colaborador acessa /auth/login
2. Informa credenciais do DONO:
   - Username: "joao@loja.com"
   - Senha: senha do dono
3. LoginForm valida em prest_usuarios
4. Sistema cria sessão Yii2 com ID do dono
5. Após login, controller busca colaborador:
   - WHERE usuario_id = ID do dono logado
   - AND ativo = true
6. Se encontrado, aplica permissões do colaborador
7. Acesso baseado em permissões (não tem acesso completo do dono)
```

### **Para Colaborador COM Login Próprio:**

```
1. Colaborador acessa /auth/login
2. Informa suas próprias credenciais:
   - Username: "maria"
   - Senha: senha própria
3. LoginForm valida em prest_usuarios (login do colaborador)
4. Sistema cria sessão Yii2 com ID do login do colaborador
5. Após login, controller busca colaborador:
   - WHERE prest_usuario_login_id = ID do login logado
   - AND ativo = true
6. Se encontrado, aplica permissões do colaborador
7. Acesso baseado em permissões
```

---

## ⚠️ Diferenças Importantes

### **Colaborador SEM Login Próprio:**
- ✅ Usa credenciais do dono
- ✅ Login cria sessão com ID do dono
- ✅ Busca colaborador: `WHERE usuario_id = ID do dono`
- ⚠️ Se o dono mudar a senha, colaborador perde acesso
- ⚠️ Não pode ter senha diferente do dono

### **Colaborador COM Login Próprio:**
- ✅ Usa suas próprias credenciais
- ✅ Login cria sessão com ID do login do colaborador
- ✅ Busca colaborador: `WHERE prest_usuario_login_id = ID do login`
- ✅ Pode ter senha diferente do dono
- ✅ Pode ser bloqueado independentemente (`blocked_at` no login)

---

## 🔧 Ajustes Necessários no Código

### **1. Atualizar busca de colaborador em controllers:**

**ANTES:**
```php
$colaborador = Colaborador::find()
    ->where(['usuario_id' => $usuario->id])
    ->andWhere(['ativo' => true])
    ->one();
```

**DEPOIS (suporta ambos):**
```php
$usuarioLogado = Yii::$app->user->identity;

// Tenta buscar por login próprio primeiro
$colaborador = Colaborador::find()
    ->where(['prest_usuario_login_id' => $usuarioLogado->id])
    ->andWhere(['ativo' => true])
    ->one();

// Se não encontrou, busca por usuario_id (sem login próprio)
if (!$colaborador) {
    $colaborador = Colaborador::find()
        ->where(['usuario_id' => $usuarioLogado->id])
        ->andWhere(['ativo' => true])
        ->one();
}
```

### **2. Adicionar método no modelo Colaborador:**

```php
/**
 * Busca colaborador associado ao usuário logado
 */
public static function getColaboradorLogado()
{
    $usuarioLogado = Yii::$app->user->identity;
    
    if (!$usuarioLogado) {
        return null;
    }
    
    // Tenta buscar por prest_usuario_login_id (com login próprio)
    $colaborador = static::find()
        ->where(['prest_usuario_login_id' => $usuarioLogado->id])
        ->andWhere(['ativo' => true])
        ->one();
    
    // Se não encontrou, busca por usuario_id (sem login próprio)
    if (!$colaborador) {
        $colaborador = static::find()
            ->where(['usuario_id' => $usuarioLogado->id])
            ->andWhere(['ativo' => true])
            ->one();
    }
    
    return $colaborador;
}
```

---

## 📝 Resumo

### **Colaborador SEM Login Próprio:**
1. Usa credenciais do dono para fazer login
2. Sistema busca colaborador por `usuario_id = ID do dono logado`
3. Aplica permissões do colaborador
4. Acesso limitado pelas permissões

### **Colaborador COM Login Próprio:**
1. Usa suas próprias credenciais para fazer login
2. Sistema busca colaborador por `prest_usuario_login_id = ID do login logado`
3. Aplica permissões do colaborador
4. Acesso limitado pelas permissões

### **Identificar a Loja:**
- **Sempre** use `colaborador->usuario_id` para identificar a loja
- Este campo sempre aponta para o dono da loja, independente de ter login próprio ou não

---

**Data:** 2024-12-08
**Status:** ✅ DOCUMENTAÇÃO COMPLETA

