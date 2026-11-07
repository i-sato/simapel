import { expect, test, type Page } from '@playwright/test';
import { accounts, adminStorageState, e2ePassword, login, syntheticNip, uniqueSuffix } from '../support/accounts';

function day(offset: number): string {
    const date = new Date();
    date.setDate(date.getDate() + offset);
    return date.toISOString().slice(0, 10);
}

async function createDraft(page: Page, name: string, team: string): Promise<void> {
    await page.goto('/trainings/create');
    await page.getByLabel('Nama/tema pelatihan').fill(name);
    const value = await page.locator('#work_team_id option', { hasText: `(${team})` }).first().getAttribute('value');
    await page.selectOption('#work_team_id', value ?? '');
    // Rentang beban kerja sama (awal pendaftaran–akhir pelatihan) agar penugasan kedua bentrok.
    await page.getByLabel(/^Awal pendaftaran/).fill(day(60));
    await page.getByLabel(/^Akhir pendaftaran/).fill(day(65));
    await page.getByLabel(/^Awal pelatihan/).fill(day(70));
    await page.getByLabel(/^Akhir pelatihan/).fill(day(72));
    await page.getByRole('button', { name: 'Simpan draft' }).click();
    await expect(page.getByRole('heading', { name, level: 1 })).toBeVisible();
}

test.describe('Penugasan', { tag: '@penugasan' }, () => {
    test.describe('dengan sesi Super Admin', () => {
        test.use({ storageState: adminStorageState });

        test('menugaskan Admin, lalu Panitia yang bentrok wajib disertai alasan', async ({ page }) => {
            const suffix = uniqueSuffix();
            const employee = `E2E Penugas ${suffix}`;

            await page.goto('/employees/create');
            await page.getByLabel('Nama', { exact: true }).fill(employee);
            await page.getByLabel('NIP', { exact: true }).fill(syntheticNip(suffix));
            await page.getByLabel('Dapat ditugaskan sebagai Admin pelatihan').check();
            await page.getByLabel('Kata sandi awal', { exact: true }).fill(e2ePassword());
            await page.getByLabel('Ulangi kata sandi awal').fill(e2ePassword());
            await page.getByRole('button', { name: 'Tambah pegawai' }).click();
            await expect(page).toHaveURL(/\/employees$/);

            await createDraft(page, `E2E Pelatihan DTA ${suffix}`, 'DTA');
            await page.getByRole('link', { name: 'Atur penugasan' }).click();
            await page.getByLabel(`Admin: ${employee}`).check();
            await page.getByRole('button', { name: 'Simpan penugasan' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Penugasan disimpan: 1 ditambahkan' })).toBeVisible();
            await expect(page.getByRole('row', { name: new RegExp(employee) })).toContainText('Admin');

            await createDraft(page, `E2E Pelatihan KDD ${suffix}`, 'KDD');
            await page.getByRole('link', { name: 'Atur penugasan' }).click();
            const row = page.getByRole('row', { name: new RegExp(employee) });
            await expect(row.locator('.badge.warning')).toContainText('Admin DTA ·');
            await expect(page.getByRole('heading', { name: 'Ada pegawai dengan penugasan yang beririsan' })).toBeVisible();

            await page.getByLabel(`Panitia: ${employee}`).check();
            await page.getByRole('button', { name: 'Simpan penugasan' }).click();
            await expect(page.getByText(/Penugasan bentrok dengan pelatihan lain/).first()).toBeVisible();

            await page.getByLabel(`Panitia: ${employee}`).check();
            await page.getByLabel('Alasan tetap menugaskan meskipun bentrok').fill('Satu-satunya fasilitator materi ini');
            await page.getByRole('button', { name: 'Simpan penugasan' }).click();
            await expect(page.getByRole('status').filter({ hasText: '1 dengan alasan bentrok' })).toBeVisible();
            await expect(page.getByText('Alasan bentrok: Satu-satunya fasilitator materi ini')).toBeVisible();
        });
    });

    test('pegawai biasa tidak dapat mengatur penugasan', async ({ page }) => {
        await login(page, accounts.employee);
        await page.getByRole('link', { name: 'Pelatihan' }).click();
        const first = page.locator('table a[href*="/trainings/"]').first();

        if ((await first.count()) === 0) {
            test.skip(true, 'Belum ada pelatihan pada database ini.');
        }

        await first.click();
        await expect(page.getByRole('link', { name: 'Atur penugasan' })).toHaveCount(0);
        const response = await page.goto(`${page.url()}/assignments`);
        expect(response?.status()).toBe(403);
    });
});
