// @ts-check

const { defineConfig, devices } = require('@playwright/test');

const baseURL = process.env.E2E_BASE_URL || 'http://127.0.0.1:8000/';

module.exports = defineConfig({
  testDir: './tests/e2e',
  timeout: 45_000,
  retries: 0,
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure'
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] }
    }
  ]
});