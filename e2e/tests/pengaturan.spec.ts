import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, login, uniqueSuffix } from '../support/accounts';

// Spec ini memakai tahun anggaran berikutnya dan menghapus draf yang dibuatnya,
// sehingga ketentuan pada database pengembangan tidak ikut berubah.
const nextYear = new Date().getFullYear() + 1;

test.describe('Pengaturan', { tag: '@pengaturan' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('menyimpan link panduan tim dan menampilkannya di halaman tim kerja', async ({ page }) => {
            const url = `https://drive.example.test/panduan-dta-${uniqueSuffix()}`;

            await page.goto('/dashboard');
            await page.getByRole('link', { name: 'Pengaturan' }).click();
            await expect(page.getByRole('heading', { name: 'Pengaturan', level: 1 })).toBeVisible();

            const guide = page.getByLabel('Talenta Digital (DTA)');
            const original = await guide.inputValue();
            await guide.fill(url);
            await page.getByRole('button', { name: 'Simpan link DTA' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Link panduan' })).toBeVisible();

            await page.getByRole('link', { name: 'Tim Kerja' }).click();
            await page.getByRole('link', { name: 'Lihat anggota Talenta Digital' }).click();
            await expect(page.getByRole('link', { name: /Panduan pendaftaran/ })).toHaveAttribute('href', url);

            // Kembalikan nilai semula agar database pengembangan tidak berubah.
            await page.goto('/settings');
            await page.getByLabel('Talenta Digital (DTA)').fill(original);
            await page.getByRole('button', { name: 'Simpan link DTA' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Link panduan' })).toBeVisible();
        });

        test('menolak link yang bukan http/https', async ({ page }) => {
            await page.goto('/settings');
            const field = page.getByLabel('Link login Digitalent', { exact: false });
            await field.evaluate((input: HTMLInputElement) => { input.type = 'text'; });
            await field.fill('javascript:alert(1)');
            await page.getByRole('button', { name: 'Simpan pengaturan umum' }).click();

            await expect(page.getByText(/http:\/\/ atau https:\/\//).first()).toBeVisible();
        });

        test('membuat, mengubah, dan menghapus draf ketentuan SOP', async ({ page }) => {
            const number = `E2E/SOP/${uniqueSuffix()}`;

            await page.goto(`/settings?year=${nextYear}`);
            await expect(page.getByRole('heading', { name: `Ketentuan SOP tahun anggaran ${nextYear}` })).toBeVisible();
            await page.getByRole('link', { name: `＋ Draf baru DTA ${nextYear}` }).click();

            await page.getByLabel('Nomor SOP').fill(number);
            await page.getByLabel('Link SOP').fill('https://drive.example.test/sop-e2e');
            await page.getByLabel('Tanggal mulai berlaku di aplikasi').fill(`${nextYear}-01-01`);
            await page.getByLabel(/Tenggat BAP/).fill('1');
            await page.getByLabel(/Tenggat SK/).fill('2');
            await page.getByLabel(/Tenggat Surat Tugas/).fill('2');
            await page.getByLabel(/Administrasi siap/).fill('1');
            await page.getByRole('button', { name: 'Buat draf' }).click();

            // Administrasi siap tidak boleh lebih awal dari tenggat dokumen.
            await expect(page.getByText(/tidak boleh lebih awal/)).toBeVisible();
            await page.getByLabel(/Administrasi siap/).fill('3');
            await page.getByRole('button', { name: 'Buat draf' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Draf ketentuan SOP dibuat' })).toBeVisible();

            const row = page.getByRole('row', { name: new RegExp(number) });
            await expect(row.getByText('Draf', { exact: true })).toBeVisible();
            await row.getByRole('link', { name: /^Ubah/ }).click();
            await page.getByLabel(/Administrasi siap/).fill('4');
            await page.getByRole('button', { name: 'Simpan draf' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Draf ketentuan SOP diperbarui' })).toBeVisible();
            await expect(page.getByRole('row', { name: new RegExp(number) }).getByRole('cell', { name: 'H+4' })).toBeVisible();

            page.once('dialog', (dialog) => dialog.accept());
            await page.getByRole('row', { name: new RegExp(number) }).getByRole('button', { name: /^Hapus/ }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Draf ketentuan SOP dihapus' })).toBeVisible();
            await expect(page.getByRole('row', { name: new RegExp(number) })).toHaveCount(0);
        });

        test('badge peringatan SOP memiliki penjelasan saat hover', async ({ page }) => {
            await page.goto('/settings');
            const badges = page.locator('.badge.warning[title]');
            const count = await badges.count();
            test.skip(count === 0, 'Semua tim sudah memiliki ketentuan berlaku pada database ini.');
            await expect(badges.first()).toHaveAttribute('title', /Pengaturan/);
        });
    });

    test('pegawai biasa tidak melihat menu dan mendapat 403', async ({ page }) => {
        await login(page, accounts.employee);

        await expect(page.getByRole('link', { name: 'Pengaturan' })).toHaveCount(0);
        const response = await page.goto('/settings');
        expect(response?.status()).toBe(403);
    });
});
