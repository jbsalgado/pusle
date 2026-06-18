# Análise de Vulnerabilidades e Inseguranças - Pulse ERP

**Data:** 18/06/2026  
**Escopo:** Código-fonte completo (PHP/Yii2, JavaScript, Python)  
**Classificação:** Crítica, Alta, Média, Baixa

---

## CRÍTICA

### C-01: JWT forjável no ClienteController (sem assinatura)

| Campo | Detalhe |
|---|---|
| **Arquivo** | `modules/api/controllers/ClienteController.php:389-399` |
| **Método** | `gerarTokenJWT()` |
| **Descrição** | O token JWT é gerado como `base64_encode(json_encode($payload))` — **sem assinatura HMAC, sem qualquer criptografia ou verificação de integridade**. Qualquer pessoa pode forjar um token e se passar por qualquer cliente. |
| **Impacto** | Acesso total à API como qualquer cliente da base. Leitura de dados sigilosos, criação de pedidos, consulta de cobranças. |
| **Correção** | Reimplementar usando `components/JwtHelper.php` ou `firebase/php-jwt` com assinatura HMAC-SHA256. |

### C-02: Chave secreta JWT e sessão padrão ("your-secret-key-here-change-in-production")

| Campo | Detalhe |
|---|---|
| **Arquivo** | `config/web.php:26` |
| **Descrição** | `cookieValidationKey` definido como `'your-secret-key-here-change-in-production'`. Esta chave é usada para assinar JWT em `Usuario.php:244` e `JwtHelper.php`, além de criptografar cookies de sessão. |
| **Impacto** | Qualquer pessoa que conheça esta chave (pública no código-fonte) pode forjar tokens JWT válidos e sequestrar sessões de qualquer usuário, incluindo administradores. |
| **Correção** | Gerar chave aleatória forte (32+ caracteres) via `openssl_random_pseudo_bytes()` e definir via variável de ambiente, não no código. |

### C-03: Senha do certificado NFe hardcoded no código

| Campo | Detalhe |
|---|---|
| **Arquivo** | `config/params.php:31` |
| **Descrição** | `'nfe_cert_password' => 'onlycode2026'` — senha do certificado digital A1 (NFe/NFCe) em texto plano no repositório. |
| **Impacto** | Exposição completa do certificado digital, permitindo emissão fraudulenta de notas fiscais em nome de qualquer loja do sistema. |
| **Correção** | Mover para variável de ambiente ou cofre de senhas (Hashicorp Vault, AWS Secrets Manager). |

---

## ALTA

### A-01: Injeção de comando no BackupController

| Campo | Detalhe |
|---|---|
| **Arquivo** | `controllers/BackupController.php:66-71` |
| **Descrição** | `exec()` construído com concatenação direta de strings: `pg_dump ... "postgresql://{$db_user}:{$db_password}@{$db_host}:{$db_port}/{$db_name}"` — credenciais do banco expostas em linha de comando (visível via `ps aux`). |
| **Impacto** | Qualquer processo no servidor pode capturar a senha do PostgreSQL. A string do comando permite injeção se qualquer variável de ambiente for contaminada. |
| **Correção** | Usar arquivo `.pgpass` ou variáveis de ambiente PGPASSWORD. Sanitizar todos os parâmetros. |

### A-02: Modo debug (YII_DEBUG) ativado

| Campo | Detalhe |
|---|---|
| **Arquivo** | `.env:5` |
| **Descrição** | `YII_DEBUG="true"` em produção ativa o Yii2 Debug Toolbar e stack traces detalhados. |
| **Impacto** | Exposição de variáveis de ambiente, configuração do banco, caminhos do sistema, estrutura de arquivos, queries SQL e tokens de sessão em caso de erro. |
| **Correção** | Definir `YII_DEBUG=false` e `YII_ENV=prod` no ambiente de produção. |

### A-03: Webhooks sem verificação de origem

| Campo | Detalhe |
|---|---|
| **Arquivos** | `modules/api/controllers/MercadoPagoController.php:586-650`, `modules/api/controllers/AsaasController.php:470-558`, `controllers/WebhookController.php:16-89` |
| **Descrição** | Webhooks não validam a origem da requisição (IP, assinatura HMAC, ou token secreto compartilhado). Qualquer requisição POST para estas URLs é processada como webhook legítimo. |
| **Impacto** | Injeção falsa de eventos de pagamento, confirmação de transações inexistentes, adulteração do status de cobranças. |
| **Correção** | Validar assinatura HMAC dos webhooks (Mercado Pago envia `X-Signature`, Asaas envia `asaas-access-token`). Validar IP de origem contra ranges oficiais. |

