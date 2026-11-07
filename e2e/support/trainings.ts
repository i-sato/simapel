import { expect, type Page } from '@playwright/test';

/** Tanggal relatif terhadap hari ini (YYYY-MM-DD). */
export function day(offset: number): string {
    const date = new Date();
    date.setDate(date.getDate() + offset);
    return date.toISOString().slice(0, 10);
}

/**
 * Membuat pelatihan lengkap (siap difinalisasi) lewat form, lalu berhenti di halaman detail.
 * Jadwal bawaan: pendaftaran H+30–H+40, pelatihan H+45–H+47.
 */
export async function createCompleteTraining(page: Page, name: string, team = 'DTA', offsets = [30, 40, 45, 47]): Promise<void> {
    await page.goto('/trainings/create');
    await page.getByLabel('Nama/tema pelatihan').fill(name);
    const value = await page.locator('#work_team_id option', { hasText: `(${team})` }).first().getAttribute('value');
    await page.selectOption('#work_team_id', value ?? '');
    await page.getByLabel(/^Batch/).fill('1');
    await page.getByLabel(/^Mitra\/lokasi/).fill('Mitra E2E, Yogyakarta');
    await page.getByLabel(/^Metode/).selectOption('daring');
    await page.getByLabel(/^Awal pendaftaran/).fill(day(offsets[0]));
    await page.getByLabel(/^Akhir pendaftaran/).fill(day(offsets[1]));
    await page.getByLabel(/^Awal pelatihan/).fill(day(offsets[2]));
    await page.getByLabel(/^Akhir pelatihan/).fill(day(offsets[3]));
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
    await page.getByRole('button', { name: 'Simpan draft' }).click();
    await expect(page.getByRole('heading', { name, level: 1 })).toBeVisible();
}
