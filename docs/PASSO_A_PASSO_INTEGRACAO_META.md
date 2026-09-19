# Guia Completo: Passo a Passo Integração Meta (Instagram & Facebook) no Pulse SaaS

Este guia documenta o fluxo completo para obtenção de credenciais, configuração de permissões, testes imediatos, conformidade e verificação empresarial no ecossistema da **Meta (Facebook & Instagram)** e **TikTok** para o **Pulse SaaS**.

---

## ⚡ Tabela Rápida: Copiar e Colar no Painel da Meta

Para agilizar, aqui estão todos os links exatos configurados no seu servidor para preencher no **Meta Developer Console** (App ID: `2061639194459307`):

| Campo no Painel da Meta | Valor Exato para Copiar e Colar | Onde Configurar no Painel |
| :--- | :--- | :--- |
| **URL da Política de Privacidade** | `https://catalogos.oncode.app.br/politica-privacidade` | Configurações → Básico |
| **URL dos Termos de Serviço** | `https://catalogos.oncode.app.br/termos-de-uso` | Configurações → Básico |
| **Exclusão de dados do usuário** | `https://catalogos.oncode.app.br/exclusao-dados` | Configurações → Básico |
| **Domínios permitidos para o SDK JS** | `https://catalogos.oncode.app.br` | Produtos → Login do Facebook → Configurações |
| **URIs de redirecionamento OAuth válidos** | `https://catalogos.oncode.app.br/social-integration/index` | Produtos → Login do Facebook → Configurações |
| **ID do Gerenciador de Negócios** | `1413634620074769` | Configurações → Básico (Verificação) |

---

## ⚡ Tabela Rápida: Copiar e Colar no Painel do TikTok

| Campo no TikTok Developers | Valor Exato para Copiar e Colar |
| :--- | :--- |
| **Redirect URI** | `https://catalogos.oncode.app.br/social-integration/tiktok-callback` |
| **Website URL** | `https://catalogos.oncode.app.br` |
| **Privacy Policy URL** | `https://catalogos.oncode.app.br/politica-privacidade` |
| **Terms of Service URL** | `https://catalogos.oncode.app.br/termos-de-uso` |

---

## 📌 1. Visão Geral: Desenvolvimento vs Produção

A Meta estrutura o acesso à API em duas fases distintas:

| Fase | Requisito de Verificação | Quem consegue usar? | Objetivo |
| :--- | :--- | :--- | :--- |
| **Modo de Desenvolvimento** | **Nenhum** (Imediato) | Administradores e Testadores cadastrados no App | Testes e homologação imediata de publicação de Cards e Vídeos (Feed, Stories, Reels) |
| **Modo de Produção (Live)** | **Verificação da Empresa** (`authorizations_verifications`) | Qualquer lojista ou afiliado externo do Pulse | Permitir que clientes terceiros conectem suas próprias páginas sem restrições |

> 💡 **Você já pode testar agora!** Não é necessário aguardar a aprovação da documentação da empresa para publicar os primeiros cards e vídeos da sua loja.

---

## 🚀 2. Fase 1: Obtenção das Credenciais e Teste Imediato

