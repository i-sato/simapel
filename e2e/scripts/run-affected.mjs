// Menjalankan Playwright hanya untuk area UI yang terdampak.
// Pemakaian: node scripts/run-affected.mjs <file-daftar-perubahan>
// Isi file: satu path per baris (relatif dari root repo), atau "ALL" untuk semua uji.

import { existsSync, readFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { resolveTags } from './affected.mjs';

const listPath = process.argv[2] ?? '.changed-files';
const changedFiles = existsSync(listPath) ? readFileSync(listPath, 'utf8').split('\n') : ['ALL'];
const map = JSON.parse(readFileSync(new URL('../impact-map.json', import.meta.url), 'utf8'));
const result = resolveTags(changedFiles, map);

if (result.unmapped.length > 0) {
    console.log(`File belum dipetakan di impact-map.json (semua uji UI dijalankan):\n  ${result.unmapped.join('\n  ')}`);
}

if (result.mode === 'none') {
    console.log('Tidak ada UI terdampak; uji Playwright dilewati.');
    process.exit(0);
}

const args = ['playwright', 'test'];

if (result.mode === 'tags') {
    const pattern = [...map.alwaysRunTags, ...result.tags].join('|');
    console.log(`Area UI terdampak: ${result.tags.join(', ')}`);
    args.push('--grep', pattern);
} else {
    console.log('Menjalankan semua uji UI.');
}

const run = spawnSync('npx', args, { stdio: 'inherit' });
process.exit(run.status ?? 1);
