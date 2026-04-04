# Slim Site Template

Template base para aplicacoes web em PHP com arquitetura orientada a rotas e controllers, utilizando Slim 4 como microframework HTTP/PSR-7 e Twig como engine de templates para composicao da camada de apresentacao.

## Stack
- PHP 8.3+
- Slim 4 (`slim/slim`, `slim/psr7`)
- Twig (`slim/twig-view`, `twig/twig`)
- PHPMailer (`phpmailer/phpmailer`)
- Apache 2.4 com `mod_rewrite`

## Estrutura
- `public/`: front controller (`index.php`), `.htaccess` e assets.
- `routes/web.php`: rotas Slim.
- `src/Controllers/HomeController.php`: controller HTTP (PSR-7).
- `src/Core/Env.php`: loader simples de `.env`.
- `views/`: templates Twig.
- `storage/`: cache e logs de runtime.
- `.env.example`: modelo de configuracao para novos servidores.

## Requisitos de servidor
- PHP 8.3 ou superior
- Apache 2.4 com `mod_rewrite`
- Composer 2
- Permissao de escrita em `storage/`

## Variaveis de ambiente
Use `.env.example` como base para criar o arquivo `.env` do servidor.

Principais variaveis:
- `APP_NAME`
- `APP_MARK`
- `APP_BADGE`
- `APP_PAGE_TITLE`
- `APP_BASE`
- `APP_ENV`
- `APP_PALETTE`
- `GITHUB_URL`
- `X_URL`
- `INSTAGRAM_URL`
- `WHATSAPP_URL`
- `CONTACT_TO`
- `CONTACT_FROM`
- `MAIL_DRIVER`
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USER`
- `SMTP_PASS`
- `SMTP_ENCRYPTION`
- `SMTP_AUTH`
- `SMTP_TIMEOUT`
- `RATE_LIMIT_MAX_ATTEMPTS`
- `RATE_LIMIT_WINDOW_SECONDS`

## Execucao local
```bash
composer install
cp .env.example .env
```

Para ambiente local, use:
- `APP_ENV="dev"`
- `APP_BASE=""` se o projeto abrir na raiz local

## Comportamento por ambiente
- `APP_ENV=dev`: Twig sem cache, `auto_reload` ativo e erros detalhados.
- `APP_ENV=production`: Twig com cache em `storage/cache/twig`, `auto_reload` desativado e middleware de erro em modo restrito.

## Formulario de contato
- Rota: `POST /contato`
- Controller: `src/Controllers/HomeController.php`
- Driver suportado: `smtp` ou `mail`
- Protecoes ativas: `CSRF`, honeypot simples e rate limit por IP
- Logs:
  - `storage/logs/lead-events.log`
  - `storage/logs/contatos-fallback.log`

Se `MAIL_DRIVER=smtp`, configure `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_ENCRYPTION`, `SMTP_AUTH` e `SMTP_TIMEOUT`.

Rate limit padrao:
- `RATE_LIMIT_MAX_ATTEMPTS=5`
- `RATE_LIMIT_WINDOW_SECONDS=600`

## Deploy principal: vhost compartilhado com alias `/natalcode`
Este e o fluxo recomendado para o servidor de destino atual, onde o host principal ja existe e o projeto sera publicado em `https://srv798468.hstgr.cloud/natalcode/`.

### 1. Publicar o codigo
```bash
git clone https://github.com/luciolemos/natalcode_cloud18344.git /var/www/natalcode
cd /var/www/natalcode
composer install --no-dev --optimize-autoloader
cp .env.example .env
```

### 2. Configurar o `.env`
Para deploy em subcaminho, use:
- `APP_ENV="production"`
- `APP_BASE="/natalcode"`

Preencha tambem:
- identidade visual (`APP_NAME`, `APP_MARK`, `APP_BADGE`, `APP_PAGE_TITLE`)
- links institucionais
- configuracao de envio de email

### 3. Ajustar permissoes
Exemplo com Apache rodando como `www-data`:

