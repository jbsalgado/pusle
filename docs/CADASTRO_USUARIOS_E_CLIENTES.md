# Sistema de Cadastro: Dono da Loja vs Cliente

## 📋 Visão Geral

O sistema possui **duas entidades distintas** com propósitos diferentes:

1. **Dono da Loja (Usuário/Prestador)** - Gerencia a loja e vende produtos
2. **Cliente** - Compra produtos da loja

---

## 🏪 1. CADASTRO DO DONO DA LOJA (Usuário/Prestador)

### 📍 Localização
- **Model:** `app/models/Usuario.php`
- **Tabela:** `prest_usuarios`
- **Formulário:** `app/models/SignupForm.php`
- **Controller:** `app/controllers/AuthController.php`

### 🔐 Processo de Cadastro

#### 1.1. Formulário de Cadastro (`/auth/signup`)
O dono da loja se cadastra através do formulário em `views/auth/signup.php`:

**Campos Obrigatórios:**
- ✅ **Nome Completo** (3-100 caracteres, apenas letras)
- ✅ **CPF** (11 dígitos, único no sistema, validado)
- ✅ **Telefone** (10-11 dígitos)
- ✅ **Email** (formato válido, opcionalmente único)
- ✅ **Senha** (mínimo 6 caracteres)
- ✅ **Confirmar Senha** (deve ser igual à senha)
- ✅ **Termos de Uso** (deve aceitar)

#### 1.2. Validações
```php
// SignupForm.php
- CPF: Validação matemática dos dígitos verificadores
- CPF: Único no sistema (não pode repetir)
- Email: Formato válido
- Senha: Mínimo 6 caracteres
- Confirmação: Deve ser igual à senha
```

#### 1.3. Processo de Salvamento
```php
// SignupForm::signup()
1. Gera UUID único para o usuário
2. Limpa CPF e telefone (remove formatação)
3. Criptografa senha usando Yii2 Security (hash)
4. Gera auth_key para "lembrar-me"
5. Salva na tabela prest_usuarios
6. Faz login automático após cadastro
7. Redireciona para /vendas/dashboard
```

#### 1.4. Estrutura da Tabela `prest_usuarios`
```sql
- id (UUID) - Chave primária
- nome (VARCHAR 100) - Nome do dono da loja
- cpf (VARCHAR 20) - CPF único
- telefone (VARCHAR 30)
- email (VARCHAR 100)
- hash_senha (VARCHAR 255) - Senha criptografada
- auth_key (VARCHAR 32) - Para "lembrar-me"
- api_de_pagamento (BOOLEAN) - Se usa gateway
- gateway_pagamento (VARCHAR 50) - 'mercadopago' | 'asaas' | 'nenhum'
- mercadopago_public_key, mercadopago_access_token
- asaas_api_key
- catalogo_path (VARCHAR 100) - Caminho do catálogo
- data_criacao, data_atualizacao
```

#### 1.5. Autenticação
- **Login:** Via `LoginForm` usando email/CPF + senha
- **Sessão:** Yii2 User Component (`Yii::$app->user`)
- **Acesso:** Dashboard, módulos de vendas, configurações

---

## 👤 2. CADASTRO DO CLIENTE (Quem Compra)

### 📍 Localização
- **Model:** `app/modules/vendas/models/Cliente.php`
- **Tabela:** `prest_clientes`
- **API:** `modules/api/controllers/ClienteController.php`
- **Frontend:** `web/catalogo/js/customer.js` (PWA)

### 🔐 Processo de Cadastro

#### 2.1. Contexto de Uso
O cliente se cadastra **dentro do contexto de uma loja específica**:
- Através do **Catálogo PWA** (`web/catalogo`)
- Durante o processo de compra
- **Sempre vinculado a um `usuario_id`** (dono da loja)

#### 2.2. Formulário de Cadastro (PWA)
O cliente se cadastra no catálogo quando:
1. Busca CPF e não encontra
2. Clica em "Cadastrar Novo Cliente"
3. Preenche formulário completo

**Campos Obrigatórios:**
- ✅ **Nome Completo** (máx 150 caracteres)
- ✅ **CPF** (11 dígitos, único **POR LOJA**)
- ✅ **Telefone** (máx 20 caracteres)
- ✅ **Senha** (mínimo 4 caracteres) - Para login no PWA
- ✅ **Endereço Completo:**
  - Logradouro (rua/avenida)
  - Número
  - Bairro
  - Cidade
  - Estado (2 caracteres)
  - CEP (opcional)
  - Complemento (opcional)

