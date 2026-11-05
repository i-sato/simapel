import { expect, test as setup } from '@playwright/test';
import { accounts, adminStorageState, login } from '../support/accounts';

// Satu kali login untuk dipakai bersama oleh spec yang membutuhkan sesi admin,
// agar tidak menabrak pembatasan login per IP. Tag @setup selalu ikut dijalankan.
setup('masuk sebagai Super Admin E2E', { tag: '@setup' }, async ({ page }) => {
    await login(page, accounts.admin);
    await expect(page).toHaveURL(/\/dashboard$/);
    await page.context().storageState({ path: adminStorageState });
});
