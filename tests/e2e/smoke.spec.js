const { test, expect } = require('@playwright/test');

test('palette switch updates CSS and persists after reload', async ({ page }) => {
  await page.goto('./');

  const paletteLink = page.locator('#paletteStylesheet');
  await expect(paletteLink).toHaveAttribute('href', /\/assets\/css\/palettes\/[a-z]+\.css/);

  const paletteFabToggle = page.getByRole('button', { name: /Abrir seletor de paletas/i });
  if (await paletteFabToggle.isVisible()) {
    await paletteFabToggle.click();
  }

  const redButton = page.locator('[data-palette-btn="red"]:visible').first();
  await expect(redButton).toBeVisible();
  await redButton.click();

  await expect(page).toHaveURL(/palette=red/);
  await expect(paletteLink).toHaveAttribute('href', /\/assets\/css\/palettes\/red\.css/);

  await page.reload();
  await expect(paletteLink).toHaveAttribute('href', /\/assets\/css\/palettes\/red\.css/);
});

test('copy toggle navigates to growth and back to soft', async ({ page }) => {
  await page.goto('./');

  const copyToggle = page.locator('#copyModeToggle');
  await copyToggle.click();
  await expect(page).toHaveURL(/copy=growth/);

  await page.locator('#copyModeToggle').click();
  await expect(page).not.toHaveURL(/copy=growth/);
});