**Campos Opcionais:**
- Email
- Ponto de Referência
- Observações

#### 2.3. Validações
```php
// Cliente.php
- CPF: 11 dígitos, único POR LOJA (mesmo CPF pode existir em lojas diferentes)
- Senha: Mínimo 4 caracteres
- Endereço: Logradouro, número, bairro e cidade são obrigatórios
- Email: Formato válido (se informado)
```

#### 2.4. Processo de Salvamento
```php
// ClienteController::actionCreate()
1. Recebe dados via API REST (POST /api/cliente)
2. Valida usuario_id (obrigatório - identifica a loja)
3. Limpa CPF (remove formatação)
4. Criptografa senha usando Yii2 Security (hash)
5. Salva na tabela prest_clientes
6. Retorna dados do cliente criado
```

#### 2.5. Estrutura da Tabela `prest_clientes`
```sql
- id (UUID) - Chave primária
- usuario_id (UUID) - FK para prest_usuarios (OBRIGATÓRIO)
- nome_completo (VARCHAR 150) - Nome do cliente
- cpf (VARCHAR 11) - CPF (único por usuario_id)
- telefone (VARCHAR 20)
- email (VARCHAR 100)
- senha_hash (VARCHAR 255) - Senha criptografada para PWA
- endereco_logradouro (VARCHAR 255) - OBRIGATÓRIO
- endereco_numero (VARCHAR 20) - OBRIGATÓRIO
- endereco_complemento (VARCHAR 100)
- endereco_bairro (VARCHAR 100) - OBRIGATÓRIO
- endereco_cidade (VARCHAR 100) - OBRIGATÓRIO
- endereco_estado (VARCHAR 2)
- endereco_cep (VARCHAR 8)
- ponto_referencia (TEXT)
- observacoes (TEXT)
- ativo (BOOLEAN) - Exclusão lógica
- regiao_id (UUID) - FK para região (opcional)
- data_criacao, data_atualizacao
```

#### 2.6. Autenticação do Cliente
- **Login:** Via API (`POST /api/cliente/login`)
- **Credenciais:** CPF + Senha + usuario_id
- **Token:** JWT simples gerado após login
- **Acesso:** Apenas ao catálogo da loja específica

---

## 🔗 3. RELACIONAMENTO ENTRE AS ENTIDADES

### 3.1. Estrutura de Relacionamento
```
prest_usuarios (Dono da Loja)
    │
    ├── prest_clientes (Clientes da Loja)
    │   └── usuario_id → FK para prest_usuarios
    │
    ├── prest_produtos (Produtos da Loja)
    │   └── usuario_id → FK para prest_usuarios
    │
    ├── prest_vendas (Vendas da Loja)
    │   └── usuario_id → FK para prest_usuarios
    │
    └── prest_colaboradores (Vendedores da Loja)
        └── usuario_id → FK para prest_usuarios
```

### 3.2. Isolamento de Dados
- **Cada loja tem seus próprios clientes**
- Um CPF pode existir em múltiplas lojas (diferentes `usuario_id`)
- Cliente só vê produtos da sua loja
- Vendas são isoladas por loja

### 3.3. Foreign Key
```sql
ALTER TABLE prest_clientes
ADD CONSTRAINT prest_clientes_usuario_id_fkey 
FOREIGN KEY (usuario_id) 
REFERENCES prest_usuarios(id) 
ON DELETE RESTRICT;
```

---

## 📊 4. COMPARAÇÃO: DONO DA LOJA vs CLIENTE

| Aspecto | Dono da Loja (Usuario) | Cliente |
|---------|------------------------|---------|
| **Tabela** | `prest_usuarios` | `prest_clientes` |
| **Cadastro** | `/auth/signup` (Web) | `/api/cliente` (PWA) |
| **Autenticação** | Sessão Yii2 | JWT Token |
| **CPF** | Único no sistema | Único por loja |
| **Senha Mínima** | 6 caracteres | 4 caracteres |
| **Endereço** | Não obrigatório | Obrigatório |
| **Acesso** | Dashboard completo | Apenas catálogo |
| **Vinculação** | Independente | Sempre vinculado a `usuario_id` |
| **Propósito** | Gerenciar loja | Comprar produtos |

