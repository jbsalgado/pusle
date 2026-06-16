# ⚠️ Problema: Colaborador Sem Login Próprio Não Pode Acessar Sistema Web

## ❌ Problema Identificado

**Você está absolutamente correto!** 

Se um colaborador **sem login próprio** usa as credenciais do dono para fazer login, o sistema **NÃO consegue diferenciar** se quem está logado é:
- O **dono da loja** (acesso completo)
- Um **colaborador** (acesso limitado)

Ambos usariam as **mesmas credenciais** e o sistema criaria a **mesma sessão**, sem saber qual é qual.

---

## 🔍 Análise do Problema

### **Cenário Problemático:**

```
1. Dono faz login:
   - Username: "joao@loja.com"
   - Senha: "senha123"
   - Sistema cria sessão: Yii::$app->user->id = uuid-dono
   - Busca colaborador: WHERE usuario_id = uuid-dono
   - Encontra colaborador? Pode ser o dono ou um colaborador!

2. Colaborador faz login (mesmas credenciais):
   - Username: "joao@loja.com" (do dono)
   - Senha: "senha123" (do dono)
   - Sistema cria sessão: Yii::$app->user->id = uuid-dono (MESMO!)
   - Busca colaborador: WHERE usuario_id = uuid-dono
   - Encontra colaborador? Qual? Pode ser qualquer um!
```

**Resultado:** Sistema não consegue diferenciar quem está logado!

---

## ✅ Soluções Possíveis

### **Solução 1: Colaborador SEM Login Próprio NÃO Acessa Sistema Web**

**Regra:**
- Colaborador **SEM login próprio** (`prest_usuario_login_id = NULL`) **NÃO pode acessar o sistema web**
- Apenas colaboradores **COM login próprio** podem acessar o sistema web
- Colaborador sem login próprio pode acessar apenas via **PWA** (que tem outro método de autenticação)

**Implementação:**
```php
// Após login, verificar se é colaborador sem login próprio
$usuario = Yii::$app->user->identity;

if ($usuario->eh_dono_loja === false) {
    // É um login de colaborador
    $colaborador = Colaborador::find()
        ->where(['prest_usuario_login_id' => $usuario->id])
        ->andWhere(['ativo' => true])
        ->one();
    
    if (!$colaborador) {
        // Não tem colaborador associado, não pode acessar
        Yii::$app->user->logout();
        throw new ForbiddenHttpException('Você não tem permissão para acessar o sistema web.');
    }
} else {
    // É dono da loja, acesso completo
}
```

---

### **Solução 2: Forçar Criação de Login Próprio**

**Regra:**
- Todo colaborador que precisa acessar o sistema web **DEVE ter login próprio**
- Não é possível criar colaborador sem login próprio para acesso web
- Colaborador sem login próprio só existe para acesso via PWA/API

**Implementação:**
```php
// Ao criar colaborador, sempre criar login próprio
$loginColab = new Usuario();
$loginColab->username = 'maria';
$loginColab->eh_dono_loja = false;
$loginColab->setPassword('senha123');
$loginColab->save();

$colaborador = new Colaborador();
$colaborador->usuario_id = $donoId; // Loja
$colaborador->prest_usuario_login_id = $loginColab->id; // SEMPRE preencher
$colaborador->save();
```

---

### **Solução 3: Sistema de "Assumir Identidade" (Complexo)**

**Regra:**
- Dono faz login normalmente
- Após login, dono pode "assumir identidade" de um colaborador
- Sistema mantém sessão do dono, mas aplica permissões do colaborador

**Implementação:**
```php
// Dono faz login
Yii::$app->user->login($dono);

// Dono escolhe "assumir identidade" de colaborador
Yii::$app->session->set('colaborador_id', $colaboradorId);

// Em controllers, verifica se está "assumindo identidade"
$colaboradorId = Yii::$app->session->get('colaborador_id');
if ($colaboradorId) {
    $colaborador = Colaborador::findOne($colaboradorId);
    // Aplica permissões do colaborador
}
```

