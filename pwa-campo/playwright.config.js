import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './tests',
  timeout: 45_000,
  workers: 1,
  reporter: [['line']],
  use: {
    ...devices['Pixel 7'],
    baseURL: 'http://localhost:8082',
    channel: 'msedge',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
})
