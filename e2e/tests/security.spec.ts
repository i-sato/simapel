import { expect, test, type Page } from '@playwright/test';
import { adminStorageState } from '../support/accounts';

function collectCspViolations(page: Page): string[] {
    const violations: string[] = [];
    page.on('console', (message) => {
        if (message.type() === 'error' && /Content Security Policy/i.test(message.text())) {
            violations.push(message.text());
        }
    });

    return violations;
}

test.describe('Keamanan halaman', { tag: '@security' }, () => {
    test('header keamanan dan CSP ketat pada halaman masuk', async ({ page }) => {
        const response = await page.goto('/login');
        const headers = response?.headers() ?? {};

        expect(headers['content-security-policy']).toContain("script-src 'self'");
        expect(headers['content-security-policy']).not.toContain('unsafe-inline');
        expect(headers['x-frame-options']).toBe('DENY');
        expect(headers['x-content-type-options']).toBe('nosniff');
        expect(headers['x-powered-by']).toBeUndefined();
    });

    test('halaman masuk tanpa pelanggaran CSP', async ({ page }) => {
        const violations = collectCspViolations(page);
        await page.goto('/login');

        expect(violations).toEqual([]);
    });

    test.describe('dengan sesi admin', () => {
        test.use({ storageState: adminStorageState });

        test('halaman terautentikasi tanpa pelanggaran CSP dan tidak di-cache', async ({ page }) => {
            const violations = collectCspViolations(page);

            const response = await page.goto('/dashboard');
            expect(response?.headers()['cache-control']).toContain('no-store');
            await page.goto('/account/password');

            expect(violations).toEqual([]);
        });
    });
});
