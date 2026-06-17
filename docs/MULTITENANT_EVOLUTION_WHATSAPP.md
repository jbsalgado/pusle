# PULSE-PLUS — Arquitetura Multi-Empresa e Integração Evolution API Go

> **Versão:** 1.0 — Junho/2026  
> **Contexto:** Evolution API Go (Engine v0.7.1) · Yii2 Basic · PostgreSQL

---

## 1. Como o Sistema Trabalha com Múltiplas Empresas (Multi-Tenant)

### 1.1 Conceito de Tenant no PULSE-PLUS

O PULSE-PLUS adota um modelo **single-database multi-tenant**: um único banco
de dados PostgreSQL (`pulse_plus`) hospeda os dados de **todas** as empresas
cadastradas. O isolamento entre elas é feito por **chave de escopo** em cada
tabela, e não por schemas ou bancos separados.

A tabela raiz do modelo é `prest_usuarios`. **Cada linha nessa tabela representa
uma empresa/loja** (o "dono da loja"), identificada por um UUID gerado pelo
PostgreSQL (`gen_random_uuid()`).

```
prest_usuarios
──────────────
id   (UUID)  ← identificador único da empresa / tenant
nome
cpf
email
eh_dono_loja (boolean) ← true para donos de loja
...
```

### 1.2 Chave de Escopo: `usuario_id`

Todas as tabelas de dados operacionais possuem uma coluna `usuario_id` (UUID)
com **Foreign Key** para `prest_usuarios(id)`. Essa coluna funciona como a
chave de escopo que isola os dados de cada empresa.

```
prest_vendas          prest_produtos        prest_clientes
─────────────         ──────────────        ───────────────
id                    id                    id
usuario_id (FK) ──►   usuario_id (FK) ──►   usuario_id (FK)
...                   ...                   ...
```

Quando a Empresa A acessa `/vendas/inicio`, o sistema busca
`WHERE usuario_id = 'uuid-da-empresa-a'` automaticamente — os dados
da Empresa B nunca aparecem.

### 1.3 Como a Sessão Identifica a Empresa Ativa

O Yii2 usa a interface `IdentityInterface` implementada pelo model `app\models\Usuario`
(tabela `prest_usuarios`). Após o login, o framework armazena o UUID da empresa
logada na sessão. Em qualquer controller, o ID do tenant ativo é:

```php
// Forma canônica — retorna o UUID da empresa logada
$empresaId = Yii::$app->user->id;

// Equivalente (acesso ao objeto completo)
$empresa = Yii::$app->user->identity;  // instância de app\models\Usuario
$empresaId = $empresa->id;
```

### 1.4 Padrão de Isolamento nos Controllers

Todos os controllers do sistema seguem o mesmo padrão:

```php
// Exemplo: listar produtos da empresa ativa
$usuarioId = Yii::$app->user->id;

$produtos = Produto::find()
    ->where(['usuario_id' => $usuarioId, 'ativo' => true])
    ->all();
```

```php
// Exemplo: proteger update/delete contra acesso cruzado
$model = Venda::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id]);
if ($model === null) {
    throw new ForbiddenHttpException('Acesso negado.');
}
```

### 1.5 Hierarquia de Usuários por Empresa

Cada empresa possui seus próprios colaboradores na tabela `prest_colaboradores`.
O campo `usuario_id` nessa tabela aponta para o UUID do **dono da loja** (a empresa),
não do colaborador em si.

```
Empresa A (prest_usuarios.id = uuid-A)
  └── Colaborador 1 (usuario_id = uuid-A)
  └── Colaborador 2 (usuario_id = uuid-A)
  └── Colaborador 3 (usuario_id = uuid-A)

Empresa B (prest_usuarios.id = uuid-B)
  └── Colaborador 4 (usuario_id = uuid-B)
```

---

## 2. Integração Evolution API Go por Empresa

### 2.1 Arquitetura da Integração

Cada empresa do PULSE-PLUS possui sua própria **instância WhatsApp isolada**
no motor Evolution API Go. O PULSE-PLUS não compartilha instâncias entre
clientes — cada empresa se conecta com seu próprio número.

```
┌───────────────────────────────────────────────────────────┐
│                     PULSE-PLUS (Yii2)                     │
│                                                           │
│  Empresa A ──► EvolutionService ──► instância A (Go)      │
│  Empresa B ──► EvolutionService ──► instância B (Go)      │
│  Empresa C ──► EvolutionService ──► instância C (Go)      │
│                                                           │
│  Tabela: pulse_whatsapp_config (1 linha por empresa)      │
└───────────────────────────────────────────────────────────┘
                              │
                              ▼
                 ┌────────────────────────┐
                 │  Evolution API Go      │
                 │  Engine v0.7.1         │
                 │  (localhost:8083)      │
                 └────────────────────────┘
```

### 2.2 Tabela de Persistência: `pulse_whatsapp_config`

Um registro por empresa. Armazena os metadados da sessão WhatsApp:

