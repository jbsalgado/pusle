# Log de Tarefas - Implementações 12/04/2026

### 1. Sistema de Preços e Busca
- [x] Implementação de busca com `unaccent` no PostgreSQL.
- [x] Criação de 10 colunas para escala de preços em `prest_produtos`.
- [x] Interface de cadastro de escala no ERP (`_form.php`).
- [x] Lógica de detecção de escala no `cart.js`.
- [x] Bloqueio de edição de preço unitário no PWA (Segurança).

### 2. Integração WhatsApp
- [x] Pesquisa da geração de imagem via `html2canvas` no `pix.js`.
- [x] Modificação do `index.html` para incluir `prompt` de telefone.
- [x] Implementação de sanitização de número e prefixo `55`.
- [x] Ajuste de redirecionamento `wa.me` com mensagem pré-definida.
- [x] Documentação das melhorias em arquivos locais (`.md`).

---
### 3. Aplicativo Mobile (Flutter)
- [x] Criação do plano de implementação mobile.
- [x] Inicialização do projeto Flutter em `pulse_app`.
- [x] Configuração de dependências e definições do AndroidManifest.
- [/] Implementação do Shell Android com Bridge JS (Scanner e Impressão integrados).
- [ ] Implementação de notificações persistentes (sem dependências externas).

### 4. Toques Finais & Pendências
- [x] Definição do ícone e Splash Screen (Identidade Visual).
- [x] Ajuste do nome comercial do app (Display Name: Tausz-Pulse).
- [x] Configuração da URL de consulta para notificações sem Firebase.
- [x] Implementação de notificações persistentes (via API interna PHP).
- [x] Backend: NotificacaoController.php e Rota API
- [x] Backend: Tabela sys_notificacoes_app (SQL)
- [x] **Correção Compartilhamento WhatsApp**
    - [x] Flutter: Adicionar `share_plus` e `path_provider`
    - [x] Flutter: Implementar `PulseBridge.shareImage` (Base64)
    - [x] Web: Adicionar botão WhatsApp e ponte no `web/venda-direta/js/pix.js`
- [x] **NOVO: Build Final**
    - [x] Código preparado para build (Flutter + Web)
    - [x] Correção de NDK version (28.2.13676358) conforme exigido pelo plugin `jni`.
    - [x] Atualização da API do `flutter_foreground_task` para versão 6.5.0.
    - [x] Correção de parâmetros inválidos no `Scaffold`.
    - [x] **NOVO:** Forçado JVM Target 11 globalmente para resolver inconsistência entre Java e Kotlin.
    - [/] Executando `flutter build apk --release` para verificação final.

---
*Gerado automaticamente pela Antigravity AI.*
