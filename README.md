# Landing Médica

Protótipo de landing page para serviços de saúde, pronto para derivar projetos como `/medico`, `/pediatria`, `/veterinaria`, `/odontologia` e outros subdiretórios.

## Requisitos

- PHP 8.3 ou 8.4
- Composer
- Node.js somente para testes E2E com Playwright

## Configuração

Copie `.env.example` para `.env` e ajuste:

- `APP_NAME`: nome público da clínica.
- `APP_PAGE_TITLE`: título da página.
- `APP_SLUG`: identificador curto da landing, por exemplo `medico`, `pediatria` ou `odontologia`.
- `APP_REQUEST_PREFIX`: prefixo dos protocolos, por exemplo `MED`, `PED`, `ODO`.
- `APP_CONTENT_FILE`: arquivo de conteúdo em `config/content/` sem a extensão `.php`; use `landing` para uma landing por repositório.
- `APP_CANONICAL_URL`: URL pública canônica da landing, com domínio e subcaminho; quando vazio, o app deriva do host da requisição.
- `APP_BASE`: subcaminho de publicação, por exemplo `/medico`, `/pediatria` ou `/odontologia`.
- `APP_PALETTE`: paleta padrão da landing (`blue`, `red`, `emerald`, `amber` ou `violet`).
- `APP_SHOW_PALETTE_SELECTOR`: use `true` em catálogo/demo para mostrar o seletor de cores; mantenha `false` na landing final.
- `FACEBOOK_URL`: link oficial do Facebook.
- `WHATSAPP_URL`: link oficial de WhatsApp.
- `CONTACT_TO` e `CONTACT_FROM`: emails usados pelo formulário.
- Configurações `SMTP_*`, se `MAIL_DRIVER="smtp"`.
- `LEAD_LOG_RETENTION_DAYS`: retenção dos logs de leads e fallback em `storage/`.
- `LEAD_LOG_HASH_SALT`: sal usado para pseudonimizar IP, user-agent e hashes operacionais; defina um valor privado no `.env` real.
- `RECAPTCHA_ENABLED`: mantenha `false` em homologação ou domínios ainda não cadastrados no Google; use `true` apenas no `.env` real de produção.
- `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`, `RECAPTCHA_MIN_SCORE`, `RECAPTCHA_ALLOWED_HOSTNAME` e `RECAPTCHA_ACTION`: configuram o reCAPTCHA v3 do formulário; mantenha os segredos somente no `.env` não versionado da produção.

O arquivo `.env` não é versionado. Não coloque chaves secretas de SMTP/reCAPTCHA em `.env.example`, README ou arquivos de backup versionados.

## Execução Local

```bash
composer install
bash scripts/dev-local.sh
```

Abra `http://127.0.0.1:8000/`.

## Criando uma nova landing

1. Copie este projeto para o novo diretório, por exemplo `/var/www/pediatria`.
2. Remova a identidade Git herdada se o destino for outro repositório.
3. Ajuste `.env`: `APP_BASE`, nome público, links sociais, WhatsApp, SMTP e reCAPTCHA.
4. Troque textos em `config/content/landing.php` e imagens em `public/assets/img/hero/` e `public/assets/img/social/`.
5. Rode `composer test` e `bash scripts/run-tests.sh --url "http://127.0.0.1:8000/"`.

Também há um gerador para criar uma cópia limpa do protótipo:

```bash
bash scripts/create-landing.sh pediatria --name "Clínica Pediátrica" --mark P --palette emerald --request-prefix PED
```

## Testes

```bash
composer test
npx playwright test
```

Os scripts em `scripts/` também mantêm smoke tests de paleta, formulário e frontend para ambientes publicados.

Valide conteúdo, SEO e assets antes de publicar:

```bash
php scripts/validate-landing-content.php
```

## Privacidade e retenção

`lead-events.log` registra eventos operacionais sem nome, telefone, email, mensagem, IP ou user-agent em texto claro. Quando o e-mail falha, `contatos-fallback.log` guarda o contato completo para recuperação manual e deve ser retido por pouco tempo.

Limpeza manual ou por cron:

```bash
php scripts/prune-lead-data.php --days 30
php scripts/prune-lead-data.php --days 30 --dry-run
```

## Conteúdo

O conteúdo principal está em:

- `config/content/landing.php`
- `views/partials/navbar.twig`
- `views/partials/footer.twig`
- `public/assets/img/`

Para protótipos com mais de uma variação no mesmo repositório, crie outro arquivo em `config/content/`, por exemplo `config/content/pediatria.php`, e aponte `APP_CONTENT_FILE="pediatria"`. Quando `APP_CONTENT_FILE` não é informado, o app tenta `APP_SLUG` e depois volta para `landing`.

A seção `seo` em `config/content/landing.php` controla título, descrição, Open Graph, Twitter Card e JSON-LD. Para novos nichos, ajuste principalmente `seo.schema.type`, por exemplo `MedicalClinic`, `Dentist` ou `VeterinaryCare`, além de imagem social, área atendida e serviços.

Use `typography.profile` para diferenciar a personalidade visual de cada landing sem alterar o layout. Perfis disponíveis: `clinical` para clínica médica, `family` para pediatria/família, `premium` para estética ou odontologia de alto padrão, `warm` para veterinária ou atendimento acolhedor e `technical` para páginas mais objetivas. O perfil troca famílias tipográficas, pesos e ritmo dos títulos via CSS variables.

Os presets recomendados para cada nicho ficam em `config/presets/niches.php`. O gerador `scripts/create-landing.sh` usa esses presets para sugerir nome, paleta, tipografia, schema SEO e prefixo de protocolo quando o slug é conhecido.

As imagens principais seguem nomes padronizados: `public/assets/img/hero/{slug}-640.webp`, `{slug}-960.webp`, `{slug}-1896.webp`, `{slug}-mobile-640.webp` e `public/assets/img/social/{slug}-og.jpg`. O corte mobile é vertical para preservar o rosto/atendimento em telas estreitas. O gerador renomeia os placeholders para o slug novo; depois substitua esses arquivos por imagens finais do nicho.

Após alterar templates em produção, limpe o cache Twig em `storage/cache/twig` ou rode o script de pós-update.