| Coluna          | Tipo         | Descrição                                             |
|-----------------|--------------|-------------------------------------------------------|
| `id`            | SERIAL PK    | Chave primária sequencial                             |
| `empresa_id`    | UUID (UK/FK) | UUID da empresa em `prest_usuarios` (1:1)             |
| `instance_name` | VARCHAR(255) | Nome da instância no motor Go (padrão canônico)       |
| `token`         | VARCHAR(255) | API Key específica da instância, retornada pelo Go    |
| `status`        | VARCHAR(50)  | `'CONNECTED'` ou `'DISCONNECTED'`                     |
| `created_at`    | TIMESTAMP    | Data de criação (auto)                                |
| `updated_at`    | TIMESTAMP    | Última atualização (auto via trigger)                 |

### 2.3 Padrão Canônico do Nome de Instância

O nome de cada instância no motor Go é gerado deterministicamente a partir
do UUID da empresa:

```
UUID da empresa: 3f2504e0-4f89-11d3-9a0c-0305e82c3301
Remove hífens:   3f2504e04f8911d39a0c0305e82c3301
Primeiros 12:    3f2504e04f89
Nome final:      pulse_empresa_id_3f2504e04f89
```

**Lógica PHP (EvolutionService):**
```php
private function buildInstanceName(string $empresaId): string
{
    $short = substr(str_replace('-', '', $empresaId), 0, 12);
    return "pulse_empresa_id_{$short}";
}
```

Isso garante que duas empresas com UUIDs diferentes **nunca** gerarão o mesmo
nome de instância, e o nome é sempre estável para o mesmo UUID.

---

## 3. Configuração Passo a Passo

### 3.1 Pré-requisitos

| Requisito               | Valor esperado                     |
|-------------------------|------------------------------------|
| Evolution API Go rodando| `http://localhost:8083` (ou outra porta) |
| Global API Key definida | Configurada no motor Go ao iniciar |
| PHP extension           | `yii2-httpclient` instalado via composer |
| Migration executada     | `m260608_020000_create_pulse_whatsapp_config` aplicada |

### 3.2 Configurar `config/params.php`

Abra o arquivo e localize o bloco `evolution`. Atualize com os valores reais:

```php
'evolution' => [
    // URL base do motor Go (sem barra final)
    // Em produção, pode ser http://72.61.221.180:8083 ou um domínio próprio
    'baseUrl'      => 'http://localhost:8083',

    // Global API Key configurada no motor Go
    // Geralmente definida na variável de ambiente AUTHENTICATION_API_KEY do Go
    'globalApiKey' => 'sua-chave-global-aqui',
],
```

> **⚠️ Atenção:** Não commite a `globalApiKey` real no repositório. Em produção,
> use variáveis de ambiente ou um arquivo `.env` não versionado.

### 3.3 Verificar o Módulo no `config/web.php`

O módulo já está registrado:

```php
'modules' => [
    // ... outros módulos
    'evolution' => [
        'class' => 'app\modules\evolution\Module',
    ],
],
```

Nenhuma alteração necessária aqui.

### 3.4 Confirmar que a Migration Foi Executada

```bash
# Verifica o histórico de migrations
php yii migrate/history --migrationPath=@app/migrations 5

# Saída esperada:
# (2026-06-08 ...) m260608_020000_create_pulse_whatsapp_config
```

Se não aparecer, execute:

```bash
php yii migrate --migrationPath=@app/migrations --interactive=0
```

---

## 4. Fluxo de Uso por Empresa

### 4.1 Primeira Conexão (Pareamento)

```
1. Empresa loga no PULSE-PLUS
   → Yii::$app->user->id retorna o UUID da empresa

2. Acessa /evolution/config
   → ConfigController::actionIndex() consulta checkStatus()
   → Status: DISCONNECTED (nenhuma instância criada ainda)
   → View exibe botão "Conectar WhatsApp"

3. Clica em "Conectar WhatsApp"
   → ConfigController::actionConnect()
   → EvolutionService::createInstance($empresaId)
   → POST /instance/create na API Go com apiKey global
   → Go retorna: token da instância + QR Code em Base64
   → Salva token e instance_name em pulse_whatsapp_config
   → View exibe QR Code

4. Empresa escaneia o QR Code com o celular

5. JavaScript faz polling a cada 4s para /evolution/config/check-status-ajax
   → EvolutionService::checkStatus($empresaId)
   → GET /instance/all na API Go
   → Localiza instância pelo instanceName
   → Retorna { "connected": true }

6. JavaScript redireciona para /evolution/config
   → Painel exibe badge verde "Conectado"
```

### 4.2 Envio de Notificação Automática (Backend)

```php
use app\modules\evolution\services\EvolutionService;

// Em qualquer ponto do sistema (controller, command, etc.)
$service   = new EvolutionService();
$empresaId = Yii::$app->user->id; // ou UUID fixo em commands CLI

$enviado = $service->sendMessage(
    $empresaId,
    '81999998888',       // número do destinatário
    "✅ Venda #1234 confirmada!\nValor: R$ 250,00"
);

if (!$enviado) {
    Yii::warning("Falha ao enviar WhatsApp para empresa {$empresaId}", __METHOD__);
}
```

