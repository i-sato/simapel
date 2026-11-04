import { expect, type Page } from '@playwright/test';

/**
 * Akun sintetis dari E2eSeeder. Kata sandi dibuat acak per eksekusi (`make e2e`)
 * dan diteruskan lewat variabel lingkungan; tidak pernah ditulis ke file.
 */
const baseNip = process.env.E2E_NIP ?? '999900000000000001';

export const accounts = {
    admin: baseNip,
    inactive: `${baseNip.slice(0, 17)}2`,
    mustChangePassword: `${baseNip.slice(0, 17)}3`,
} as const;

export function e2ePassword(): string {
    const password = process.env.E2E_PASSWORD;

    if (!password) {
        throw new Error('E2E_PASSWORD belum diatur. Jalankan uji lewat `make e2e` atau `make e2e-affected`.');
    }

    return password;
}

export function maskNip(nip: string): string {
    return `${nip.slice(0, 4)}${'*'.repeat(10)}${nip.slice(-4)}`;
}

export const adminStorageState = '.auth/admin.json';

export async function login(page: Page, nip: string, password: string = e2ePassword()): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('NIP', { exact: true }).fill(nip);
    await page.getByLabel('Kata sandi', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Masuk' }).click();
}

export async function expectGenericLoginError(page: Page): Promise<void> {
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('alert')).toHaveText('NIP atau kata sandi salah, atau akun tidak aktif.');
}
