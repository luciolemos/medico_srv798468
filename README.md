# Slim Site Template

Template base para aplicações web em PHP com arquitetura orientada a rotas e controllers, utilizando Slim 4 como microframework HTTP/PSR-7 e Twig como engine de templates para composição da camada de apresentação.

## Stack
- PHP 8.3+
- Slim 4 (`slim/slim`, `slim/psr7`)
- Twig (`slim/twig-view`, `twig/twig`)
- Apache 2.4 com `mod_rewrite`

## Estrutura
- `public/`: front controller (`index.php`), `.htaccess` e assets.
- `routes/web.php`: rotas Slim.
- `src/Controllers/HomeController.php`: controller HTTP (PSR-7).
- `src/Core/Env.php`: loader simples de `.env`.
- `views/`: templates Twig.
- `storage/`: cache/logs.

## Mapa da Home (conversao)
Arquivo principal: `views/pages/home.twig`

- `Hero`: proposta de valor, CTAs primarios e prova social inicial.
- `Stack strip`: reforco rapido de credibilidade tecnica.
- `Features`: beneficios em cards (copy muda por modo `soft/growth`).
- `Projects`: casos visuais para tangibilizar qualidade/entrega.
- `Depoimentos`: prova social principal (tambem alimenta o quick-proof da hero).
- `How`: pipeline tecnico resumido para reduzir friccao com lead tecnico.
- `Docs`: reforco de governanca e maturidade de engenharia.
- `Labs links`: atalhos operacionais do ecossistema Labs.
- `CTA final`: reforco de decisao antes do formulario.
- `Formulario #form-orcamento`: captura de lead com wizard de 2 etapas.
- `FAQ`: quebra de objecoes tecnico-comerciais.
- `Footer + palette FAB + WhatsApp float`: navegacao de suporte e contato rapido.

## Interacoes ativas no front
Arquivo principal: `public/assets/js/landing.js`

- `AOS progressivo` com ajustes por secao e por perfil de dispositivo.
- `Theme toggle` (dark/light) com persistencia em `localStorage`.
- `Palette switcher` por query string + persistencia local.
- `Quick-proof randomizer` no card de depoimento da hero (a cada load).
- `Lead form wizard` (2 etapas) com validacao progressiva.
- `Tracking de CTA/form` via `dataLayer`/`gtag`/`fbq` quando disponiveis.
- `Tablet carousel` para `projects` e `depoimentos`.
- `Navegacao one-page` (active link + smooth scroll + fechamento do menu mobile).

## Execucao local
```bash
composer install
```

Abra:
- `http://88.198.104.148/<slug>/`

## Como funciona
1. Apache aponta para `/var/www/<slug>/public`.
2. `public/index.php` sobe Slim, Twig e middlewares.
3. `routes/web.php` registra endpoints.
4. Controller renderiza Twig.

## Alias Apache (Labs)
Formato correto:

```apache
Alias /<slug> /var/www/<slug>/public
<Directory /var/www/<slug>/public>
  Options FollowSymLinks
  AllowOverride All
  Require all granted
</Directory>
```

## Variaveis de ambiente
- `APP_NAME`
- `APP_BASE` (ex.: `/site2`)
- `APP_ENV` (`production` ou `dev`)
- `APP_PALETTE` (`blue`, `red`, `emerald`, `amber`, `violet`)
- Links sociais e contato (`GITHUB_URL`, `X_URL`, `INSTAGRAM_URL`, `WHATSAPP_URL`)

### Formulario de contato (backend + tracking)
- Rota: `POST /contato`
- Secao front: `#form-orcamento` em `views/pages/home.twig`
- Controller: `src/Controllers/HomeController.php` (`contact()`)
- SMTP via PHPMailer (com fallback para `mail()`).

