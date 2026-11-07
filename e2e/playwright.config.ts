import { defineConfig, devices } from '@playwright/test';

/**
 * Dijalankan di container `playwright` (compose profile e2e) terhadap service `web`.
 * Lihat docs/pengujian.md untuk cara menjalankan dan aturan tag area UI.
 */
export default defineConfig({
    testDir: './tests',
    // Satu worker: uji Super Admin berbagi satu sesi (auth.setup.ts, karena login dibatasi
    // per IP), sehingga pesan flash dan error formulir tertukar bila uji berjalan paralel.
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
    outputDir: 'test-results',
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8080',
        locale: 'id-ID',
        timezoneId: 'Asia/Jakarta',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /.*\.setup\.ts/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
        },
    ],
});
