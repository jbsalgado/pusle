# Sincronização Apache Local com Remoto

## Resumo Rápido

Para deixar seu Apache local igual ao remoto (sem mexer no remoto), execute:

```bash
cd /srv/http/pulse
sudo ./docs/sincronizar-apache-local.sh
```

## O que isso faz?

1. ✅ Sincroniza `httpd.conf` local com o remoto
2. ✅ Mantém VirtualHost específico do Pulse com `AllowOverride All`
3. ✅ Cria backup automático antes de alterar

## Resultado

- **DocumentRoot principal**: `AllowOverride None` (igual ao remoto)
- **VirtualHost do Pulse**: `AllowOverride All` (permite .htaccess funcionar)
- **Outros projetos**: Não são afetados

## Documentação Completa

Veja `docs/SINCRONIZAR_APACHE.md` para detalhes completos.

