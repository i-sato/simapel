import { expect, test } from '@playwright/test';
import { adminStorageState } from '../support/accounts';

test.describe('Tata letak', { tag: '@layout' }, () => {
    test.use({ storageState: adminStorageState });

    test('menu samping menandai halaman aktif', async ({ page }) => {
        await page.goto('/dashboard');

        const nav = page.getByRole('navigation', { name: 'MENU UTAMA' });
        await expect(nav.getByRole('link', { name: 'Dashboard' })).toHaveAttribute('aria-current', 'page');
    });

    test('tautan lewati konten dapat dijangkau dengan keyboard', async ({ page }) => {
        await page.goto('/dashboard');
        await page.keyboard.press('Tab');

        await expect(page.getByRole('link', { name: 'Lewati ke konten utama' })).toBeFocused();
    });

    test('layar ponsel tidak memiliki gulir horizontal', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/dashboard');

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(overflow).toBeLessThanOrEqual(0);
        await expect(page.getByRole('button', { name: 'Keluar' })).toBeVisible();
    });
});
