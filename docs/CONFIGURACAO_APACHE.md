# Configuração do Apache para Pulse - Arch Linux

Este guia explica como configurar o Apache para que o projeto Pulse funcione corretamente tanto em desenvolvimento local quanto em produção no **Arch Linux**.

## Problema Comum

**Sintoma:** A URL `localhost/pulse/web/index.php/vendas/inicio` funciona localmente, mas em produção retorna erro 404.

**Causa:** O Apache em produção não está processando o arquivo `.htaccess` corretamente, ou os módulos necessários não estão habilitados.

## Solução

### 1. Instalar e Configurar Apache no Arch Linux

#### Instalar Apache:
```bash
sudo pacman -S apache
```

#### Habilitar Módulos Necessários:

Os seguintes módulos devem estar habilitados:

- `mod_rewrite` - Para reescrita de URLs
- `mod_headers` - Para passar cabeçalhos HTTP

Edite o arquivo `/etc/httpd/conf/httpd.conf` e certifique-se de que estas linhas estão descomentadas:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
```

Se as linhas estiverem comentadas (com `#` no início), remova o `#`.

#### Habilitar Inclusão de VirtualHosts:

No mesmo arquivo `/etc/httpd/conf/httpd.conf`, certifique-se de que esta linha está descomentada:

```apache
Include conf/extra/httpd-vhosts.conf
```

Ou, se preferir usar `conf.d`:

```apache
Include conf.d/*.conf
```

#### Reiniciar Apache:
```bash
sudo systemctl restart httpd
```

### 2. Configurar VirtualHost

#### Para Localhost (Desenvolvimento) - Arch Linux

1. **Copie o conteúdo de `docs/apache-localhost.conf`** para `/etc/httpd/conf/extra/httpd-vhosts.conf`
   ```bash
   sudo cp docs/apache-localhost.conf /etc/httpd/conf/extra/httpd-vhosts.conf
   ```
   
   Ou crie um novo arquivo em `/etc/httpd/conf.d/pulse-localhost.conf`:
   ```bash
   sudo nano /etc/httpd/conf.d/pulse-localhost.conf
   # Cole o conteúdo de docs/apache-localhost.conf
   ```

2. **Ajuste o caminho do `DocumentRoot`** se necessário (o padrão é `/srv/http/pulse/web`)

3. **Adicione ao `/etc/hosts`**:
   ```bash
   echo "127.0.0.1 pulse.localhost" | sudo tee -a /etc/hosts
   ```

4. **Teste a configuração**:
   ```bash
   sudo apachectl configtest
   ```

5. **Reinicie o Apache**:
   ```bash
   sudo systemctl restart httpd
   ```

#### Para Produção - Arch Linux

1. **Copie o conteúdo de `docs/apache-producao.conf`** para `/etc/httpd/conf/extra/httpd-vhosts.conf`
   ```bash
   sudo cp docs/apache-producao.conf /etc/httpd/conf/extra/httpd-vhosts.conf
   ```
   
   Ou crie um novo arquivo em `/etc/httpd/conf.d/pulse.conf`:
   ```bash
   sudo nano /etc/httpd/conf.d/pulse.conf
   # Cole o conteúdo de docs/apache-producao.conf
   ```

2. **AJUSTE O CAMINHO** do `DocumentRoot` para o caminho real no servidor
   - Edite o arquivo e altere a linha `DocumentRoot "/caminho/completo/ate/pulse/web"`
   - Exemplo: Se o projeto está em `/srv/http/pulse/web`, já está correto

3. **AJUSTE O ServerName** para seu domínio
   - Altere `ServerName seu-dominio.com.br` para seu domínio real

4. **Teste a configuração**:
   ```bash
   sudo apachectl configtest
   ```

5. **Reinicie o Apache**:
   ```bash
   sudo systemctl restart httpd
   ```

### 3. Configuração Crítica: AllowOverride

