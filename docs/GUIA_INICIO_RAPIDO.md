# 🚀 Guia de Início Rápido - Contas a Pagar

## ⚡ 5 Minutos para Começar

### 1️⃣ Acessar o Módulo

```
URL: /contas-pagar/conta-pagar/index
```

### 2️⃣ Criar Primeira Conta

1. Clique em **"Nova Conta"** (verde)
2. Preencha:
   - **Descrição:** Aluguel Janeiro/2026
   - **Valor:** 1500.00
   - **Vencimento:** 10/03/2026
   - **Fornecedor:** Selecione da lista
   - **Categoria:** ALUGUEL
3. **Salvar**

### 3️⃣ Pagar a Conta

1. Clique em **"Pagar"** (verde)
2. Selecione **forma de pagamento**
3. **Confirmar**

### 4️⃣ Ver Relatórios

```
URL: /contas-pagar/relatorio/index
```

### 5️⃣ Dashboard Financeiro

```
URL: /vendas/dashboard-financeiro/index
```

---

## 📋 Checklist de Configuração

### Obrigatório

- [x] Migrations executadas
- [ ] Fornecedores cadastrados
- [ ] Categorias configuradas

### Opcional

- [ ] E-mail configurado (notificações)
- [ ] Cron configurado (notificações automáticas)
- [ ] Permissões de upload ajustadas

---

## 🎯 Principais Funcionalidades

| Funcionalidade    | URL                                  | Descrição               |
| ----------------- | ------------------------------------ | ----------------------- |
| **Listar Contas** | `/contas-pagar/conta-pagar/index`    | Ver todas as contas     |
| **Relatórios**    | `/contas-pagar/relatorio/index`      | Dashboard de relatórios |
| **Dashboard**     | `/vendas/dashboard-financeiro/index` | Visão consolidada       |
| **Nova Conta**    | Botão "Nova Conta"                   | Criar conta manual      |

---

## 💡 Dicas Rápidas

### Atalhos

- **Verde** = Ações positivas (criar, pagar)
- **Vermelho** = Ações negativas (cancelar, estornar)
- **Roxo** = Relatórios
- **Azul** = Visualizar

### Filtros Úteis

- Status: PENDENTE, PAGA, CANCELADA
- Período: Últimos 30/60/90 dias
- Fornecedor: Filtrar por fornecedor específico

### Exportações

- **PDF**: Relatórios formatados
- **CSV**: Análise no Excel

---

## ⚠️ Avisos Importantes

1. **Caixa Aberto**: Necessário para pagar contas
2. **Saldo Suficiente**: Validado antes do pagamento
3. **Estorno**: Apenas contas PAGAS podem ser estornadas
4. **Comprovantes**: Upload opcional mas recomendado

---

## 🆘 Problemas Comuns

**Não consigo pagar conta**
→ Verifique se há caixa aberto e saldo suficiente

**E-mails não chegam**
→ Configure o mailer em `config/web.php`

**Relatório vazio**
→ Verifique se há contas cadastradas para o período

---

## 📞 Comandos Úteis

```bash
# Testar notificação
php yii notificacao-contas/testar <ID>

# Enviar notificações manualmente
php yii notificacao-contas/enviar

# Ver ajuda do comando
php yii help notificacao-contas
```

---

## ✅ Pronto!

Você está pronto para usar o módulo de Contas a Pagar!

**Dúvidas?** Consulte `/docs/PROJETO_CONCLUIDO.md`