### A-04: Ausência de rate limiting na API

| Campo | Detalhe |
|---|---|
| **Arquivos** | Todos os controllers em `modules/api/controllers/` |
| **Descrição** | Nenhum endpoint público ou autenticado possui limitação de taxa de requisições. |
| **Impacto** | Ataques de força bruta em login, enumeração de CPFs (via `cliente/buscar-cpf`), esgotamento de recursos (DoS), scraping de dados. |
| **Correção** | Implementar `yii\filters\RateLimiter` ou middleware nginx/cloudflare. |

### A-05: Chave da Evolution API hardcoded no .env

| Campo | Detalhe |
|---|---|
| **Arquivo** | `.env:9` |
| **Descrição** | `EVOLUTION_API_KEY=429683C4C977415CAAFCCE10F7D57E11` — chave de API do serviço de WhatsApp em texto plano no repositório. |
| **Impacto** | Acesso não autorizado à fila de mensagens WhatsApp, envio de mensagens em nome de qualquer loja, vazamento de conversas de clientes. |
| **Correção** | Gerar chave forte via `openssl rand -hex 32` e armazenar em variável de ambiente fora do repositório. |

---

## MÉDIA

### M-01: Tokens de pagamento armazenados em texto plano no banco

| Campo | Detalhe |
|---|---|
| **Arquivo** | `models/Usuario.php` (campos na tabela `prest_usuarios`) |
| **Campos expostos** | `mercadopago_access_token`, `mp_access_token`, `mp_refresh_token`, `mp_public_key`, `asaas_api_key` |
| **Descrição** | Tokens de integração com gateways de pagamento armazenados sem qualquer criptografia no banco PostgreSQL. |
| **Impacto** | Em caso de SQL injection ou vazamento do banco, atacantes têm acesso a tokens financeiros para criar cobranças, receber pagamentos e consultar transações. |
| **Correção** | Criptografar com `Yii::$app->security->encryptByPassword()` usando chave mestra em variável de ambiente. |

### M-02: User.php com comparação de senha em texto plano

| Campo | Detalhe |
|---|---|
| **Arquivo** | `models/User.php:100` |
| **Descrição** | Método `validatePassword()` usa `$this->password === $password` — comparação direta de strings, sem hash. |
| **Impacto** | Médio (código boilerplate não utilizado ativamente), mas se qualquer fluxo acidental usar este modelo, senhas seriam armazenadas e comparadas em texto plano. |
| **Correção** | Remover o arquivo ou reimplementar com `Yii::$app->security->validatePassword()`. |

### M-03: Endpoints API com autenticação opcional sem validação adequada

| Campo | Detalhe |
|---|---|
| **Arquivos** | `PedidoController.php:49-117`, `ProdutoController.php:38-144`, `WhatsappController.php:55` |
| **Descrição** | Vários endpoints definem `authenticator` como `optional`, permitindo acesso anônimo a funcionalidades de criação de pedidos e consulta de dados. |
| **Impacto** | Criação de pedidos fraudulentos, scraping de catálogo de produtos, envio de mensagens WhatsApp não autenticado. |
| **Correção** | Exigir autenticação Bearer obrigatória em todos os endpoints de escrita. Revisar necessidade de endpoints públicos. |

### M-04: Exposição de dados sensíveis em logs e respostas de erro

| Campo | Detalhe |
|---|---|
| **Arquivos** | Múltiplos controllers (padrão Yii2 debug) |
| **Descrição** | Com `YII_DEBUG=true`, erros de banco expõem credenciais, estrutura do schema, e dados internos. Mesmo em produção, exceções não tratadas podem vazar informação. |
| **Impacto** | Vazamento de informações internas auxilia ataques direcionados. |
| **Correção** | Além de desligar debug, implementar handler de erro personalizado que sanitiza mensagens. |

### M-05: JWT com expiração excessivamente longa (30 dias)