### Passo 2.1: Acessar o Portal de Desenvolvedores
1. Acesse **[developers.facebook.com/apps](https://developers.facebook.com/apps)**.
2. Faça login com sua conta do Facebook (a mesma que gerencia a Página do Facebook e o Instagram Business da loja).

### Passo 2.2: Criar ou Selecionar o Aplicativo
- Se o aplicativo já estiver criado, clique sobre ele.
- Se ainda não criou:
  1. Clique no botão verde **Criar aplicativo**.
  2. Selecione o tipo de aplicativo: escolha **Outro** ou **Empresarial (Business)**.
  3. Vincule à sua Empresa: Selecione a empresa **Only-code** (ID: `1413634620074769`).
  4. Nomeie o aplicativo (ex: `Pulse SaaS Marketing`).

### Passo 2.3: Coletar as Chaves do Aplicativo
1. No menu lateral esquerdo do aplicativo (App ID: `2061639194459307`), acesse **Configurações** → **Básico**.
2. Preencha os campos obrigatórios de **Conformidade e Privacidade**:
   - **URL da Política de Privacidade:**
     ```text
     https://catalogos.oncode.app.br/politica-privacidade
     ```
   - **URL dos Termos de Serviço:**
     ```text
     https://catalogos.oncode.app.br/termos-de-uso
     ```
   - **Exclusão de dados do usuário (URL de retorno ou instruções):**
     ```text
     https://catalogos.oncode.app.br/exclusao-dados
     ```
3. Copie os seguintes dados para o seu servidor:
   - **ID do Aplicativo** (App ID): `2061639194459307` (Já configurado no `.env` do Pulse)
   - **Chave Secreta do Aplicativo** (App Secret - clique em *Mostrar*)

### Passo 2.4: Configurar o Login do Facebook
1. No menu lateral esquerdo, vá em **Produtos** → **Login do Facebook para Empresas** (ou *Facebook Login*) → **Configurações**.
2. Ative a opção: **Login pelo navegador com fluxo do SDK JavaScript**.
3. No campo **Domínios permitidos para o SDK do JavaScript**, insira:
   ```text
   https://catalogos.oncode.app.br
   ```
4. No campo **URIs de redirecionamento do OAuth válidos**, insira:
   ```text
   https://catalogos.oncode.app.br/social-integration/index
   ```
5. Clique em **Salvar alterações**.

### Passo 2.5: Permissões Necessárias (Scopes)
Na seção **Revisão do aplicativo** → **Permissões e recursos**, confirme que seu app possui acesso aos seguintes escopos (em modo de desenvolvimento já ficam liberados para admins):
- `pages_show_list` — Listar páginas do Facebook gerenciadas pelo usuário.
- `pages_read_engagement` — Ler métricas e engajamento da página.
- `pages_manage_posts` — Publicar posts, fotos e carrosséis no Facebook.
- `instagram_basic` — Acessar a conta profissional do Instagram conectada à Página.
- `instagram_content_publish` — Publicar fotos (Feed), Reels e Stories no Instagram.

---

## 🏢 3. Fase 2: Verificação da Empresa (`authorizations_verifications`)

Link direto da sua empresa na Meta:
👉 [https://business.facebook.com/latest/settings/authorizations_verifications?business_id=1413634620074769](https://business.facebook.com/latest/settings/authorizations_verifications?business_id=1413634620074769)

Esta etapa serve para comprovar a existência legal da **Only-code** perante a Meta, liberando o app para modo público:

### 3.1 Documentação Exigida
1. **Dados Oficiais da Empresa:**
   - **Razão Social:** Only-code
   - **CNPJ:** `47.037.952/0001-43`
   - Endereço e telefone idênticos aos registrados na Receita Federal.
2. **Comprovante de CNPJ:**
   - Faça upload do **Cartão CNPJ** emitido recentemente pelo site da Receita Federal ou do **Contrato Social**.
3. **Comprovante de Endereço:**
   - Conta recente de consumo (energia elétrica, água ou internet) em nome de *Only-code* ou do titular responsável legal pelo CNPJ, com o mesmo endereço cadastrado.
4. **Confirmação de Contato:**
   - A Meta enviará um código de 6 dígitos para o e-mail corporativo ou telefone da empresa.

### 3.2 Verificação do Domínio (`oncode.app.br`)
1. No menu esquerdo de Configurações do Negócio, acesse **Adequação e segurança da marca** → **Domínios**.
2. Clique em **Adicionar** → Criar um novo domínio: `oncode.app.br`.
3. Escolha uma das opções de verificação:
   - **Opção A (Meta Tag HTML):** Copie a tag `<meta name="facebook-domain-verification" content="..." />` e insira no `<head>` do Pulse.
   - **Opção B (Registro DNS TXT):** Adicione uma entrada TXT no gerenciador DNS do domínio `oncode.app.br`.
4. Clique em **Verificar Domínio**.

### 3.3 Autenticação de Dois Fatores (2FA)
1. Na tela de configurações da empresa, em **Informações da empresa**, certifique-se de que a **Autenticação de dois fatores** está configurada como:
   - *Obrigatório para todos os administradores* (ou *Obrigatório para todos*).

### 3.4 Vincular o Aplicativo ao Gerenciador de Negócios
1. No menu lateral em **Contas** → **Aplicativos**.
2. Se o app ainda não estiver na lista, clique em **Adicionar** → **Conectar um ID do aplicativo**.
3. Cole o ID do aplicativo criado no Meta for Developers.

---

## ⚙️ 4. Onde Inserir as Chaves no Pulse

Com o **ID do Aplicativo** e a **Chave Secreta** em mãos, adicione-os no arquivo de ambiente do servidor:

### No arquivo `.env`:
```ini
# Meta Graph API (Facebook & Instagram)
META_APP_ID="SEU_APP_ID_AQUI"
META_APP_SECRET="SUA_CHAVE_SECRETA_AQUI"
```

O sistema já está programado para carregar automaticamente essas chaves no arquivo [config/params.php](file:///srv/http/pulse/config/params.php):
```php
'meta_app_id' => $_ENV['META_APP_ID'] ?? getenv('META_APP_ID') ?: '',
'meta_app_secret' => $_ENV['META_APP_SECRET'] ?? getenv('META_APP_SECRET') ?: '',
'meta_api_version' => 'v19.0',
```

---

## 📱 5. Como Testar e Usar no Pulse

Após salvar as chaves no `.env`:

1. Acesse o menu **📢 Marketing Social** na barra de navegação ou pelo link:
   ```text
   https://catalogos.oncode.app.br/social-integration/index
   ```
2. Clique no botão **Conectar Facebook / Instagram**:
   - Uma janela oficial da Meta será aberta solicitando autorização para suas Páginas e Contas do Instagram.
   - Conclua a autorização e veja a conta conectada com status **Ativo**.
3. **Publicação de Vídeos:**
   - Acesse o Estúdio de Vídeos (`/vendas/produto-video/studio`).
   - Crie um vídeo e clique em **🚀 Publicar nas Redes Sociais**.
   - O vídeo será postado no Instagram Reels ou Facebook.
4. **Publicação de Cards:**
   - Acesse a página de qualquer produto (`/vendas/produto/view?id=...`).
   - Na seção de Cards, clique no ícone **📢** em qualquer card ou selecione múltiplos para enviar um **Carrossel**.
   - O sistema converte automaticamente imagens WebP para JPEG e publica instantaneamente.
