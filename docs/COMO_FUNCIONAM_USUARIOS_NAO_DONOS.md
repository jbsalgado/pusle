# 👥 Como Funcionam os Usuários do Sistema que Não São Donos

## 📋 Visão Geral

O sistema possui **3 tipos de usuários** com diferentes níveis de acesso:

1. **Usuario (Dono da Loja)** - `prest_usuarios`
2. **Colaborador (Funcionário)** - `prest_colaboradores`
3. **Cliente (Comprador)** - `prest_clientes`

---

## 🏢 1. USUARIO (Dono da Loja)

### **Características:**
- ✅ **Tabela:** `prest_usuarios`
- ✅ **Login:** Via web (`/auth/login`)
- ✅ **Credenciais:** CPF ou Email + Senha
- ✅ **Autenticação:** Sessão Yii2 (`Yii::$app->user`)
- ✅ **Acesso:** Dashboard completo, todos os módulos
- ✅ **Propósito:** Gerenciar a loja/empresa

### **Como Funciona:**
```
1. Usuario se cadastra em /auth/signup
2. Faz login em /auth/login (CPF/Email + Senha)
3. Sistema cria sessão Yii2
4. Acesso a todos os módulos de gestão
```

---

## 👨‍💼 2. COLABORADOR (Funcionário/Vendedor/Cobrador)

### **Características:**
- ✅ **Tabela:** `prest_colaboradores`
- ✅ **Relacionamento:** Pertence a um `Usuario` (via `usuario_id`)
- ⚠️ **Login:** **USA O MESMO LOGIN DO USUARIO ASSOCIADO**
- ✅ **Permissões:** Definidas no registro do Colaborador
- ✅ **Status:** Controlado pelo campo `ativo`

### **Como Funciona:**

#### **2.1. Estrutura:**
```
Usuario (Dono da Loja)
    └── Colaborador 1 (Vendedor)
    └── Colaborador 2 (Cobrador)
    └── Colaborador 3 (Administrador)
```

#### **2.2. Permissões (Campos no Colaborador):**
- `eh_vendedor` - Pode fazer vendas
- `eh_cobrador` - Pode fazer cobranças
- `eh_administrador` - Acesso completo (igual ao dono)
- `ativo` - Se `false`, não pode acessar (bloqueado)

#### **2.3. Processo de Login:**
```
1. Colaborador faz login usando as credenciais do Usuario associado
   (mesmo CPF/Email e senha do Usuario)
   
2. Sistema verifica se existe Colaborador ativo para esse Usuario:
   - Busca em prest_colaboradores onde usuario_id = ID do Usuario logado
   - Verifica se ativo = true
   
3. Se encontrado e ativo:
   - Sistema identifica as permissões do Colaborador
   - Aplica restrições de acesso baseadas em:
     * eh_vendedor
     * eh_cobrador  
     * eh_administrador
```

#### **2.4. Controle de Acesso:**
```php
// Exemplo: modules/vendas/controllers/InicioController.php
$colaborador = Colaborador::find()
    ->where(['usuario_id' => $usuario->id])
    ->andWhere(['ativo' => true])
    ->one();

$ehAdministrador = $colaborador ? $colaborador->eh_administrador : false;
```

#### **2.5. Restrições de Acesso:**
- **Se `eh_administrador = true`:** Acesso completo (igual ao dono)
- **Se `eh_vendedor = true`:** Pode acessar módulos de vendas
- **Se `eh_cobrador = true`:** Pode acessar módulos de cobrança
- **Se `ativo = false`:** Não pode acessar nada (bloqueado)

#### **2.6. PWA Prestanista (Cobradores):**
Para o PWA Prestanista, há um fluxo especial:
```
1. Cobrador acessa PWA Prestanista
2. Informa CPF
3. Sistema busca Colaborador por CPF + usuario_id
4. Verifica se eh_cobrador = true e ativo = true
5. Se válido, permite acesso ao PWA
```

**Endpoint:** `GET /api/colaborador/buscar-cpf?cpf=XXX&usuario_id=YYY`

---

## 🛒 3. CLIENTE (Comprador)

### **Características:**
- ✅ **Tabela:** `prest_clientes`
- ✅ **Relacionamento:** Pertence a um `Usuario` (via `usuario_id`)
- ✅ **Login:** Via API (`POST /api/cliente/login`)
- ✅ **Credenciais:** CPF + Senha + usuario_id
- ✅ **Autenticação:** JWT Token
- ✅ **Acesso:** Apenas catálogo PWA da loja específica
- ✅ **Propósito:** Comprar produtos

