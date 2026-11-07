import { expect, test } from '@playwright/test';
import { adminStorageState, uniqueSuffix } from '../support/accounts';
import { createCompleteTraining } from '../support/trainings';

test.describe('Administrasi', { tag: '@administrasi' }, () => {
    test.use({ storageState: adminStorageState });

    test('finalisasi membuat BAP/SK/ST yang dapat diperbarui sampai Selesai', async ({ page }) => {
        const name = `E2E Administrasi ${uniqueSuffix()}`;
        await createCompleteTraining(page, name);

        // Draft belum memiliki kartu Administrasi.
        await expect(page.getByRole('heading', { name: 'Administrasi', exact: true })).toHaveCount(0);

        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Finalisasi' }).click();
        await expect(page.getByRole('status').filter({ hasText: 'Pelatihan difinalisasi' })).toBeVisible();

        const card = page.getByRole('region', { name: 'Administrasi', exact: true });
        await expect(card).toContainText('0/3 selesai');
        await expect(card.getByRole('row', { name: /BAP penetapan peserta/ })).toBeVisible();
        await expect(card.getByRole('row', { name: /SK peserta/ })).toBeVisible();
        await expect(card.getByRole('row', { name: /Surat Tugas/ })).toBeVisible();
        await expect(card.getByLabel('Penanggung jawab BAP')).toBeVisible();

        await card.getByRole('link', { name: 'Perbarui BAP' }).click();
        await page.getByLabel('Status').selectOption('selesai');
        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByText('BAP Selesai wajib memiliki nomor.').first()).toBeVisible();

        await page.getByLabel('Status').selectOption('selesai');
        await page.getByLabel('Nomor dokumen').fill('BAP/E2E/2026');
        await page.getByLabel('Tanggal dokumen').fill(new Date().toISOString().slice(0, 10));
        await page.getByLabel('Link berkas').fill('https://drive.example.test/bap-e2e');
        await page.getByRole('button', { name: 'Simpan' }).click();

        await expect(page.getByRole('status').filter({ hasText: 'BAP diperbarui' })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Administrasi', exact: true })).toContainText('1/3 selesai');
        await expect(page.getByRole('link', { name: 'Buka berkas ↗' })).toHaveAttribute('href', 'https://drive.example.test/bap-e2e');
    });
});
