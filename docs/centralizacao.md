# Centralizacao de Templates (NatalCode Core)

## Objetivo
Padronizar os sites provisionados para usar a mesma base visual/comportamental do `natalcode`, variando apenas a paleta.

## Conceito
- Core (unico): estrutura Twig, CSS/JS de comportamento e componentes.
- Variação: somente paleta (`APP_PALETTE` + `public/assets/css/palettes/*.css`).

## Arquivos core (fonte de verdade)
No `natalcode`:
- `views/base.twig`
- `views/pages/home.twig`
- `views/partials/navbar.twig`
- `views/partials/footer.twig`
- `public/assets/css/landing.css`
- `public/assets/js/landing.js`
- `public/index.php`
- `src/Controllers/HomeController.php`
- `public/assets/css/palettes/*.css`

## Paletas permitidas
- `blue`
- `red`
- `emerald`
- `amber`
- `violet`

## Regras de resolucao de paleta
Ordem aplicada no front:
1. Query string `?palette=<cor>` (se valida)
2. `localStorage.palette` (se valida)
3. Valor backend (`APP_PALETTE`)

Fallback: `blue`.

## Fluxo recomendado para novos sites
1. Provisionar site normalmente no Labs.
2. Sincronizar arquivos core do `natalcode` para o novo slug.
3. Definir `APP_PALETTE` no `.env` do site.
4. Validar visual por tema (dark/light) e breakpoints (mobile/tablet/desktop).

## Sincronizacao manual (exemplo)
> Ajuste `<slug>` para o site alvo.

```bash
# Backup rapido do alvo
sudo mkdir -p /var/www/<slug>/storage/backup-sync
sudo cp -a \
  /var/www/<slug>/views/base.twig \
  /var/www/<slug>/views/pages/home.twig \
  /var/www/<slug>/views/partials/navbar.twig \
  /var/www/<slug>/views/partials/footer.twig \
  /var/www/<slug>/public/assets/css/landing.css \
  /var/www/<slug>/public/assets/js/landing.js \
  /var/www/<slug>/public/index.php \
  /var/www/<slug>/src/Controllers/HomeController.php \
  /var/www/<slug>/storage/backup-sync/

# Sync do core
sudo rsync -a /var/www/natalcode/views/base.twig /var/www/<slug>/views/base.twig
sudo rsync -a /var/www/natalcode/views/pages/home.twig /var/www/<slug>/views/pages/home.twig
sudo rsync -a /var/www/natalcode/views/partials/navbar.twig /var/www/<slug>/views/partials/navbar.twig
sudo rsync -a /var/www/natalcode/views/partials/footer.twig /var/www/<slug>/views/partials/footer.twig
sudo rsync -a /var/www/natalcode/public/assets/css/landing.css /var/www/<slug>/public/assets/css/landing.css
sudo rsync -a /var/www/natalcode/public/assets/js/landing.js /var/www/<slug>/public/assets/js/landing.js
sudo rsync -a /var/www/natalcode/public/index.php /var/www/<slug>/public/index.php
sudo rsync -a /var/www/natalcode/src/Controllers/HomeController.php /var/www/<slug>/src/Controllers/HomeController.php
sudo rsync -a /var/www/natalcode/public/assets/css/palettes/ /var/www/<slug>/public/assets/css/palettes/
```

## Ajuste de paleta por site
```bash
sudo sed -i '/^APP_PALETTE=/d' /var/www/<slug>/.env
echo 'APP_PALETTE="blue"' | sudo tee -a /var/www/<slug>/.env >/dev/null
```

## Script de paleta em lote
Use no `natalcode`:

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

## Rollback
Se algo quebrar, restaurar do backup local do alvo:

```bash
sudo cp -a /var/www/<slug>/storage/backup-sync/* /var/www/<slug>/
```

## Checklist pos-sync
- [ ] Home abre sem erro no slug.
- [ ] Navbar funcional (menu mobile, tema, seletor de paleta).
- [ ] Paleta troca com `?palette=...`.
- [ ] `APP_BASE` consistente com alias Apache.
- [ ] Tema dark/light com contraste adequado.
- [ ] Tablet carousel funcional em `projects` e `depoimentos`.

## Problemas comuns
### 404 no slug
- Verificar `APP_BASE` no `.env`.
- Exemplo: se URL e `/natalcode`, usar `APP_BASE="/natalcode"`.

### Site nao refletiu mudancas do natalcode
- O site tem arquivos proprios e nao herda automaticamente.
- Necessario rodar sync manual ou automatizar no Labs.

### Permissao negada ao sincronizar
- Rodar comandos com `sudo`.
- Garantir ownership final:

```bash
sudo chown -R www-data:www-data /var/www/<slug>
```