**Problema:** Muito complexo e confuso para o usuário.

---

## 🎯 Recomendação: Solução 1 + Solução 2

### **Regra Final:**

1. **Colaborador SEM login próprio (`prest_usuario_login_id = NULL`):**
   - ❌ **NÃO pode acessar sistema web**
   - ✅ Pode acessar apenas via **PWA Prestanista** (autenticação por CPF)
   - ✅ Usado para cobradores que só usam o PWA mobile

2. **Colaborador COM login próprio (`prest_usuario_login_id` preenchido):**
   - ✅ **PODE acessar sistema web**
   - ✅ Usa suas próprias credenciais
   - ✅ Sistema identifica corretamente quem está logado

3. **Dono da Loja (`eh_dono_loja = true`):**
   - ✅ **PODE acessar sistema web**
   - ✅ Acesso completo

---

## 🔧 Implementação Recomendada

### **1. Atualizar LoginForm para validar:**

```php
public function validatePassword($attribute, $params)
{
    if (!$this->hasErrors()) {
        $usuario = $this->getUsuario();
        
        if (!$usuario) {
            $this->addError($attribute, 'Usuário não encontrado.');
            return;
        }
        
        // Verifica se está bloqueado
        if ($usuario->isBlocked()) {
            $this->addError($attribute, 'Usuário bloqueado.');
            return;
        }
        
        // Se não é dono, verifica se tem colaborador associado
        if ($usuario->eh_dono_loja === false) {
            $colaborador = Colaborador::find()
                ->where(['prest_usuario_login_id' => $usuario->id])
                ->andWhere(['ativo' => true])
                ->one();
            
            if (!$colaborador) {
                $this->addError($attribute, 'Você não tem permissão para acessar o sistema web. Use o aplicativo mobile.');
                return;
            }
        }
        
        // Valida senha
        if (!$usuario->validatePassword($this->senha)) {
            $this->addError($attribute, 'CPF/E-mail ou senha incorretos.');
        }
    }
}
```

### **2. Atualizar método getColaboradorLogado:**

```php
public static function getColaboradorLogado()
{
    $usuarioLogado = Yii::$app->user->identity;
    
    if (!$usuarioLogado) {
        return null;
    }
    
    // Se é dono, não é colaborador
    if ($usuarioLogado->eh_dono_loja === true) {
        return null;
    }
    
    // Busca colaborador por prest_usuario_login_id (deve ter login próprio)
    $colaborador = static::find()
        ->where(['prest_usuario_login_id' => $usuarioLogado->id])
        ->andWhere(['ativo' => true])
        ->one();
    
    return $colaborador;
}
```

### **3. Atualizar controllers para verificar:**

```php
public function actionIndex()
{
    $usuario = Yii::$app->user->identity;
    
    // Se não é dono, deve ter colaborador associado
    if ($usuario->eh_dono_loja === false) {
        $colaborador = Colaborador::getColaboradorLogado();
        
        if (!$colaborador) {
            throw new ForbiddenHttpException('Você não tem permissão para acessar esta área.');
        }
        
        // Aplica permissões do colaborador
        $ehAdmin = $colaborador->eh_administrador;
    } else {
        // É dono, acesso completo
        $ehAdmin = true;
    }
    
    return $this->render('index', [
        'ehAdministrador' => $ehAdmin,
    ]);
}
```

---

## 📝 Resumo

### **Problema:**
- Colaborador sem login próprio usa credenciais do dono
- Sistema não consegue diferenciar quem está logado
- **Não funciona!**

### **Solução:**
- Colaborador sem login próprio **NÃO acessa sistema web**
- Apenas colaboradores **COM login próprio** acessam sistema web
- Colaborador sem login próprio acessa apenas via **PWA** (CPF)

### **Regra:**
> **Todo colaborador que precisa acessar o sistema web DEVE ter login próprio.**

---

**Data:** 2024-12-08
**Status:** ⚠️ PROBLEMA IDENTIFICADO - Solução proposta

