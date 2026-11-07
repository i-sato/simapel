import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, login, maskNip } from '../support/accounts';

test.describe('Dashboard', { tag: '@dashboard' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('ringkasan akun hanya menampilkan NIP tersamar', async ({ page }) => {
            await page.goto('/dashboard');

            await expect(page.getByRole('heading', { name: 'Akun Anda' })).toBeVisible();
            await expect(page.getByText(maskNip(accounts.admin))).toBeVisible();
            await expect(page.getByText('Super Admin', { exact: true }).first()).toBeVisible();
            expect(await page.content()).not.toContain(accounts.admin);
        });

        test('kartu ringkasan dan daftar tindakan tampil', async ({ page }) => {
            await page.goto('/dashboard');

            const summary = page.getByRole('region', { name: 'Ringkasan pelatihan' });
            for (const label of ['Pelatihan berjalan', 'Berkas belum lengkap', 'Segera berakhir', 'Bentrok penugasan']) {
                await expect(summary.getByText(label, { exact: true })).toBeVisible();
            }
            await expect(page.getByRole('heading', { name: 'Perlu tindakan saya' })).toBeVisible();
            await expect(page.getByRole('heading', { name: 'Pelatihan berjalan', level: 2 })).toBeVisible();
        });
    });

    test('pegawai biasa melihat ringkasan pelatihan berjalan', async ({ page }) => {
        await login(page, accounts.employee);
        await expect(page).toHaveURL(/\/dashboard$/);

        await expect(page.getByRole('heading', { name: 'Pelatihan berjalan', level: 2 })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Perlu tindakan saya' })).toBeVisible();
    });
});
