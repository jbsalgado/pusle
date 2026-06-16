# Sistema de Notificações de Contas a Pagar

## Visão Geral

O sistema de notificações envia e-mails automáticos para alertar sobre vencimentos de contas a pagar.

## Funcionalidades

### Tipos de Notificações

1. **⏰ Vencendo em 3 dias** - Alerta preventivo
2. **🔔 Vence hoje** - Alerta urgente
3. **🚨 Vencida há 1 dia** - Alerta crítico

### Informações no E-mail

- Descrição da conta
- Valor a pagar
- Data de vencimento
- Fornecedor
- Categoria
- Link direto para a conta no sistema

## Uso

### Comando Manual

```bash
# Enviar todas as notificações
php yii notificacao-contas/enviar

# Testar com uma conta específica
php yii notificacao-contas/testar <ID_DA_CONTA>
```

### Configuração do Cron

Adicione ao crontab para execução automática diária às 8h:

```bash
# Editar crontab
crontab -e

# Adicionar linha:
0 8 * * * cd /srv/http/pulse && php yii notificacao-contas/enviar >> /var/log/pulse-notificacoes.log 2>&1
```

### Logs

Os logs são salvos em `/var/log/pulse-notificacoes.log` (se configurado no cron).

Para visualizar:

```bash
tail -f /var/log/pulse-notificacoes.log
```

## Configuração de E-mail

### Verificar Configuração

Edite `/srv/http/pulse/config/web.php` ou `/srv/http/pulse/config/console.php`:

```php
'components' => [
    'mailer' => [
        'class' => 'yii\swiftmailer\Mailer',
        'useFileTransport' => false, // IMPORTANTE: false para enviar e-mails reais
        'transport' => [
            'class' => 'Swift_SmtpTransport',
            'host' => 'smtp.gmail.com',  // Servidor SMTP
            'username' => 'seu-email@gmail.com',
            'password' => 'sua-senha-app',
            'port' => '587',
            'encryption' => 'tls',
        ],
    ],
],

'params' => [
    'adminEmail' => 'noreply@pulse.com',
    'siteUrl' => 'http://localhost/pulse', // URL do sistema
],
```

### Teste de Envio

```bash
# Criar uma conta de teste
# Depois executar:
php yii notificacao-contas/testar <ID_DA_CONTA>
```

## Requisitos

1. **E-mail do Usuário**: Cada usuário deve ter e-mail configurado na tabela `prest_usuarios`
2. **Mailer Configurado**: Componente `mailer` do Yii2 deve estar configurado
3. **Cron Access**: Permissão para configurar cron jobs no servidor

## Troubleshooting

### E-mails não são enviados

1. Verificar se `useFileTransport` está `false`
2. Verificar credenciais SMTP
3. Verificar se usuários têm e-mail cadastrado
4. Checar logs de erro do Yii2

### Comando não executa no cron

1. Verificar permissões do arquivo
2. Verificar caminho do PHP no cron
3. Testar comando manualmente primeiro
4. Verificar logs do cron: `grep CRON /var/log/syslog`

## Exemplo de Saída

```
=== Iniciando envio de notificações de contas a pagar ===
Data/Hora: 2026-02-10 08:00:00

📅 Verificando contas vencendo em 3 dias...
   Encontradas 2 conta(s) para notificar
   ✓ E-mail enviado para usuario@example.com - Conta #abc-123
   ✓ E-mail enviado para outro@example.com - Conta #def-456

📅 Verificando contas vencendo hoje...
   Encontradas 1 conta(s) para notificar
   ✓ E-mail enviado para usuario@example.com - Conta #ghi-789

📅 Verificando contas vencidas há 1 dia...
   Encontradas 0 conta(s) para notificar

============================================================
✅ Total de notificações enviadas: 3
============================================================
```

## Personalização

### Alterar Dias de Antecedência

Edite o método `actionEnviar()` em `NotificacaoContasController.php`:

```php
// Alterar de 3 para 7 dias
$resultado7Dias = $this->enviarNotificacoesVencimento(7, 'vencendo');
```

### Customizar Template de E-mail

Edite o método `enviarEmail()` para modificar o HTML do e-mail.

### Adicionar Outros Canais

Implemente métodos adicionais para:

- SMS
- WhatsApp
- Notificações push
- Telegram

## Segurança

- ✅ E-mails são enviados apenas para o usuário dono da conta
- ✅ Links contêm ID da conta (requer autenticação no sistema)
- ✅ Senhas SMTP devem estar em arquivo de configuração protegido
- ✅ Logs não expõem informações sensíveis
