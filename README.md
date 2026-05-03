# Medico

Landing page para clínica médica com foco em apresentação de serviços, agendamento de consultas e contato por WhatsApp/email.

## Requisitos

- PHP 8.4+
- Composer
- Node.js somente para testes E2E com Playwright

## Configuração

Copie `.env.example` para `.env` e ajuste:

- `APP_NAME`: nome público da clínica.
- `APP_PAGE_TITLE`: título da página.
- `APP_BASE`: subcaminho de publicação, por exemplo `/medico`.
- `APP_PALETTE`: paleta padrão da landing (`blue`, `red`, `emerald`, `amber` ou `violet`).
- `APP_SHOW_PALETTE_SELECTOR`: use `true` em catálogo/demo para mostrar o seletor de cores; mantenha `false` na landing final.
- `FACEBOOK_URL`: link oficial do Facebook.
- `WHATSAPP_URL`: link oficial de WhatsApp.
- `CONTACT_TO` e `CONTACT_FROM`: emails usados pelo formulário.
- Configurações `SMTP_*`, se `MAIL_DRIVER="smtp"`.
- `RECAPTCHA_ENABLED`: mantenha `false` em `srv798468.hstgr.cloud`; use `true` apenas no `.env` real de produção em `natalcode.com.br/medico`.
- `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`, `RECAPTCHA_ALLOWED_HOSTNAME`: configure somente no `.env` não versionado da produção.

O arquivo `.env` não é versionado. Não coloque chaves secretas de reCAPTCHA em `.env.example`, README ou arquivos de backup versionados.

## Execução Local

```bash
composer install
php -S 127.0.0.1:8000 -t public
```

Abra `http://127.0.0.1:8000/`.

## Testes

```bash
composer test
npx playwright test
```

Os scripts em `scripts/` também mantêm smoke tests de paleta, formulário e frontend para ambientes publicados.

## Conteúdo

O conteúdo principal está em:

- `views/pages/home.twig`
- `views/partials/navbar.twig`
- `views/partials/footer.twig`
- `public/assets/img/`

Após alterar templates em produção, limpe o cache Twig em `storage/cache/twig` ou rode o script de pós-update.
