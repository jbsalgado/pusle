# Diagnóstico: Carteira não sendo exibida no Prestanista

## Problema
A nova carteira e período cadastrados não estão sendo exibidos no módulo Prestanista.

## Checklist de Verificação

### 1. Verificar Status do Período
O período DEVE estar com status `EM_COBRANCA` ou `ABERTO`:
- Acesse: `http://localhost/pulse/basic/web/index.php/vendas/periodo-cobranca/index`
- Verifique se o período criado tem status `EM_COBRANCA` ou `ABERTO`
- Se estiver `FECHADO`, altere para `EM_COBRANCA` ou `ABERTO`

### 2. Verificar Vinculação da Carteira ao Período
A carteira DEVE estar vinculada ao período correto:
- Acesse: `http://localhost/pulse/basic/web/index.php/vendas/carteira-cobranca/index`
- Verifique se o campo `periodo_id` da carteira corresponde ao ID do período ativo
- Se não corresponder, edite a carteira e selecione o período correto

### 3. Verificar se Carteira está Ativa
A carteira DEVE estar com `ativo = true`:
- Na listagem de carteiras, verifique se a carteira está marcada como ativa
- Se não estiver, edite e marque como ativa

### 4. Verificar Cobrador
A carteira DEVE estar vinculada ao cobrador correto:
- Verifique se o `cobrador_id` da carteira corresponde ao ID do colaborador que está fazendo login no Prestanista
- O CPF usado no login do Prestanista deve corresponder ao CPF do colaborador vinculado à carteira

### 5. Limpar Cache do Prestanista
O cache local pode estar mantendo dados antigos:
- No Prestanista, clique em "Sair" para limpar o cache
- Ou abra o Console do navegador (F12) e execute:
  ```javascript
  // Limpar IndexedDB
  indexedDB.deleteDatabase('prestanista_db');
  // Recarregar página
  location.reload();
  ```

### 6. Verificar Logs do Servidor
Após clicar em "Sincronizar" no Prestanista, verifique:
- Console do navegador (F12) para ver mensagens de erro
- Logs do servidor PHP para ver se há erros na API

### 7. Testar Endpoint da API Diretamente
Teste o endpoint diretamente no navegador:
```
http://localhost/pulse/basic/web/index.php/api/rota-cobranca/dia?cobrador_id=SEU_COBRADOR_ID&usuario_id=SEU_USUARIO_ID
```

Substitua `SEU_COBRADOR_ID` e `SEU_USUARIO_ID` pelos valores corretos.

## Possíveis Causas

1. **Período com status FECHADO**: O sistema só retorna períodos com status `EM_COBRANCA` ou `ABERTO`
2. **Carteira vinculada ao período errado**: A carteira está vinculada a um período fechado ou diferente
3. **Carteira inativa**: A carteira está com `ativo = false`
4. **Cobrador incorreto**: A carteira está vinculada a outro cobrador
5. **Cache local desatualizado**: O navegador está usando dados antigos do IndexedDB

## Solução Rápida

1. Verifique o status do período e altere para `EM_COBRANCA` se necessário
2. Verifique se a carteira está vinculada ao período correto
3. No Prestanista, clique em "Sair" e faça login novamente
4. Clique em "Sincronizar" para baixar os dados atualizados

## Debug no Console

Abra o Console do navegador (F12) e execute:
```javascript
// Ver período ativo
fetch('/pulse/basic/web/index.php/api/rota-cobranca/dia?cobrador_id=SEU_COBRADOR_ID&usuario_id=SEU_USUARIO_ID')
  .then(r => r.json())
  .then(d => console.log('Resposta API:', d));
```

Isso mostrará a resposta completa da API, incluindo informações de debug se houver erro.

