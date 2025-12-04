# Migration: Adicionar Campos de Endereço e Logo ao Cadastro de Usuários

## 📋 Resumo das Alterações

Adicionados campos de endereço e logo ao cadastro do dono da loja (`prest_usuarios`):

### Novos Campos na Tabela `prest_usuarios`:
- `endereco` (VARCHAR 255) - Endereço (logradouro) da empresa
- `bairro` (VARCHAR 100) - Bairro da empresa
- `cidade` (VARCHAR 100) - Cidade da empresa
- `estado` (VARCHAR 2) - Estado (UF) da empresa
- `logo_path` (VARCHAR 500) - Caminho/URL da logo da empresa

## 🔧 Arquivos Modificados

### 1. Migration SQL
- **Arquivo:** `sql/postgres/005_add_endereco_logo_to_prest_usuarios.sql`
- **Descrição:** Script SQL para adicionar os novos campos à tabela

### 2. Model Usuario
- **Arquivo:** `models/Usuario.php`
- **Alterações:**
  - Adicionadas regras de validação para os novos campos
  - Adicionados labels para os novos campos

### 3. Formulário de Cadastro (SignupForm)
- **Arquivo:** `models/SignupForm.php`
- **Alterações:**
  - Adicionadas propriedades públicas para os novos campos
  - Adicionadas regras de validação
  - Adicionados labels
  - Atualizado método `signup()` para salvar os novos campos

### 4. View de Cadastro
- **Arquivo:** `views/auth/signup.php`
- **Alterações:**
  - Adicionados campos de endereço no formulário
  - Adicionado campo de logo
  - Adicionada máscara para campo Estado (UF)

### 5. API de Dados da Loja
- **Arquivo:** `modules/api/controllers/UsuarioController.php`
- **Alterações:**
  - Atualizado `actionDadosLoja()` para retornar os novos campos
  - Monta endereço completo a partir dos campos individuais

### 6. Comprovante de Venda
- **Arquivo:** `web/venda-direta/js/pix.js`
- **Alterações:**
  - Atualizado para usar os novos campos de endereço
  - Prioriza campos individuais sobre endereco_completo

## 📝 Como Executar a Migration

### Opção 1: Via psql (Recomendado)
```bash
psql -U seu_usuario -d nome_do_banco -f sql/postgres/005_add_endereco_logo_to_prest_usuarios.sql
```

### Opção 2: Via pgAdmin ou cliente SQL
1. Abra o arquivo `sql/postgres/005_add_endereco_logo_to_prest_usuarios.sql`
2. Execute o script no banco de dados

### Opção 3: Via Yii2 Console (se configurado)
```bash
php yii migrate
```

## ✅ Validações Implementadas

### Campos de Endereço:
- **endereco:** Máximo 255 caracteres (opcional)
- **bairro:** Máximo 100 caracteres (opcional)
- **cidade:** Máximo 100 caracteres (opcional)
- **estado:** Máximo 2 caracteres, apenas letras maiúsculas (opcional)
- **logo_path:** Máximo 500 caracteres (opcional)

## 🎯 Uso dos Campos

### No Cadastro:
- Todos os campos são **opcionais**
- Podem ser preenchidos durante o cadastro inicial
- Podem ser atualizados posteriormente

### No Comprovante:
- Os campos são usados para preencher o cabeçalho do comprovante
- Se não preenchidos, o sistema usa valores padrão
- O endereço completo é montado automaticamente

### Na API:
- Endpoint `/api/usuario/dados-loja` retorna todos os campos
- Usado pelo sistema de comprovantes PIX

## 🔍 Verificação

Após executar a migration, verifique se os campos foram criados:

```sql
SELECT column_name, data_type, character_maximum_length
FROM information_schema.columns
WHERE table_name = 'prest_usuarios'
AND column_name IN ('endereco', 'bairro', 'cidade', 'estado', 'logo_path');
```

## 📌 Notas Importantes

1. **Campos Opcionais:** Todos os novos campos são opcionais para não quebrar cadastros existentes
2. **Compatibilidade:** O sistema mantém compatibilidade com `endereco_completo` da tabela `prest_configuracoes`
3. **Prioridade:** Se os campos individuais estiverem preenchidos, eles têm prioridade sobre `endereco_completo`

