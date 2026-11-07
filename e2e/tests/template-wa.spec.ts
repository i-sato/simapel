import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, login, uniqueSuffix } from '../support/accounts';

function day(offset: number): string {
    const date = new Date();
    date.setDate(date.getDate() + offset);
    return date.toISOString().slice(0, 10);
}

test.describe('Template WA', { tag: '@template-wa' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('pratinjau, salin, buka WhatsApp, kustom, dan reset', async ({ page }) => {
            const name = `E2E Template ${uniqueSuffix()}`;

            await page.goto('/trainings/create');
            await page.getByLabel('Nama/tema pelatihan').fill(name);
            const team = await page.locator('#work_team_id option', { hasText: '(DTA)' }).first().getAttribute('value');
            await page.selectOption('#work_team_id', team ?? '');
            await page.getByLabel(/^Awal pendaftaran/).fill(day(20));
            await page.getByLabel(/^Akhir pendaftaran/).fill(day(25));
            await page.getByLabel(/^Awal pelatihan/).fill(day(27));
            await page.getByLabel(/^Akhir pelatihan/).fill(day(29));
            await page.getByLabel(/^Link pendaftaran/).fill('https://digitalent.example.test/pelatihan/e2e');
            await page.getByRole('button', { name: 'Simpan draft' }).click();

            await page.getByRole('link', { name: 'Template WA' }).click();
            const preview = page.getByLabel(/Teks template default/);
            await expect(preview).toHaveValue(new RegExp(`peserta pelatihan ${name}`));
            await expect(preview).toHaveValue(/Daftar peserta: \(menyusul\)/);
            await expect(preview).toHaveValue(/alias gratis/);

            await page.getByRole('button', { name: 'Salin' }).click();
            await expect(page.locator('#copy-status')).toHaveText(/tersalin|salin manual/);
            await expect(page.getByRole('link', { name: 'Buka di WhatsApp' })).toHaveAttribute('href', /^https:\/\/wa\.me\/\?text=Selamat%20datang/);

            await page.getByRole('link', { name: '＋ Template baru' }).click();
            const editor = page.getByLabel('Template baru');
            await editor.fill(`${await editor.inputValue()}\n{{ nomor_hp }}`);
            await page.getByRole('button', { name: 'Simpan template' }).click();
            await expect(page.getByText(/Placeholder tidak dikenal/).first()).toBeVisible();

            await page.getByLabel('Template baru').fill(`Halo semua 👋 {{nama_pelatihan}}\nDaftar: {{link_pendaftaran}}\nPendaftaran {{tanggal_pendaftaran}}, pelatihan {{tanggal_pelatihan}}\nPanduan: {{link_panduan_pelatihan}}\nPelatihan ini gratis 🚀`);
            await page.getByRole('button', { name: 'Simpan template' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Template disimpan' })).toBeVisible();
            await expect(page.getByRole('heading', { name: 'Template saya (1/3)' })).toBeVisible();
            await expect(page.getByLabel(/Teks template kustom/)).toHaveValue(new RegExp(`Halo semua 👋 ${name}`));

            page.once('dialog', (dialog) => dialog.accept());
            await page.getByRole('button', { name: 'Reset template 1' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Template dikembalikan ke default' })).toBeVisible();
            await expect(page.getByRole('heading', { name: 'Template saya (0/3)' })).toBeVisible();
        });
    });

    test('pegawai biasa yang bukan panitia mendapat 403', async ({ page }) => {
        await login(page, accounts.employee);
        await page.getByRole('link', { name: 'Pelatihan' }).click();
        const first = page.locator('table a[href*="/trainings/"]').first();

        if ((await first.count()) === 0) {
            test.skip(true, 'Belum ada pelatihan pada database ini.');
        }

        await first.click();
        await expect(page.getByRole('link', { name: 'Template WA' })).toHaveCount(0);
        const response = await page.goto(`${page.url()}/messages`);
        expect(response?.status()).toBe(403);
    });
});
