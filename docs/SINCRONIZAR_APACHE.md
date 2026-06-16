# Sincronizar Apache Local com Remoto

Este guia explica como deixar o Apache local idêntico ao remoto, mantendo um VirtualHost específico para o Pulse com `AllowOverride All`.

## Por que sincronizar?

- **Consistência**: Ambiente local igual ao de produção
- **Evitar problemas**: Problemas que aparecem só em produção são detectados localmente
- **Segurança**: Mesma configuração de segurança em ambos os ambientes

## O que o script faz

O script `sincronizar-apache-local.sh` ajusta o `httpd.conf` local para ficar igual ao remoto:

1. ✅ Muda MPM de `prefork` para `event` (igual ao remoto)
2. ✅ Adiciona `Listen 443` (igual ao remoto)
3. ✅ Habilita módulos adicionais (proxy, ssl, http2, etc.)
4. ✅ Ajusta `AllowOverride None` no DocumentRoot principal (igual ao remoto)
5. ✅ Ajusta `DirectoryIndex` para só `index.html` (igual ao remoto)
6. ✅ Habilita `Include conf/extra/httpd-vhosts.conf` (igual ao remoto)
7. ✅ Adiciona `IncludeOptional conf/conf.d/*.conf` (igual ao remoto)
8. ✅ Adiciona `Protocols h2 h2c http/1.1` (igual ao remoto)
9. ✅ Ajusta configuração PHP para usar `php-fpm.conf` (igual ao remoto)
10. ✅ Cria VirtualHost específico do Pulse com `AllowOverride All`

## Como usar

### Opção 1: Script Automático (Recomendado)

```bash
cd /srv/http/pulse
sudo ./docs/sincronizar-apache-local.sh
```

### Opção 2: Manual

Se preferir fazer manualmente, siga estes passos:

#### 1. Backup do httpd.conf
```bash
sudo cp /etc/httpd/conf/httpd.conf /etc/httpd/conf/httpd.conf.backup
```

#### 2. Ajustar MPM
Edite `/etc/httpd/conf/httpd.conf`:
```apache
# Descomentar:
LoadModule mpm_event_module modules/mod_mpm_event.so

# Comentar:
#LoadModule mpm_prefork_module modules/mod_mpm_prefork.so
```

#### 3. Adicionar Listen 443
```apache
Listen 80
Listen 443
```

#### 4. Habilitar módulos adicionais
```apache
LoadModule socache_shmcb_module modules/mod_socache_shmcb.so
LoadModule proxy_module modules/mod_proxy.so
LoadModule proxy_fcgi_module modules/mod_proxy_fcgi.so
LoadModule ssl_module modules/mod_ssl.so
LoadModule http2_module modules/mod_http2.so
LoadModule logio_module modules/mod_logio.so
```

#### 5. Ajustar AllowOverride no DocumentRoot
```apache
<Directory "/srv/http">
    Options Indexes FollowSymLinks
    AllowOverride None  # ← Mudar de All para None
    Require all granted
</Directory>
```

#### 6. Ajustar DirectoryIndex
```apache
DirectoryIndex index.html  # Remover index.php se existir
```

#### 7. Habilitar Includes
```apache
Include conf/extra/httpd-vhosts.conf
IncludeOptional conf/conf.d/*.conf
```

#### 8. Adicionar Protocols
```apache
Protocols h2 h2c http/1.1
```

#### 9. Ajustar PHP
Remover configuração antiga:
```apache
# Remover estas linhas se existirem:
# LoadModule php_module modules/libphp.so
# AddHandler php-script .php
# Include conf/extra/php_module.conf
```

Adicionar:
```apache
Include conf/extra/php-fpm.conf
```

#### 10. Criar VirtualHost do Pulse
Criar/editar `/etc/httpd/conf/extra/httpd-vhosts.conf`:
```apache
<VirtualHost *:80>
    ServerName pulse.localhost
    ServerAlias localhost
    
    DocumentRoot "/srv/http/pulse/web"
    
    <Directory "/srv/http/pulse/web">
        Options Indexes FollowSymLinks
        AllowOverride All  # ← Permitir .htaccess apenas para o Pulse
        Require all granted
        RewriteEngine on
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . index.php [L]
    </Directory>
    
    ErrorLog /var/log/httpd/pulse-localhost-error.log
    CustomLog /var/log/httpd/pulse-localhost-access.log combined
    LogLevel warn
</VirtualHost>
```

#### 11. Adicionar ao /etc/hosts
```bash
echo "127.0.0.1 pulse.localhost" | sudo tee -a /etc/hosts
```

#### 12. Testar e Reiniciar
```bash
sudo apachectl configtest
sudo systemctl restart httpd
```

## Diferenças entre Local e Remoto

Após a sincronização, a única diferença será:

- **Remoto**: `AllowOverride None` no DocumentRoot principal (padrão)
- **Local**: `AllowOverride None` no DocumentRoot principal + VirtualHost do Pulse com `AllowOverride All`

Isso permite que:
- O ambiente local funcione igual ao remoto
- O Pulse tenha `AllowOverride All` apenas no seu VirtualHost
- Outros projetos no servidor não sejam afetados

## Verificar Sincronização

Após executar o script, verifique:

```bash
# Verificar sintaxe
sudo apachectl configtest

# Verificar configuração do Pulse
sudo httpd -S | grep pulse

# Testar acesso
curl -I http://pulse.localhost/vendas/inicio
```

## Restaurar Backup

Se algo der errado, restaure o backup:

```bash
sudo cp /etc/httpd/conf/httpd.conf.backup-YYYYMMDD /etc/httpd/conf/httpd.conf
sudo systemctl restart httpd
```

## Troubleshooting

### Erro ao reiniciar Apache

1. Verifique a sintaxe:
   ```bash
   sudo apachectl configtest
   ```

2. Verifique os logs:
   ```bash
   sudo tail -f /var/log/httpd/error_log
   ```

3. Restaure o backup se necessário

### Pulse não funciona após sincronização

1. Verifique se o VirtualHost está correto:
   ```bash
   sudo cat /etc/httpd/conf/extra/httpd-vhosts.conf
   ```

2. Verifique se o Include está habilitado:
   ```bash
   grep "Include conf/extra/httpd-vhosts.conf" /etc/httpd/conf/httpd.conf
   ```

3. Verifique se mod_rewrite está habilitado:
   ```bash
   sudo httpd -M | grep rewrite
   ```

## Arquivos Modificados

- `/etc/httpd/conf/httpd.conf` - Configuração principal (sincronizada com remoto)
- `/etc/httpd/conf/extra/httpd-vhosts.conf` - VirtualHost do Pulse
- `/etc/hosts` - Adiciona pulse.localhost

## Backup

O script cria automaticamente um backup em:
- `/etc/httpd/conf/httpd.conf.backup-YYYYMMDD`

