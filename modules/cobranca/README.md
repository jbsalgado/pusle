# Módulo de Automação de Cobranças

Sistema automatizado de cobranças via WhatsApp para o Sistema Pulse.

## 📋 Funcionalidades

- ✅ Envio automático de lembretes de pagamento
- ✅ Integração com Z-API (WhatsApp Business)
- ✅ Templates personalizáveis de mensagens
- ✅ Histórico completo de envios
- ✅ Agendamento via cron job
- ✅ Retry automático em caso de falha

## 🚀 Como Usar

### 1. Configurar Z-API

1. Acesse [z-api.io](https://www.z-api.io/) e crie uma conta
2. Copie o **Instance ID** e o **Token**
3. Conecte seu WhatsApp escaneando o QR Code

### 2. Configurar no Sistema

1. Acesse: `/cobranca/configuracao/index`
2. Cole as credenciais da Z-API
3. Clique em "Testar Conexão"
4. Configure os parâmetros de envio
5. Marque "Ativar automação"
6. Salve

### 3. Personalizar Templates

1. Acesse: `/cobranca/template/index`
2. Edite os 3 templates disponíveis:
   - **3 Dias Antes** - Lembrete amigável
   - **Dia do Vencimento** - Aviso urgente
   - **Após Vencimento** - Cobrança
3. Use as variáveis disponíveis
4. Visualize o preview em tempo real
5. Salve

### 4. Configurar Cron Job

```bash
# Editar crontab
crontab -e

# Adicionar linha (executar diariamente às 9h)
0 9 * * * cd /srv/http/pulse && php yii cobranca/processar >> /var/log/cobranca.log 2>&1
```

### 5. Acompanhar Histórico

1. Acesse: `/cobranca/historico/index`
2. Veja estatísticas de envio
3. Filtre por tipo, status, data
4. Reenvie cobranças com falha

## 📊 Estrutura do Módulo

```
modules/cobranca/
├── Module.php
├── controllers/
│   ├── ConfiguracaoController.php
│   ├── TemplateController.php
│   └── HistoricoController.php
├── models/
│   ├── CobrancaConfiguracao.php
│   ├── CobrancaTemplate.php
│   └── CobrancaHistorico.php
├── components/
│   ├── WhatsAppService.php
│   └── CobrancaProcessor.php
└── views/
    ├── configuracao/index.php
    ├── template/
    │   ├── index.php
    │   └── update.php
    └── historico/
        ├── index.php
        └── view.php
```

## 🗄️ Banco de Dados

### Tabelas

- `prest_cobranca_configuracao` - Configurações por usuário
- `prest_cobranca_template` - Templates de mensagens
- `prest_cobranca_historico` - Histórico de envios

### Migration

```bash
psql -U postgres -d pulse -f sql/postgres/021_create_cobranca_tables.sql
```

## 🔧 Comandos Console

```bash
# Processar cobranças do dia
php yii cobranca/processar

# Testar sistema
php yii cobranca/teste
```

## 📝 Variáveis de Template

- `{nome}` - Nome do cliente
- `{valor}` - Valor da parcela (formatado)
- `{vencimento}` - Data de vencimento (dd/mm/yyyy)
- `{parcela}` - Número da parcela (ex: 1/12)
- `{empresa}` - Nome da empresa

## 🎯 Fluxo de Processamento

1. Cron executa comando diariamente
2. Sistema busca configurações ativas
3. Para cada configuração:
   - Busca parcelas com vencimento em X dias
   - Verifica se já foi enviada
   - Busca template ativo
   - Substitui variáveis
   - Envia via WhatsApp
   - Registra histórico

## ⚙️ Configurações Padrão

- **Dias antes:** 3
- **Enviar no dia:** Sim
- **Dias após:** 1
- **Horário:** 09:00

## 🔒 Segurança

- Token criptografado no banco
- Validação de telefone
- Verificação de duplicidade
- Access control em todos os controllers
- Logs de todas as tentativas

## 📱 Formato de Telefone

O sistema aceita e formata automaticamente:

```
81999999999   → 5581999999999
5581999999999 → 5581999999999
8199999999    → 55819999999999
```

## 🐛 Troubleshooting

### Mensagem não enviada

1. Verificar se WhatsApp está conectado na Z-API
2. Verificar credenciais
3. Verificar saldo da conta Z-API
4. Ver detalhes no histórico

### Cron não executando

1. Verificar se cron está ativo: `systemctl status cron`
2. Verificar logs: `tail -f /var/log/cobranca.log`
3. Testar manualmente: `php yii cobranca/processar`

### Template não aparece

1. Verificar se está ativo
2. Verificar se pertence ao usuário correto
3. Verificar banco de dados

## 📚 Referências

- [Z-API Documentação](https://developer.z-api.io/)
- [Yii2 Console Commands](https://www.yiiframework.com/doc/guide/2.0/en/tutorial-console)
- [Cron Job Tutorial](https://crontab.guru/)

## 🎉 Recursos Implementados

- [x] Módulo completo
- [x] 3 tabelas no banco
- [x] 3 models com validação
- [x] WhatsAppService (Z-API)
- [x] CobrancaProcessor
- [x] Console command
- [x] Interface de configuração
- [x] Interface de templates
- [x] Interface de histórico
- [x] Preview em tempo real
- [x] Filtros e estatísticas
- [x] Reenvio de cobranças
- [x] Templates padrão

## 📞 Suporte

Para dúvidas ou problemas, consulte a documentação completa em:

- `/home/barbosa/.gemini/antigravity/brain/.../task_008_walkthrough.md`