---

## 🔄 5. FLUXOS DE CADASTRO

### 5.1. Fluxo: Cadastro do Dono da Loja
```
1. Acessa /auth/signup
2. Preenche formulário (nome, CPF, telefone, email, senha)
3. Sistema valida CPF (matemática + único)
4. Sistema criptografa senha
5. Salva em prest_usuarios
6. Faz login automático
7. Redireciona para /vendas/dashboard
```

### 5.2. Fluxo: Cadastro do Cliente (PWA)
```
1. Cliente acessa catálogo PWA
2. Tenta buscar CPF para comprar
3. Sistema verifica se existe (GET /api/cliente/buscar-cpf)
4. Se não existe:
   a. Mostra formulário de cadastro
   b. Cliente preenche dados + endereço + senha
   c. Sistema envia POST /api/cliente
   d. Sistema valida CPF (único na loja)
   e. Sistema criptografa senha
   f. Salva em prest_clientes com usuario_id
5. Cliente pode fazer login com CPF + senha
6. Cliente finaliza compra
```

---

## 🔒 6. SEGURANÇA E AUTENTICAÇÃO

### 6.1. Dono da Loja
- **Hash de Senha:** `Yii::$app->security->generatePasswordHash()`
- **Auth Key:** Gerado para "lembrar-me"
- **Sessão:** Gerenciada pelo Yii2 User Component
- **Acesso:** Requer autenticação em todas as rotas protegidas

### 6.2. Cliente
- **Hash de Senha:** `Yii::$app->security->generatePasswordHash()`
- **Token JWT:** Gerado após login bem-sucedido
- **Acesso:** Apenas ao catálogo da loja específica
- **Isolamento:** Cliente só vê dados da sua loja

---

## 📝 7. DIFERENÇAS CHAVE

### 7.1. Unicidade do CPF
- **Dono da Loja:** CPF único em TODO o sistema
- **Cliente:** CPF único APENAS dentro da mesma loja (`usuario_id`)

### 7.2. Endereço
- **Dono da Loja:** Não obrigatório (pode ser configurado depois)
- **Cliente:** Obrigatório (necessário para entrega/cobrança)

### 7.3. Senha
- **Dono da Loja:** Mínimo 6 caracteres
- **Cliente:** Mínimo 4 caracteres (mais simples para PWA)

### 7.4. Contexto
- **Dono da Loja:** Sistema completo (múltiplos módulos)
- **Cliente:** Apenas catálogo PWA da loja específica

---

## 🎯 8. CASOS DE USO

### 8.1. Venda Direta (`web/venda-direta`)
- **Cliente:** Opcional (pode ser venda sem cliente)
- **Dono da Loja:** Deve estar autenticado
- **Vendedor:** Opcional (colaborador com CPF)

### 8.2. Catálogo PWA (`web/catalogo`)
- **Cliente:** Obrigatório (deve estar cadastrado e logado)
- **Dono da Loja:** Não precisa estar logado (cliente acessa diretamente)
- **Isolamento:** Cliente só vê produtos da loja (`usuario_id`)

---

## 🔍 9. EXEMPLOS DE CONSULTAS

### 9.1. Buscar Cliente por CPF (dentro de uma loja)
```php
Cliente::find()
    ->where(['cpf' => '12345678900', 'usuario_id' => $usuarioId, 'ativo' => true])
    ->one();
```

### 9.2. Listar Todos os Clientes de uma Loja
```php
Cliente::find()
    ->where(['usuario_id' => $usuarioId, 'ativo' => true])
    ->all();
```

### 9.3. Verificar se CPF de Dono da Loja Existe
```php
Usuario::find()
    ->where(['cpf' => '12345678900'])
    ->exists();
```

---

## ✅ 10. RESUMO

**Dono da Loja (Usuario):**
- Cadastra-se uma vez no sistema
- Gerencia sua loja completa
- CPF único globalmente
- Acesso completo ao sistema

**Cliente:**
- Cadastra-se por loja (pode ter cadastro em múltiplas lojas)
- Apenas compra produtos
- CPF único por loja
- Acesso apenas ao catálogo PWA

**Relacionamento:**
- Cliente sempre pertence a uma loja (`usuario_id`)
- Dados isolados por loja
- Mesmo CPF pode existir em lojas diferentes como clientes diferentes

