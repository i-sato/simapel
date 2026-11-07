import { expect, test } from '@playwright/test';
import { accounts, adminStorageState, maskNip } from '../support/accounts';

test.describe('Dashboard', { tag: '@dashboard' }, () => {
    test.use({ storageState: adminStorageState });

    test('ringkasan akun hanya menampilkan NIP tersamar', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page.getByRole('heading', { name: 'Akun Anda' })).toBeVisible();
        await expect(page.getByText(maskNip(accounts.admin))).toBeVisible();
        await expect(page.getByText('Super Admin', { exact: true }).first()).toBeVisible();
        expect(await page.content()).not.toContain(accounts.admin);
    });
});
