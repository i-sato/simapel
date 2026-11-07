import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, e2ePassword, login, syntheticNip, uniqueSuffix } from '../support/accounts';
import { createCompleteTraining } from '../support/trainings';

test.describe('Beban kerja', { tag: '@beban-kerja' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('matriks menampilkan penugasan pegawai dan detailnya', async ({ page }) => {
            const suffix = uniqueSuffix();
            const employee = `E2E Beban ${suffix}`;
            const training = `E2E Pelatihan Beban ${suffix}`;

            await page.goto('/employees/create');
            await page.getByLabel('Nama', { exact: true }).fill(employee);
            await page.getByLabel('NIP', { exact: true }).fill(syntheticNip(suffix));
            await page.getByLabel('Kata sandi awal', { exact: true }).fill(e2ePassword());
            await page.getByLabel('Ulangi kata sandi awal').fill(e2ePassword());
            await page.getByRole('button', { name: 'Tambah pegawai' }).click();
            await expect(page).toHaveURL(/\/employees$/);

            // Pendaftaran H+5 sampai pelatihan H+12: masuk rentang bawaan (bulan berjalan + 2 bulan).
            await createCompleteTraining(page, training, 'DTA', [5, 8, 10, 12]);
            await page.getByRole('link', { name: 'Atur penugasan' }).click();
            await page.getByLabel(`Panitia: ${employee}`).check();
            await page.getByRole('button', { name: 'Simpan penugasan' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Penugasan disimpan' })).toBeVisible();

            await page.getByRole('link', { name: 'Beban Kerja' }).click();
            await expect(page.getByRole('heading', { name: 'Beban Kerja', level: 1 })).toBeVisible();
            const row = page.getByRole('row', { name: new RegExp(employee) });
            await expect(row.locator('td.load-1, td.load-2, td.load-3').first()).toBeVisible();

            await page.getByLabel('Tampilan').selectOption('bulan');
            await page.getByRole('button', { name: 'Terapkan' }).click();
            await expect(page.getByRole('columnheader', { name: /\d{4}$/ }).first()).toBeVisible();

            await page.getByRole('link', { name: employee }).click();
            await expect(page.getByRole('heading', { name: employee, level: 1 })).toBeVisible();
            await expect(page.getByRole('row', { name: new RegExp(training) })).toContainText('Panitia');
        });
    });

    test('pegawai biasa melihat matriks; tampilan ponsel tanpa gulir horizontal halaman', async ({ page }) => {
        await login(page, accounts.employee);
        await page.setViewportSize({ width: 390, height: 844 });
        await page.getByRole('link', { name: 'Beban Kerja' }).click();

        await expect(page.getByRole('heading', { name: 'Matriks pegawai × periode' })).toBeVisible();
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(overflow).toBeLessThanOrEqual(0);

        // Tabel boleh bergulir di dalam wadahnya.
        const wrap = page.locator('.table-wrap').first();
        if ((await wrap.count()) > 0) {
            const inner = await wrap.evaluate((element) => element.scrollWidth >= element.clientWidth);
            expect(inner).toBe(true);
        }
    });
});