```bash
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

### 4. Ajustar o vhost existente
No servidor de destino, adicione ao `VirtualHost` os aliases abaixo em `:80` e `:443`:

```apache
Alias /natalcode/ /var/www/natalcode/public/
Alias /natalcode /var/www/natalcode/public/

<Directory /var/www/natalcode/public>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

No bloco HTTP (`*:80`), inclua `natalcode` no redirect para a barra final:

```apache
RewriteCond %{REQUEST_URI} ^/(dashboard|itapiru|mvc|natalcode)$
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI}/ [L,R=301]
```

No bloco HTTPS (`*:443`), inclua o redirect da URL sem barra:

```apache
RewriteRule ^/natalcode$ /natalcode/ [R=301,L]
```

Se o vhost usar segregacao de logs por app, inclua:

```apache
SetEnvIf Request_URI "^/natalcode(/|$)" app_natalcode
SetEnvIf Request_URI "^/(?!dashboard(/|$)|itapiru(/|$)|mvc(/|$)|natalcode(/|$)).*" app_root

CustomLog ${APACHE_LOG_DIR}/srv-hstgr-http-natalcode-access.log combined env=app_natalcode
CustomLog ${APACHE_LOG_DIR}/srv-hstgr-https-natalcode-access.log combined env=app_natalcode
```

### 5. Validar a configuracao Apache
```bash
sudo apache2ctl -t
sudo systemctl reload apache2
```

### 6. Validar apos o deploy
Checklist minimo:
- `https://srv798468.hstgr.cloud/natalcode/` abre corretamente
- assets carregam sem `404`
- `POST /contato` responde corretamente
- redirects preservam o prefixo `/natalcode`
- `storage/cache/twig` e `storage/logs/` recebem escrita

## Deploy alternativo: vhost dedicado
Se no futuro o projeto for publicado em um host raiz dedicado, entao:
- o codigo pode continuar em `/var/www/natalcode`
- `APP_BASE=""`
- o `DocumentRoot` deve apontar para `/var/www/natalcode/public`

Exemplo:

```apache
<VirtualHost *:80>
    ServerName exemplo.com
    ServerAlias www.exemplo.com

    DocumentRoot /var/www/natalcode/public

    <Directory /var/www/natalcode/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/natalcode_error.log
    CustomLog ${APACHE_LOG_DIR}/natalcode_access.log combined
</VirtualHost>
```

## APP_BASE e rotas
- Em `vhost` dedicado: `APP_BASE=""`
- Em subcaminho `/natalcode`: `APP_BASE="/natalcode"`

Se `APP_BASE` estiver incorreto, podem ocorrer `404` em rotas, redirects ou assets.

## Paletas
Paletas suportadas:
- `blue`
- `red`
- `emerald`
- `amber`
- `violet`

Prioridade de resolucao:
1. query string `?palette=<cor>`
2. `localStorage.palette`
3. `APP_PALETTE`

Paletas invalidas fazem fallback para `blue`.

## Seguranca de deploy
- Publique apenas o diretorio `public/` no Apache
- Nao versione o arquivo `.env`
- Use `APP_ENV="production"` em servidor publico
- Restrinja escrita apenas a `storage/`
- Configure SMTP com credenciais do servidor de destino
- Revise o rate limit do formulario conforme o perfil de trafego do site
- Prefira HTTPS no host final
- Rode `apache2ctl -t` antes de qualquer reload

## Operacao
Comandos uteis:

```bash
composer install --no-dev --optimize-autoloader
sudo apache2ctl -t
sudo systemctl reload apache2
```

## Referencias internas
- Guia operacional de centralizacao: `docs/centralizacao.md`
- Exemplo de vhost compartilhado: `docs/apache-vhost-srv798468-natalcode.conf`
- Exemplo de `.env` para subcaminho: `docs/.env.natalcode.example`
- Script de paleta em lote: `scripts/set-palettes.sh`
- Conversao de imagens para WebP: `scripts/convert-webp.php`
