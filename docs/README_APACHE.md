# Guia Rápido - Configuração Apache Arch Linux

## Problema: Erro 404 em Produção

Se você está tendo erro 404 em produção mas funciona localmente, siga estes passos:

## Solução Rápida

### Opção 1: Script Automático (Recomendado)

```bash
cd /srv/http/pulse
sudo ./docs/setup-arch-linux.sh localhost    # Para desenvolvimento
sudo ./docs/setup-arch-linux.sh producao    # Para produção
```

### Opção 2: Configuração Manual

#### 1. Instalar Apache (se necessário)
```bash
sudo pacman -S apache
```

#### 2. Habilitar Módulos

Edite `/etc/httpd/conf/httpd.conf` e descomente:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
Include conf/extra/httpd-vhosts.conf
```

#### 3. Configurar VirtualHost

**Para Localhost:**
```bash
sudo cp docs/apache-localhost.conf /etc/httpd/conf/extra/httpd-vhosts.conf
echo "127.0.0.1 pulse.localhost" | sudo tee -a /etc/hosts
```

**Para Produção:**
```bash
sudo cp docs/apache-producao.conf /etc/httpd/conf/extra/httpd-vhosts.conf
sudo nano /etc/httpd/conf/extra/httpd-vhosts.conf
# Ajuste DocumentRoot e ServerName
```

#### 4. Testar e Reiniciar
```bash
sudo apachectl configtest
sudo systemctl restart httpd
```

## Verificar Configuração

```bash
./docs/verificar-apache.sh
```

## Pontos Críticos

1. **AllowOverride All** - Deve estar configurado no VirtualHost
2. **mod_rewrite** - Deve estar habilitado
3. **DocumentRoot** - Deve apontar para `/caminho/ate/pulse/web`
4. **.htaccess** - Deve existir em `web/.htaccess`

## Documentação Completa

Veja `docs/CONFIGURACAO_APACHE.md` para documentação detalhada.

