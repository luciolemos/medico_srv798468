const { test, expect } = require('@playwright/test');

test('clinic landing renders core content and contact flow', async ({ page }) => {
  await page.goto('./');

  await expect(page.getByRole('heading', { name: /Cuidado médico/i })).toBeVisible();
  await expect(page.getByRole('heading', { name: /Serviços da clínica/i })).toBeVisible();
  await expect(page.getByRole('heading', { name: /Solicite seu agendamento/i })).toBeVisible();

  await page.getByRole('link', { name: /Agendar consulta/i }).first().click();
  await expect(page.locator('#form-orcamento')).toBeInViewport();

  await page.locator('#cta-nome').fill('Paciente Teste');
  await page.locator('#cta-telefone').fill('(84) 99999-9999');
  await page.locator('#cta-email').fill('paciente@example.com');
  await page.getByRole('button', { name: /Próximo/i }).click();

  await expect(page.locator('#cta-mensagem')).toBeVisible();
  await page.locator('#cta-mensagem').fill('Gostaria de agendar uma consulta clínica.');
  await expect(page.getByRole('button', { name: /Enviar solicitação/i })).toBeVisible();
});