Variaveis:
- `CONTACT_TO`
- `CONTACT_FROM`
- `MAIL_DRIVER` (`smtp` ou `mail`)
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`
- `SMTP_ENCRYPTION` (`tls` ou `ssl`)
- `SMTP_AUTH` (`true`/`false`)
- `SMTP_TIMEOUT`

Logs gerados:
- `storage/logs/lead-events.log` (sucesso/falha do envio)
- `storage/logs/contatos-fallback.log` (quando houver falha de envio/configuracao)

Fluxo resumido:
1. Front envia `POST /contato`.
2. Controller valida campos e tenta envio por SMTP (ou `mail()` fallback).
3. Resultado retorna como flash (`form_status`) para a home.
4. Front le `form_status` e dispara evento de analytics de sucesso/falha.

## Templates unificados (Labs)
O layout/comportamento agora usa uma base unica (`natalcode`) e a diferenca entre templates fica na paleta.

- CSS base: `public/assets/css/landing.css`
- Paletas: `public/assets/css/palettes/*.css`
- Seletor front-only (navbar): troca paleta em runtime via JS, sem rebuild
- Guia operacional completo: `docs/centralizacao.md`

Para trocar o visual sem alterar estrutura:
1. Defina `APP_PALETTE` no `.env`.
2. Use um valor permitido: `blue`, `red`, `emerald`, `amber`, `violet`.

Preview rapido por URL (sem mudar `.env`):
- `/?palette=red`

## Como a paleta e resolvida
Ordem de prioridade atual:
1. Query string `?palette=<cor>` (se valida)
2. `localStorage.palette` (se valida)
3. Valor default do backend (`APP_PALETTE` / `palette` no controller)

Paletas invalidas fazem fallback para `blue`.

## Arquitetura centralizada (na pratica)
No `natalcode`, a estrutura e comportamento vivem em arquivos base:
- `views/pages/home.twig` (layout e secoes)
- `views/partials/navbar.twig` e `views/partials/footer.twig`
- `public/assets/css/landing.css` (UI/motion core)
- `public/assets/js/landing.js` (interacoes core)

As variacoes de cor ficam isoladas em:
- `public/assets/css/palettes/blue.css`
- `public/assets/css/palettes/red.css`
- `public/assets/css/palettes/emerald.css`
- `public/assets/css/palettes/amber.css`
- `public/assets/css/palettes/violet.css`

O carregamento da paleta acontece em:
- `views/base.twig` (`<link ... /palettes/{{ palette }}.css>`)

## Importante: heranca entre sites
Sites provisionados (`/var/www/site1`, `/var/www/yellowsite`, etc.) nao herdam automaticamente mudancas do `natalcode` se tiverem arquivos proprios.

Para um site refletir o `natalcode`, e preciso:
1. sincronizar arquivos base (views/css/js/controller/index), e
2. manter apenas a paleta diferente via `APP_PALETTE`.

## Script de paleta em lote
Arquivo:
- `scripts/set-palettes.sh`

Exemplos:
```bash
# Simular
scripts/set-palettes.sh --dry-run --base-dir /var/www --from-file palettes.map

# Aplicar
scripts/set-palettes.sh --base-dir /var/www --from-file palettes.map
```

Formato de `palettes.map`:
```txt
slug=palette
site1=red
site2=blue
yellowsite=amber
```

Paletas permitidas:
- `blue`, `red`, `emerald`, `amber`, `violet`

## APP_BASE e rotas
`APP_BASE` deve bater com o alias Apache do site:
- Se o site abre em `http://host/natalcode`, use `APP_BASE="/natalcode"`.
- Se abre no root `http://host/`, use `APP_BASE=""`.

Se estiver errado, podem ocorrer 404 em rotas ou assets.

## Deploy
1. Garantir permissao de escrita em `storage/`.
2. Rodar `composer install --no-dev --optimize-autoloader`.
3. Validar Apache:
```bash
sudo apache2ctl -t
sudo systemctl reload apache2
```

## Conversao de Imagens para WebP
Script utilitario para converter imagens com GD:
- `scripts/convert-webp.php`

Exemplos:
```bash
php scripts/convert-webp.php
php scripts/convert-webp.php --path=public/assets/img --quality=82
php scripts/convert-webp.php --path=public/assets/img --dry-run
php scripts/convert-webp.php --path=public/assets/img --force
```

Exemplo de conversao de arquivo unico:
```bash
php -r '$src="public/assets/img/avatars/face1_620_620.png"; $dst="public/assets/img/avatars/face1_620_620.webp"; $im=imagecreatefrompng($src); imagepalettetotruecolor($im); imagealphablending($im,true); imagesavealpha($im,true); imagewebp($im,$dst,82); imagedestroy($im);'
```
