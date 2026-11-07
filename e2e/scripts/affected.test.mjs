import { strict as assert } from 'node:assert';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { globToRegExp, resolveTags } from './affected.mjs';

const map = JSON.parse(readFileSync(new URL('../impact-map.json', import.meta.url), 'utf8'));

test('glob ** melintasi folder, * hanya satu segmen', () => {
    assert.ok(globToRegExp('app/Http/Middleware/**').test('app/Http/Middleware/SecurityHeaders.php'));
    assert.ok(globToRegExp('e2e/package*.json').test('e2e/package-lock.json'));
    assert.ok(!globToRegExp('e2e/tests/*.setup.ts').test('e2e/tests/nested/auth.setup.ts'));
});

test('perubahan dokumentasi dan tes PHP tidak memicu uji UI', () => {
    const result = resolveTags(['docs/arsitektur.md', 'README.md', 'tests/Unit/Domain/Identity/NipTest.php', '.github/workflows/ci.yml'], map);
    assert.equal(result.mode, 'none');
});

test('view halaman masuk hanya memicu area @auth', () => {
    assert.deepEqual(resolveTags(['resources/views/auth/login.blade.php'], map), { mode: 'tags', tags: ['@auth'], unmapped: [] });
});

test('beberapa perubahan menggabungkan tag tanpa duplikat', () => {
    const result = resolveTags(['app/Http/Controllers/Account/PasswordController.php', 'app/Application/Identity/ChangeOwnPassword.php'], map);
    assert.deepEqual(result.tags, ['@account', '@auth', '@pegawai']);
});

test('layout, CSS, middleware, dan migrasi memicu semua uji', () => {
    for (const file of ['resources/views/layouts/app.blade.php', 'public/css/simapel.css', 'app/Http/Middleware/SecurityHeaders.php', 'database/migrations/2026_11_04_000001_create_audit_logs_table.php']) {
        assert.equal(resolveTags([file], map).mode, 'all', file);
    }
});

test('perubahan spec hanya menjalankan spec tersebut', () => {
    assert.deepEqual(resolveTags(['e2e/tests/dashboard.spec.ts'], map).tags, ['@dashboard']);
});

test('kode aplikasi yang belum dipetakan memicu semua uji dan dilaporkan', () => {
    const result = resolveTags(['app/Http/Controllers/TrainingController.php'], map);
    assert.equal(result.mode, 'all');
    assert.deepEqual(result.unmapped, ['app/Http/Controllers/TrainingController.php']);
});

test('penanda ALL menjalankan semua uji', () => {
    assert.equal(resolveTags(['ALL'], map).mode, 'all');
});

test('file yang dipetakan dengan tag kosong tidak memicu uji UI', () => {
    assert.deepEqual(resolveTags(['app/Console/Commands/CreateUserCommand.php', 'database/factories/UserFactory.php'], map), { mode: 'none', tags: [], unmapped: [] });
});

test('halaman pegawai dan tim kerja memicu area masing-masing', () => {
    assert.deepEqual(resolveTags(['resources/views/employees/index.blade.php'], map).tags, ['@pegawai']);
    assert.deepEqual(resolveTags(['app/Domain/Organization/TeamComposition.php'], map).tags, ['@tim-kerja']);
});