### **Como Funciona:**

#### **3.1. Cadastro:**
```
1. Cliente acessa catálogo PWA (web/catalogo)
2. Tenta buscar CPF para comprar
3. Se não existe, preenche formulário de cadastro
4. Sistema salva em prest_clientes com usuario_id
```

#### **3.2. Login:**
```
1. Cliente informa CPF + Senha + usuario_id
2. Sistema valida:
   - CPF existe na loja (usuario_id)
   - Senha está correta
   - Cliente está ativo
3. Gera token JWT
4. Retorna dados do cliente
```

#### **3.3. Acesso:**
- ✅ Visualizar catálogo de produtos da loja
- ✅ Fazer pedidos
- ✅ Ver histórico de pedidos
- ❌ Não tem acesso ao sistema de gestão

---

## 🔄 COMPARAÇÃO: TIPOS DE USUÁRIOS

| Aspecto | Usuario (Dono) | Colaborador | Cliente |
|---------|----------------|-------------|---------|
| **Tabela** | `prest_usuarios` | `prest_colaboradores` | `prest_clientes` |
| **Login** | Web (`/auth/login`) | **Mesmo do Usuario** | API (`/api/cliente/login`) |
| **Credenciais** | CPF/Email + Senha | **CPF/Email + Senha do Usuario** | CPF + Senha + usuario_id |
| **Autenticação** | Sessão Yii2 | Sessão Yii2 (via Usuario) | JWT Token |
| **Acesso** | Completo | Baseado em permissões | Apenas catálogo |
| **Vinculação** | Independente | Vinculado a Usuario | Vinculado a Usuario |
| **Pode Bloquear?** | Não (é o dono) | Sim (ativo = false) | Sim (ativo = false) |

---

## 🔐 FLUXO DE AUTENTICAÇÃO

### **Fluxo 1: Dono da Loja (Usuario)**
```
1. Acessa /auth/login
2. Informa CPF/Email + Senha
3. Sistema busca em prest_usuarios
4. Valida senha
5. Cria sessão Yii2
6. Acesso completo
```

### **Fluxo 2: Colaborador**
```
1. Acessa /auth/login
2. Informa CPF/Email + Senha (DO USUARIO ASSOCIADO)
3. Sistema busca em prest_usuarios
4. Valida senha
5. Busca Colaborador associado (usuario_id)
6. Verifica se ativo = true
7. Verifica permissões (eh_vendedor, eh_cobrador, eh_administrador)
8. Cria sessão Yii2 (com mesmo Usuario)
9. Acesso baseado em permissões
```

### **Fluxo 3: Cliente**
```
1. Acessa PWA Catálogo
2. Informa CPF + Senha + usuario_id
3. Sistema busca em prest_clientes (com usuario_id)
4. Valida senha
5. Verifica se ativo = true
6. Gera token JWT
7. Acesso apenas ao catálogo
```

---

## ⚠️ PONTOS IMPORTANTES

### **1. Colaborador NÃO tem login próprio**
- ❌ Colaborador **não tem** credenciais próprias
- ✅ Colaborador usa as credenciais do Usuario associado
- ✅ O sistema diferencia pelo registro em `prest_colaboradores`

### **2. Múltiplos Colaboradores podem usar o mesmo login**
- Se um Usuario tem 3 Colaboradores, todos usam o mesmo CPF/Email + Senha
- O sistema diferencia pelas permissões de cada Colaborador

### **3. Bloqueio de Colaborador**
- Quando `ativo = false`, o Colaborador não pode acessar
- Mas o Usuario (dono) continua podendo acessar normalmente

### **4. Administrador vs Dono**
- `eh_administrador = true` → Acesso completo (igual ao dono)
- Mas ainda é um Colaborador, pode ser bloqueado

---

## 🎯 RESUMO

### **Usuários que NÃO são donos:**

1. **Colaboradores:**
   - Funcionários que trabalham para o dono
   - Usam o login do Usuario associado
   - Permissões definidas no registro
   - Podem ser bloqueados (ativo = false)

2. **Clientes:**
   - Compradores da loja
   - Login próprio via API
   - Acesso apenas ao catálogo
   - Vinculados a uma loja específica

### **Diferença Principal:**
- **Colaborador:** Usa login do dono, mas com permissões limitadas
- **Cliente:** Login próprio, acesso apenas ao catálogo

---

**Data:** 2024-12-08
**Versão:** 1.0