O `sendMessage` recupera o token específico da empresa no banco local e
envia usando o header `apikey` (minúsculo) — nunca a chave global.

### 4.3 Desconexão

```
1. Empresa acessa /evolution/config
2. Clica em "Desconectar"
   → ConfigController::actionDisconnect()
   → EvolutionService::deleteInstance($empresaId)
   → DELETE /instance/delete/{instanceName} na API Go
   → Atualiza pulse_whatsapp_config: status=DISCONNECTED, token=''
3. Redireciona para o painel com status atualizado
```

---

## 5. Regras de Autenticação HTTP (Homologadas)

Este é um ponto crítico da integração. O motor Go usa **dois headers diferentes**
dependendo do tipo de ação:

| Ação                          | Header       | Valor              |
|-------------------------------|--------------|--------------------|
| Criar instância               | `apiKey`     | Global API Key     |
| Listar instâncias (`/all`)    | `apiKey`     | Global API Key     |
| Deletar instância             | `apiKey`     | Global API Key     |
| Enviar mensagem (`/send/text`)| `apikey`     | Token da instância |

> **`apiKey`** (K maiúsculo) = autenticação **administrativa global**  
> **`apikey`** (tudo minúsculo) = autenticação da **instância específica**

Confundir os dois causa erros HTTP 401/403 silenciosos.

---

## 6. Estrutura de Arquivos do Módulo

```
modules/evolution/
├── Module.php                          ← Bootstrap (namespace, controllerNamespace)
├── controllers/
│   └── ConfigController.php           ← UI do painel WhatsApp
│       ├── actionIndex()              ← Painel de status
│       ├── actionConnect()            ← Gera QR Code
│       ├── actionDisconnect()         ← Desconecta instância
│       └── actionCheckStatusAjax()    ← Endpoint JSON p/ polling
├── models/
│   └── WhatsappConfig.php             ← ActiveRecord (pulse_whatsapp_config)
├── services/
│   └── EvolutionService.php           ← Integração HTTP com o motor Go
│       ├── createInstance()
│       ├── sendMessage()
│       ├── checkStatus()
│       ├── deleteInstance()
│       ├── buildInstanceName()        ← (private) gerador de nome canônico
│       └── sanitizePhoneNumber()      ← (private) normalização DDI 55
└── views/config/
    ├── index.php                       ← Painel (badge conectado/desconectado)
    └── connect.php                     ← QR Code + polling JS nativo
```

---

## 7. Adicionando Envio de WhatsApp em Outras Partes do Sistema

Para enviar mensagens de qualquer módulo existente (ex: ao fechar uma venda):

```php
// Em VendaController, após salvar a venda:
use app\modules\evolution\services\EvolutionService;

$service = new EvolutionService();
$service->sendMessage(
    Yii::$app->user->id,
    $cliente->telefone,
    "Olá {$cliente->nome}! Sua venda #{$venda->id} foi registrada com sucesso. 🎉"
);
```

O método é **fire-and-forget seguro**: em caso de falha (API Go fora do ar,
token inválido, etc.), o erro é logado silenciosamente em `runtime/logs/app.log`
via `Yii::error()` e o sistema **nunca trava**.

---

## 8. Diagnóstico e Resolução de Problemas

### Motor Go não responde

```bash
# Verifica se o processo Go está rodando
curl -s http://localhost:8083/instance/all \
  -H "apiKey: sua-chave-global-aqui"

# Saída esperada: [] (array vazio se nenhuma instância criada)
# Se Connection refused: o motor Go não está rodando
```

### QR Code não aparece

1. Verifique o log do Yii2: `runtime/logs/app.log`
2. Busque por `EvolutionService::createInstance`
3. O log mostrará o status HTTP e o corpo da resposta da API Go

### Token expirado / instância desapareceu do Go

A instância pode ser removida do motor Go manualmente ou por reinicialização.
Nesse caso, clique em **"Reconectar / Atualizar QR Code"** na tela de painel.
Isso chama `createInstance()` novamente, que cria uma nova instância e salva
o novo token no banco.

### Status não sincroniza automaticamente

O `checkStatus()` é chamado toda vez que a empresa acessa `/evolution/config`.
O banco local (`pulse_whatsapp_config.status`) é atualizado a cada visita.
Para sincronização em background, implemente um Yii2 Console Command que
itere sobre todos os registros da tabela e chame `checkStatus()` por empresa.

---

## 9. Referência Rápida de Endpoints

| Módulo/Rota                              | Descrição                           |
|------------------------------------------|-------------------------------------|
| `GET  /evolution/config`                 | Painel de status da empresa logada  |
| `GET  /evolution/config/connect`         | Gera e exibe o QR Code              |
| `POST /evolution/config/disconnect`      | Desconecta o WhatsApp               |
| `GET  /evolution/config/check-status-ajax` | JSON `{"connected": bool}` (Ajax) |

---

*Documento gerado automaticamente pela análise do código-fonte do PULSE-PLUS.*  
*Atualizar este documento a cada mudança significativa na arquitetura multi-tenant ou no módulo evolution.*
