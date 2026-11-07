import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, login, uniqueSuffix } from '../support/accounts';
import { day } from '../support/trainings';

test.describe('Mitra dan wilayah kerja', { tag: '@mitra' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('mendaftarkan mitra Bali lalu template WA memakai WITA', async ({ page }) => {
            const suffix = uniqueSuffix();
            const partner = `E2E Mitra Bali ${suffix}`;
            const name = `E2E Pelatihan Bali ${suffix}`;

            await page.goto('/dashboard');
            await expect(page.getByText('Wilayah kerja: DIY, Jawa Tengah, dan Bali')).toBeVisible();
            await page.getByRole('link', { name: 'Mitra' }).click();
            await page.getByLabel('Nama mitra').fill(partner);
            await page.getByLabel('Wilayah kerja').selectOption('bali');
            await page.getByRole('button', { name: 'Tambah mitra' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Mitra didaftarkan' })).toBeVisible();

            // Duplikat (beda huruf besar/kecil dan spasi) ditolak.
            await page.getByLabel('Nama mitra').fill(partner.toUpperCase().replace(' ', '  '));
            await page.getByLabel('Wilayah kerja').selectOption('diy');
            await page.getByRole('button', { name: 'Tambah mitra' }).click();
            await expect(page.getByText(/sudah terdaftar/).first()).toBeVisible();

            await page.goto('/trainings/create');
            await page.getByLabel('Nama/tema pelatihan').fill(name);
            const team = await page.locator('#work_team_id option', { hasText: '(DTA)' }).first().getAttribute('value');
            await page.selectOption('#work_team_id', team ?? '');
            await page.getByLabel(/^Mitra \(bila/).fill(partner);
            await page.getByLabel(/^Wilayah kerja/).selectOption('diy');
            await page.getByLabel(/^Awal pendaftaran/).fill(day(20));
            await page.getByLabel(/^Akhir pendaftaran/).fill(day(25));
            await page.getByLabel(/^Awal pelatihan/).fill(day(27));
            await page.getByLabel(/^Akhir pelatihan/).fill(day(29));
            await expect(page.getByLabel(/^Jam penutupan pendaftaran/)).toHaveValue('23:59');
            await page.getByRole('button', { name: 'Simpan draft' }).click();

            // Wilayah mengikuti mitra (Bali), bukan isian DIY.
            await expect(page.getByRole('heading', { name, level: 1 })).toBeVisible();
            await expect(page.getByText('Bali (WITA)')).toBeVisible();
            await expect(page.getByText(/pukul 00\.59 WITA \(.* pukul 23\.59 WIB\)/)).toBeVisible();

            await page.getByRole('link', { name: 'Template WA' }).click();
            await expect(page.getByLabel(/Teks template default/)).toHaveValue(/Pendaftaran ditutup: .* pukul 00\.59 WITA \(.* pukul 23\.59 WIB\)/);
        });
    });

    test('pegawai biasa melihat daftar mitra tanpa formulir tambah', async ({ page }) => {
        await login(page, accounts.employee);
        await page.getByRole('link', { name: 'Mitra' }).click();

        await expect(page.getByRole('heading', { name: 'Mitra', level: 1 })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Tambah mitra' })).toHaveCount(0);
    });
});
