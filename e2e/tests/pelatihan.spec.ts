import { expect, test, type Page } from '@playwright/test';
import { accounts, adminStorageState, login, uniqueSuffix } from '../support/accounts';

// Tanggal relatif terhadap hari ini agar status waktu selalu "Akan dibuka".
function day(offset: number): string {
    const date = new Date();
    date.setDate(date.getDate() + offset);
    return date.toISOString().slice(0, 10);
}

async function selectTeam(page: Page, code: string): Promise<void> {
    const value = await page.locator('#work_team_id option', { hasText: `(${code})` }).first().getAttribute('value');
    await page.selectOption('#work_team_id', value ?? '');
}

test.describe('Pelatihan', { tag: '@pelatihan' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('membuat draft parsial, melengkapi, lalu memfinalisasi', async ({ page }) => {
            const name = `E2E Pelatihan ${uniqueSuffix()}`;

            await page.goto('/dashboard');
            await page.getByRole('link', { name: 'Pelatihan' }).click();
            await page.getByRole('link', { name: '＋ Draft pelatihan' }).click();
            await expect(page.getByRole('heading', { name: 'Draft pelatihan baru' })).toBeVisible();

            await page.getByLabel('Nama/tema pelatihan').fill(name);
            await selectTeam(page, 'DTA');
            await page.getByRole('button', { name: 'Simpan draft' }).click();

            await expect(page.getByRole('status').filter({ hasText: 'Draft pelatihan dibuat' })).toBeVisible();
            await expect(page.getByRole('heading', { name, level: 1 })).toBeVisible();
            await expect(page.getByText('Belum dapat difinalisasi')).toBeVisible();
            await expect(page.getByText('Jadwal belum lengkap')).toBeVisible();

            await page.getByRole('link', { name: 'Ubah draft' }).click();
            await page.getByLabel(/^Batch/).fill('1');
            await page.getByLabel(/^Lokasi/).fill('Aula E2E, Yogyakarta');
            await page.getByLabel(/^Metode/).selectOption('daring');
            await page.getByLabel(/^Wilayah kerja/).selectOption('diy');
            await page.getByLabel(/^Awal pendaftaran/).fill(day(30));
            await page.getByLabel(/^Akhir pendaftaran/).fill(day(40));
            await page.getByLabel(/^Awal pelatihan/).fill(day(39));
            await page.getByLabel(/^Akhir pelatihan/).fill(day(47));
            await page.getByLabel(/^Link pendaftaran/).fill('https://digitalent.example.test/daftar');
            await page.getByLabel(/^Link silabus/).fill('https://drive.example.test/silabus');
            await page.getByLabel(/^Level/).selectOption('dasar');
            await page.getByLabel(/^Sertifikasi/).selectOption('industri');
            await page.getByLabel(/^Certificate of Completion/).selectOption('1');
            await page.getByLabel(/^Pelaksanaan/).selectOption('swakelola');
            await page.getByLabel(/^Peserta/).selectOption('umum');
            await page.getByLabel(/^Zonasi/).selectOption('wilayah_kerja');
            await page.getByLabel(/^Jumlah JP/).fill('32');
            await page.getByLabel(/^Target peserta/).fill('100');
            await page.getByLabel(/^Kuota pendaftar/).fill('300');
            await page.getByRole('button', { name: 'Simpan perubahan' }).click();

            // Pelatihan tidak boleh dimulai sebelum pendaftaran ditutup.
            await expect(page.getByText('Pelatihan tidak boleh dimulai sebelum pendaftaran ditutup.').first()).toBeVisible();
            await page.getByLabel(/^Awal pelatihan/).fill(day(45));
            await page.getByRole('button', { name: 'Simpan perubahan' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Pelatihan diperbarui' })).toBeVisible();
            await expect(page.getByText('Belum dapat difinalisasi')).toHaveCount(0);
            await expect(page.getByText('Akan dibuka', { exact: true })).toBeVisible();

            page.once('dialog', (dialog) => dialog.accept());
            await page.getByRole('button', { name: 'Finalisasi' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Pelatihan difinalisasi' })).toBeVisible();
            await expect(page.getByText('Final', { exact: true })).toBeVisible();
            await expect(page.getByRole('heading', { name: 'Ketentuan SOP tersalin saat finalisasi' })).toBeVisible();
            await expect(page.getByRole('button', { name: 'Finalisasi' })).toHaveCount(0);

            await page.getByRole('link', { name: '← Daftar' }).click();
            await page.getByLabel('Tim kerja').selectOption('DTA');
            await page.getByLabel('Status').selectOption('final');
            await page.getByRole('button', { name: 'Terapkan' }).click();
            await expect(page.getByRole('link', { name })).toBeVisible();
        });

        test('menampilkan tautan SOP atau badge peringatan dengan penjelasan', async ({ page }) => {
            await page.goto('/trainings/create');
            const section = page.getByRole('region', { name: 'SOP tim kerja' });
            const badges = section.locator('.badge.warning[title]');
            const links = section.getByRole('link', { name: /SOP tim kerja/ });
            expect((await badges.count()) + (await links.count())).toBeGreaterThan(0);
            if ((await badges.count()) > 0) {
                await expect(badges.first()).toHaveAttribute('title', /Pengaturan/);
            }
        });
    });

    test('pegawai biasa melihat daftar tanpa kontrol pengelolaan', async ({ page }) => {
        await login(page, accounts.employee);
        await page.getByRole('link', { name: 'Pelatihan' }).click();

        await expect(page.getByRole('heading', { name: 'Pelatihan', level: 1 })).toBeVisible();
        await expect(page.getByRole('link', { name: '＋ Draft pelatihan' })).toHaveCount(0);
        const response = await page.goto('/trainings/create');
        expect(response?.status()).toBe(403);
    });
});
