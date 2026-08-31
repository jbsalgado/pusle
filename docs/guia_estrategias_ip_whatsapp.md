# Guia de Arquitetura: Roteamento de IP para Disparos WhatsApp (VPS EUA &rarr; Brasil)

Este documento detalha o passo a passo técnico completo para executar disparos do WhatsApp a partir de uma VPS localizada nos Estados Unidos para destinatários no Brasil, contornando restrições de ASN/Geolocalização **sem custos com proxies comerciais**.

---

## Sumário das 3 Opções

1. [Opção 1: API Oficial da Meta (Cloud API)](#opção-1-api-oficial-da-meta-whatsapp-cloud-api---solução-nativa)
2. [Opção 2: Túnel Seguro P2P com Tailscale Exit Node (IP Residencial BR)](#opção-2-túnel-tailscale-com-exit-node-no-brasil-100-gratuito)
3. [Opção 3: Micro-Gateway Evolution em São Paulo (Oracle Cloud Always Free)](#opção-3-micro-gateway-evolution-go-em-são-paulo-oracle-always-free)

---

## Opção 1: API Oficial da Meta (WhatsApp Cloud API) - Solução Nativa

### Por que o IP não importa aqui?
Na API Oficial da Meta, as requisições HTTP do Pulse vão para `https://graph.facebook.com`. A Meta **não analisa nem penaliza o IP de origem da VPS**, pois a autenticação é chancelada diretamente via *System User Token* e *WABA ID*.

### Passo a Passo:
1. **Criar App no Meta for Developers:**
   * Acesse [developers.facebook.com](https://developers.facebook.com) &rarr; *Meus Apps* &rarr; *Criar App* &rarr; Tipo: **Empresa (Business)**.
   * Adicione o produto **WhatsApp**.
2. **Obter as Credenciais:**
   * No menu lateral, acerte em *WhatsApp &rarr; Primeiros Passos*.
   * Copie o **Phone Number ID** (ex: `109283746592817`) e o **WABA ID** (ex: `104829384918234`).
3. **Gerar Token de Sistema Permanente:**
   * Acesse o *Meta Business Suite* &rarr; *Configurações da Empresa* &rarr; *Usuários do Sistema*.
   * Crie um usuário com função **Administrador** e gere um token permanente com as permissões:
     * `whatsapp_business_messaging`
     * `whatsapp_business_management`
4. **Salvar no ERP Pulse:**
   * Acesse `https://catalogos.oncode.app.br/evolution/config`.
   * Clique na aba **WhatsApp Oficial (Meta Cloud API)** e cole os dados salvando as credenciais.
5. **Configurar o Webhook de Retorno:**
   * **Callback URL:** `https://catalogos.oncode.app.br/evolution/meta-webhook/index`
   * **Verify Token:** `pulse_meta_webhook_token_2026`
   * **Campos Assinados:** `messages`, `message_template_status_update`.

---

## Opção 2: Túnel Tailscale com Exit Node no Brasil (100% Gratuito)

### Como Funciona?
O Tailscale estabelece um túnel WireGuard criptografado entre a VPS nos EUA e um computador qualquer seu no Brasil (computador do escritório, notebook de casa ou Raspberry Pi). A VPS escoa o tráfego de rede através da sua conexão residencial no Brasil. O WhatsApp enxerga a conexão saindo diretamente da sua operadora (Vivo, Claro, TIM), com reputação residencial legítima.

### Passo a Passo:

#### 1. Na Máquina que Fica no Brasil (Exit Node):
* **No Linux / Raspberry Pi:**
  ```bash
  # Habilitar o encaminhamento de pacotes IPv4
  echo 'net.ipv4.ip_forward = 1' | sudo tee -a /etc/sysctl.d/99-tailscale.conf
  sudo sysctl -p /etc/sysctl.d/99-tailscale.conf

  # Iniciar o Tailscale anunciando como nó de saída
  sudo tailscale up --advertise-exit-node
  ```
* **No Windows / Mac:**
  * Baixe o app do Tailscale, faça login com sua conta, clique com o botão direito no ícone do Tailscale e selecione **"Run as exit node"**.

#### 2. No Painel Web do Tailscale:
* Acesse [login.tailscale.com/admin/machines](https://login.tailscale.com/admin/machines).
* Localize a máquina do Brasil &rarr; clique no menu dos três pontinhos `...` &rarr; **Edit route settings**.
* Marque a opção **Use as exit node** e salve.

#### 3. Na VPS dos Estados Unidos (2.25.182.204):
```bash
# 1. Instalar o Tailscale
curl -fsSL https://tailscale.com/install.sh | sh

# 2. Conectar a VPS usando a máquina do Brasil como saída de tráfego
sudo tailscale up --exit-node=<NOME_OU_IP_DA_MAQUINA_DO_BRASIL> --exit-node-allow-lan-access=true

# 3. Testar se o IP público mudou para o Brasil:
curl ifconfig.me
```

---

## Opção 3: Micro-Gateway Evolution Go em São Paulo (Oracle Always Free)

### Como Funciona?
O ERP Pulse, banco de dados e arquivos permanecem na sua VPS atual nos Estados Unidos. Apenas o motor **Evolution Go** roda em uma micro-máquina gratuita Always Free da Oracle Cloud na região de São Paulo (Vinhedo/GRU).

### Passo a Passo:

#### 1. Criar Instância na Oracle Cloud:
* Acesse [cloud.oracle.com](https://cloud.oracle.com).
* Ao criar a conta, selecione a Home Region como **Brazil East (São Paulo)**.
* Crie uma VM *Always Free* (Ubuntu 22.04 ou 24.04).

#### 2. Instalar o Motor Evolution Go na VM de São Paulo:
```bash
# No terminal da VM em São Paulo
git clone https://github.com/EvolutionAPI/evolution-go.git
cd evolution-go
cp env.example .env

# Configure a AUTHENTICATION_API_KEY no arquivo .env
nano .env

# Inicie o container
docker compose up -d
```

#### 3. Apontar o Pulse (VPS EUA) para a VM Brasileira:
* No arquivo `config/params.php` do Pulse na VPS dos EUA, aponte a URL base da Evolution para o IP da máquina de São Paulo:
```php
'evolution' => [
    'base_url'       => 'http://IP_DA_VM_SAO_PAULO:8080',
    'global_api_key' => 'SUA_CHAVE_GLOBAL',
],
```

---

## Matriz Comparativa

| Critério | Opção 1: Meta Cloud API | Opção 2: Tailscale Exit Node | Opção 3: Gateway Oracle SP |
| :--- | :---: | :---: | :---: |
| **Custo Mensal** | 1.000 msgs grátis/mês | **R$ 0,00** | **R$ 0,00** (Always Free) |
| **Risco de Ban por IP** | **Zero** | **Mínimo** (IP Residencial BR) | **Muito Baixo** (IP Datacenter BR) |
| **Tempo de Execução** | 5 minutos (Pronto no painel) | 15 minutos | 30 minutos |
| **Autonomia de Hardware** | 100% em Nuvem | Depende do PC local ligado | 100% em Nuvem |