| Campo | Detalhe |
|---|---|
| **Arquivo** | `models/Usuario.php:250` |
| **Descrição** | Token JWT gerado com `'exp' => time() + (3600 * 24 * 30)` — 30 dias de validade. |
| **Impacto** | Token vazado ou roubado permanece válido por até 30 dias, permitindo acesso prolongado não autorizado. |
| **Correção** | Reduzir para no máximo 24h e implementar refresh token com rotação. |

---

## BAIXA

### B-01: CSRF desabilitado em webhooks sem contramedida

| Campo | Detalhe |
|---|---|
| **Arquivo** | `controllers/WebhookController.php:16` |
| **Descrição** | `$enableCsrfValidation = false` é necessário para webhooks POST, mas nenhuma outra proteção (token de verificação, origem) foi implementada. |
| **Impacto** | Baixo (já coberto por A-03), mas vale documentar a falta de defesa em profundidade. |
| **Correção** | Implementar verificação de assinatura HMAC ou token compartilhado. |

### B-02: Dockerfile com PHP 7.1 (desatualizado)

| Campo | Detalhe |
|---|---|
| **Arquivo** | `docker-compose.yml` |
| **Descrição** | Ambiente Docker configurado para PHP 7.1, mas `composer.json` requer `"php": ">=8.4.5"`. Discrepância indica ambiente local não utilizado ou desatualizado. |
| **Impacto** | Se usado para desenvolvimento, executa versão do PHP com múltiplas vulnerabilidades conhecidas sem suporte de segurança. |
| **Correção** | Atualizar para PHP 8.4+ ou remover docker-compose.yml se não utilizado. |

### B-03: CORS permissivo em controllers da API

| Campo | Detalhe |
|---|---|
| **Arquivos** | Vários controllers em `modules/api/controllers/` |
| **Descrição** | Headers CORS definidos por controller sem validação de origem específica. Alguns controllers usam `Access-Control-Allow-Origin: *` ou derivam do header `Origin` sem validação. |
| **Impacto** | Qualquer site malicioso pode fazer requisições AJAX para a API (embora CSRF não seja aplicável com Bearer token, reduz a segurança geral). |
| **Correção** | Centralizar configuração CORS no `BaseController` com whitelist de origens permitidas. |

### B-04: Backup via exec() expõe credenciais em ps aux

| Campo | Detalhe |
|---|---|
| **Arquivo** | `controllers/BackupController.php:69` |
| **Descrição** | Além do risco de injeção (A-01), o comando `pg_dump` com senha na URI fica visível na tabela de processos do sistema durante a execução. |
| **Impacto** | Qualquer usuário com acesso ao servidor pode ver credenciais do banco via `ps aux`. |
| **Correção** | Usar `~/.pgpass` ou variável `PGPASSWORD`. |

### B-05: Sem proteção contra ataques de temporização (timing attacks)

| Campo | Detalhe |
|---|---|
| **Arquivos** | `models/LoginForm.php`, `modules/api/controllers/AuthController.php` |
| **Descrição** | Comparação de senha e verificação de CPF/email usam comparação direta de strings, vulnerável a timing attacks para enumeração de usuários válidos. |
| **Impacto** | Baixo (ataque requer rede local ou latência muito baixa), mas possível enumerar CPFs/emails cadastrados. |
| **Correção** | Usar `Yii::$app->security->compareString()` para comparações sensíveis. |

---

## Sumário Executivo

| Gravidade | Quantidade | Principais Riscos |
|---|---|---|
| **Crítica** | 3 | JWT forjável, chave secreta padrão, certificado NFe exposto |
| **Alta** | 5 | Shell injection, debug ligado, webhooks sem verificação, sem rate limit, API key WhatsApp |
| **Média** | 5 | Tokens PGTO em texto plano, senha texto plano legado, auth opcional, logs expondo dados, JWT 30d |
| **Baixa** | 5 | CSRF webhooks, Docker PHP antigo, CORS permissivo, backup expõe creds, timing attack |
| **Total** | **18** | |

### Ações Imediatas Recomendadas

1. **Trocar `cookieValidationKey`** em produção para chave aleatória forte armazenada em variável de ambiente.
2. **Corrigir `ClienteController::gerarTokenJWT()`** para usar assinatura HMAC-SHA256.
3. **Mover senha do certificado NFe** para variável de ambiente.
4. **Desligar `YII_DEBUG`** em produção.
5. **Implementar validação de assinatura** nos webhooks Mercado Pago e Asaas.
6. **Implementar rate limiting** em endpoints públicos de API.
