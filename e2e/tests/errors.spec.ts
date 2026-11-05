import { expect, test } from '@playwright/test';

test.describe('Halaman galat', { tag: '@errors' }, () => {
    test('404 memakai tema SIMAPEL', async ({ page }) => {
        const response = await page.goto('/halaman-yang-tidak-ada');

        expect(response?.status()).toBe(404);
        await expect(page.getByRole('heading', { name: 'Halaman tidak ditemukan' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Kembali ke SIMAPEL' })).toBeVisible();
    });
});