O mais importante é garantir que `AllowOverride All` esteja configurado no diretório do projeto. Isso permite que o arquivo `.htaccess` seja processado.

No VirtualHost, você deve ter:

```apache
<Directory "/caminho/ate/pulse/web">
    Options Indexes FollowSymLinks
    AllowOverride All    # ← ESTA LINHA É CRÍTICA
    Require all granted
    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php [L]
</Directory>
```

### 4. Verificar Configuração

Execute o script de verificação:

```bash
cd /srv/http/pulse
./docs/verificar-apache.sh
```

Ou verifique manualmente:

```bash
# Testar sintaxe
sudo apachectl configtest

# Verificar se módulos estão carregados
sudo httpd -M | grep rewrite
sudo httpd -M | grep headers

# Verificar se o Apache está rodando
sudo systemctl status httpd
```

### 5. Testar Aplicação

Após configurar, teste a aplicação:

```bash
# Teste local
curl -I http://localhost/pulse/web/vendas/inicio
# ou
curl -I http://pulse.localhost/vendas/inicio

# Teste produção (substitua pelo seu domínio)
curl -I http://seu-dominio.com.br/vendas/inicio
```

### 6. Verificar Logs em Caso de Erro

Se ainda houver problemas, verifique os logs:

```bash
# Arch Linux
sudo tail -f /var/log/httpd/error_log
sudo tail -f /var/log/httpd/pulse-error.log
sudo tail -f /var/log/httpd/pulse-localhost-error.log  # Para localhost
```

## Estrutura de URLs Esperada

Após a configuração correta, as URLs devem funcionar assim:

- ✅ `http://localhost/vendas/inicio` (sem `/pulse/web/index.php`)
- ✅ `http://pulse.localhost/vendas/inicio`
- ✅ `http://seu-dominio.com.br/vendas/inicio`

O Yii2 detecta automaticamente se deve usar `index.php` nas URLs baseado na URL atual. Se o `.htaccess` estiver funcionando, as URLs serão limpas (sem `index.php`).

## Troubleshooting

### Erro 404 Persiste

1. **Verifique se AllowOverride está como 'All'**
   ```bash
   grep -r "AllowOverride" /etc/httpd/conf.d/
   grep -r "AllowOverride" /etc/httpd/conf/extra/
   ```

2. **Verifique se mod_rewrite está habilitado**
   ```bash
   sudo httpd -M | grep rewrite
   sudo httpd -M | grep headers
   ```

3. **Verifique permissões do .htaccess**
   ```bash
   ls -la /caminho/ate/pulse/web/.htaccess
   # Deve ser legível pelo Apache
   ```

4. **Teste o .htaccess diretamente**
   ```bash
   # Adicione uma linha de teste no .htaccess e veja se é processada
   # Exemplo: adicione "ErrorDocument 404 /teste.html" e veja se funciona
   ```

### Erro 403 Forbidden

1. Verifique permissões do diretório:
   ```bash
   ls -la /caminho/ate/pulse/web
   # O Apache precisa ter permissão de leitura
   ```

2. Verifique a configuração `Require all granted` no VirtualHost

### URLs com index.php Funcionam, mas URLs Limpas Não

Isso indica que o `.htaccess` não está sendo processado. Verifique:
- `AllowOverride All` está configurado?
- `mod_rewrite` está habilitado?
- O arquivo `.htaccess` existe e é legível?

## Arquivos de Referência

- `docs/apache-localhost.conf` - Configuração para desenvolvimento
- `docs/apache-producao.conf` - Configuração para produção
- `docs/verificar-apache.sh` - Script de verificação
- `web/.htaccess` - Arquivo de reescrita do Yii2

## Suporte

Se o problema persistir após seguir este guia:
1. Execute o script de verificação: `./docs/verificar-apache.sh`
2. Verifique os logs do Apache
3. Verifique se o caminho do DocumentRoot está correto
4. Certifique-se de que reiniciou o Apache após as alterações

