# Plano de Ajuste de Timezone (Recife/Brasil)

Este documento descreve o plano para configurar o sistema PULSE para utilizar o fuso horário de Recife (`America/Recife`, UTC-3). Atualmente, o sistema utiliza o padrão UTC ou configurações parciais de `America/Sao_Paulo`.

## Descrição do Problema

O módulo de caixa e outras áreas sensíveis ao tempo (registros de vendas, logs, movimentações financeiras) precisam registrar e exibir horas precisas de acordo com a localidade de Recife. O uso de `America/Sao_Paulo` é similar, mas `America/Recife` é mais específico e seguro contra mudanças em regras de horário de verão locais (embora inexistentes no momento).

## Alterações Propostas

### 1. Configuração do Framework (Yii2)

#### [MODIFY] [config/web.php](file:///srv/http/pulse/config/web.php)

- **Timezone da Aplicação**: Definir `timeZone` como `America/Recife`. Isso afeta todas as funções nativas de data do PHP (`date`, `DateTime`) chamadas pelo Yii.
- **Formatter**: Atualizar `defaultTimeZone` no componente `formatter` para `America/Recife` para garantir que a exibição em views esteja correta.

```php
// No array de configuração principal:
'timeZone' => 'America/Recife',

// No componente formatter:
'formatter' => [
    'class' => 'yii\i18n\Formatter',
    'locale' => 'pt-BR',
    'defaultTimeZone' => 'America/Recife',
    // ...
],
```

### 2. Configuração do Banco de Dados (PostgreSQL)

#### [MODIFY] [config/db.php](file:///srv/http/pulse/config/db.php)

- **Sessão do Banco**: Configurar o fuso horário da sessão do PostgreSQL logo após a abertura da conexão. Isso garante que funções SQL como `NOW()` ou `CURRENT_TIMESTAMP` retornem o horário de Recife.

```php
return [
    'class' => 'yii\db\Connection',
    // ...
    'on afterOpen' => function($event) {
        $event->sender->createCommand("SET TIME ZONE 'America/Recife'")->execute();
    },
];
```

---

## Plano de Verificação

### Testes Automatizados (Script de Validação)

Será criado um script temporário em `web/test_timezone.php` para validar se o PHP e o PostgreSQL estão retornando os mesmos valores e se correspondem ao fuso esperado.

### Verificação Manual

1. Acessar a página de listagem de caixas (`/caixa/caixa/index`).
2. Abrir um novo caixa e verificar se a "Hora de Abertura" exibida corresponde ao horário local de Recife.
3. Repetir o processo para o fechamento de caixa.

> [!IMPORTANT]
> A alteração no fuso horário afetará todos os registros futuros. Registros passados armazenados sem informação de timezone ("timestamp without time zone") podem parecer "deslocados" se forem interpretados com o novo timezone da aplicação.
