import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, e2ePassword, login } from '../support/accounts';

test.describe('Keamanan akun', { tag: '@account' }, () => {
    test('kata sandi awal wajib diganti sebelum memakai aplikasi', async ({ page }) => {
        await login(page, accounts.mustChangePassword);

        await expect(page).toHaveURL(/\/account\/password$/);
        await expect(page.getByRole('alert').filter({ hasText: 'Ganti kata sandi awal' })).toBeVisible();

        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/account\/password$/);

        const newPassword = `${e2ePassword()}Z9`;
        await page.getByLabel('Kata sandi saat ini').fill(e2ePassword());
        await page.getByLabel('Kata sandi baru', { exact: true }).fill(newPassword);
        await page.getByLabel('Ulangi kata sandi baru').fill(newPassword);
        await page.getByRole('button', { name: 'Simpan kata sandi baru' }).click();

        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page.getByRole('status')).toContainText('Kata sandi berhasil diganti');
    });

    test.describe('dengan sesi admin', () => {
        test.use({ storageState: adminStorageState });

        test('kata sandi lemah ditolak dengan pesan berbahasa Indonesia', async ({ page }) => {
            await page.goto('/account/password');
            await page.getByLabel('Kata sandi saat ini').fill(e2ePassword());
            await page.getByLabel('Kata sandi baru', { exact: true }).fill('lemahsekali');
            await page.getByLabel('Ulangi kata sandi baru').fill('lemahsekali');
            await page.getByRole('button', { name: 'Simpan kata sandi baru' }).click();

            await expect(page).toHaveURL(/\/account\/password$/);
            await expect(page.getByRole('alert')).toContainText('kata sandi baru');
        });
    });
});
