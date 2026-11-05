// Menentukan area UI (tag Playwright) yang terdampak oleh daftar file yang berubah.
// Fungsi murni tanpa dependensi agar mudah diuji (lihat affected.test.mjs).

/**
 * Glob sederhana: `**` = lintas folder, `*` = dalam satu segmen, `?` = satu karakter.
 * @param {string} glob
 * @returns {RegExp}
 */
export function globToRegExp(glob) {
    let pattern = '';

    for (let index = 0; index < glob.length; index++) {
        const char = glob[index];

        if (char === '*' && glob[index + 1] === '*') {
            pattern += '.*';
            index++;
            if (glob[index + 1] === '/') {
                index++;
            }
        } else if (char === '*') {
            pattern += '[^/]*';
        } else if (char === '?') {
            pattern += '[^/]';
        } else {
            pattern += char.replace(/[.+^${}()|[\]\\]/g, '\\$&');
        }
    }

    return new RegExp(`^${pattern}$`);
}

/**
 * @param {string[]} changedFiles
 * @param {{rules: {pattern: string, tags: string[]}[], fallbackAllPatterns: string[], specPattern: string}} map
 * @returns {{mode: 'all'|'tags'|'none', tags: string[], unmapped: string[]}}
 */
export function resolveTags(changedFiles, map) {
    const files = changedFiles.map((file) => file.trim()).filter(Boolean);

    if (files.includes('ALL')) {
        return { mode: 'all', tags: [], unmapped: [] };
    }

    const rules = map.rules.map((rule) => ({ ...rule, regex: globToRegExp(rule.pattern) }));
    const fallback = map.fallbackAllPatterns.map(globToRegExp);
    const specRegex = new RegExp(`^${map.specPattern.replace(/[.]/g, '\\.').replace('{name}', '([a-z0-9-]+)')}$`);

    const tags = new Set();
    const unmapped = [];

    for (const file of files) {
        const spec = file.match(specRegex);
        if (spec) {
            tags.add(`@${spec[1]}`);
            continue;
        }

        const matched = rules.filter((rule) => rule.regex.test(file));
        if (matched.length > 0) {
            matched.forEach((rule) => rule.tags.forEach((tag) => tags.add(tag)));
            continue;
        }

        // File kode yang belum dipetakan: jalankan semua uji UI agar aman.
        if (fallback.some((regex) => regex.test(file))) {
            unmapped.push(file);
            tags.add('*');
        }
    }

    if (tags.has('*')) {
        return { mode: 'all', tags: [], unmapped };
    }

    return tags.size === 0
        ? { mode: 'none', tags: [], unmapped }
        : { mode: 'tags', tags: [...tags].sort(), unmapped };
}
