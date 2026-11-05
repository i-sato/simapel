import { expect, test } from '@playwright/test';
import { accounts, expectGenericLoginError, login } from '../support/accounts';

test.describe('Autentikasi', { tag: '@auth' }, () => {
    test('pengunjung diarahkan ke halaman masuk', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('heading', { name: 'Masuk ke SIMAPEL' })).toBeVisible();
    });

    test('NIP harus 18 digit sebelum formulir dikirim', async ({ page }) => {
        await page.goto('/login');
        const nip = page.getByLabel('NIP', { exact: true });
        await nip.fill('admin@simapel');

        expect(await nip.evaluate((input: HTMLInputElement) => input.checkValidity())).toBe(false);
    });

    test('kredensial salah ditolak dengan pesan umum', async ({ page }) => {
        const unknownNip = `9999${Date.now().toString().slice(-14).padStart(14, '0')}`;
        await login(page, unknownNip, 'Bukan-Sandi-2026!');

        await expectGenericLoginError(page);
    });

    test('akun nonaktif tidak dapat masuk', async ({ page }) => {
        await login(page, accounts.inactive);

        await expectGenericLoginError(page);
    });

    test('masuk lalu keluar', async ({ page }) => {
        await login(page, accounts.admin);
        await expect(page.getByRole('heading', { name: 'Dashboard', level: 1 })).toBeVisible();

        await page.getByRole('button', { name: 'Keluar' }).click();

        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('status')).toHaveText('Anda telah keluar dari SIMAPEL.');
    });
});
