import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, e2ePassword, login, maskNip, syntheticNip, uniqueSuffix } from '../support/accounts';

test.describe('Pegawai (Super Admin)', { tag: '@pegawai' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('daftar pegawai hanya menampilkan NIP tersamar', async ({ page }) => {
            await page.goto('/employees');

            await expect(page.getByRole('heading', { name: 'Pegawai', level: 1 })).toBeVisible();
            await page.getByLabel('Cari nama, jabatan, email, atau NIP lengkap').fill('Pengguna E2E');
            await page.getByRole('button', { name: 'Terapkan' }).click();

            await expect(page.getByRole('cell', { name: maskNip(accounts.admin) })).toBeVisible();
            expect(await page.content()).not.toContain(accounts.admin);
        });

        test('menambah pegawai dengan kata sandi awal wajib diganti', async ({ page }) => {
            const suffix = uniqueSuffix();
            const name = `E2E Pegawai ${suffix}`;

            await page.goto('/employees/create');
            await page.getByLabel('Nama', { exact: true }).fill(name);
            await page.getByLabel('NIP', { exact: true }).fill(syntheticNip(suffix));
            await page.getByLabel('Pangkat/Gol. Ruang').fill('Penata Muda - III/a');
            await page.getByLabel('Jabatan').fill('Pranata Komputer');
            await page.getByLabel('Dapat ditugaskan sebagai Admin pelatihan').check();
            await page.getByLabel('Kata sandi awal', { exact: true }).fill(e2ePassword());
            await page.getByLabel('Ulangi kata sandi awal').fill(e2ePassword());
            await page.getByRole('button', { name: 'Tambah pegawai' }).click();

            await expect(page).toHaveURL(/\/employees$/);
            await expect(page.getByRole('status')).toContainText('Pegawai ditambahkan');

            await page.getByLabel('Cari nama, jabatan, email, atau NIP lengkap').fill(name);
            await page.getByRole('button', { name: 'Terapkan' }).click();
            const row = page.getByRole('row', { name: new RegExp(name) });
            await expect(row.getByText('Wajib ganti sandi')).toBeVisible();
            await expect(row.getByText('Admin', { exact: true })).toBeVisible();
        });

        test('impor CSV dibatalkan bila ada baris salah, lalu berhasil setelah diperbaiki', async ({ page }) => {
            const header = 'nama,nip,pangkat_golongan,jabatan,email';
            const first = syntheticNip();
            const second = syntheticNip(uniqueSuffix());

            await page.goto('/employees/import');
            await page.getByLabel(/Berkas CSV/).setInputFiles({
                name: 'pegawai.csv',
                mimeType: 'text/csv',
                buffer: Buffer.from(`${header}\nE2E Impor A,${first},,,\nE2E Impor Salah,123,,,\n`),
            });
            page.once('dialog', (dialog) => dialog.accept());
            await page.getByRole('button', { name: 'Impor pegawai' }).click();

            await expect(page.getByRole('alert')).toContainText('Impor dibatalkan');
            await expect(page.getByRole('alert')).toContainText('Baris 3');

            await page.getByLabel(/Berkas CSV/).setInputFiles({
                name: 'pegawai.csv',
                mimeType: 'text/csv',
                buffer: Buffer.from(`${header}\nE2E Impor A,${first},,,\nE2E Impor B,${second},Penata - III/c,Analis,\n`),
            });
            page.once('dialog', (dialog) => dialog.accept());
            await page.getByRole('button', { name: 'Impor pegawai' }).click();

            await expect(page).toHaveURL(/\/employees$/);
            await expect(page.getByRole('status')).toContainText('2 pegawai diimpor');
        });
    });

    test('pegawai biasa tidak dapat membuka halaman pegawai', async ({ page }) => {
        await login(page, accounts.employee);
        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page.getByRole('link', { name: 'Pegawai' })).toHaveCount(0);

        const response = await page.goto('/employees');
        expect(response?.status()).toBe(403);
        await expect(page.getByRole('heading', { name: 'Akses ditolak' })).toBeVisible();
    });
});
