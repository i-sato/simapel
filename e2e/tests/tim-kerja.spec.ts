import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, e2ePassword, login, syntheticNip, uniqueSuffix } from '../support/accounts';

test.describe('Tim kerja', { tag: '@tim-kerja' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('menambahkan, mengubah peran, dan mengeluarkan anggota tim', async ({ page }) => {
            const suffix = uniqueSuffix();
            const name = `E2E Anggota ${suffix}`;

            await page.goto('/employees/create');
            await page.getByLabel('Nama', { exact: true }).fill(name);
            await page.getByLabel('NIP', { exact: true }).fill(syntheticNip(suffix));
            await page.getByLabel('Kata sandi awal', { exact: true }).fill(e2ePassword());
            await page.getByLabel('Ulangi kata sandi awal').fill(e2ePassword());
            await page.getByRole('button', { name: 'Tambah pegawai' }).click();
            await expect(page).toHaveURL(/\/employees$/);

            await page.getByRole('link', { name: 'Tim Kerja' }).click();
            await page.getByRole('link', { name: 'Lihat anggota Talenta Digital' }).click();
            await expect(page.getByRole('heading', { name: /Talenta Digital/, level: 1 })).toBeVisible();

            // Keluarkan sisa anggota sintetis dari eksekusi sebelumnya yang gagal,
            // agar kursi Ketua/Sekretaris tidak terisi dan uji dapat diulang.
            const leftovers = page.getByRole('row', { name: /E2E Anggota / });
            while ((await leftovers.count()) > 0) {
                page.once('dialog', (dialog) => dialog.accept());
                await leftovers.first().getByRole('button', { name: 'Keluarkan' }).click();
                await expect(page.getByRole('status').filter({ hasText: 'Anggota dikeluarkan' })).toBeVisible();
            }

            await page.getByLabel('Pegawai', { exact: true }).selectOption({ label: name });
            await page.getByLabel('Peran di tim', { exact: true }).selectOption('anggota');
            await page.getByRole('button', { name: 'Tambah ke tim' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Anggota tim disimpan' })).toBeVisible();

            const row = page.getByRole('row', { name: new RegExp(name) });
            await row.getByLabel(`Peran ${name}`).selectOption('sekretaris');
            await row.getByRole('button', { name: 'Simpan' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Peran anggota diperbarui' })).toBeVisible();

            page.once('dialog', (dialog) => dialog.accept());
            await page.getByRole('row', { name: new RegExp(name) }).getByRole('button', { name: 'Keluarkan' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Anggota dikeluarkan' })).toBeVisible();
            await expect(page.getByRole('row', { name: new RegExp(name) })).toHaveCount(0);
        });
    });

    test('pegawai biasa melihat tim kerja tanpa kontrol pengelolaan', async ({ page }) => {
        await login(page, accounts.employee);
        await page.getByRole('link', { name: 'Tim Kerja' }).click();

        await expect(page.getByRole('heading', { name: 'Keterampilan Digital Dasar' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'ASN Digital' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Talenta Digital' })).toBeVisible();

        await page.getByRole('link', { name: 'Lihat anggota ASN Digital' }).click();
        await expect(page.getByRole('heading', { name: 'Tambah anggota' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Keluarkan' })).toHaveCount(0);
    });
});
