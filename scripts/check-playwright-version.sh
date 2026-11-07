#!/bin/sh
# Memastikan versi image Docker Playwright (compose.yaml) sama dengan @playwright/test
# (e2e/package.json). Bila berbeda, browser di image tidak cocok dan semua uji UI gagal.
set -eu

root="$(cd "$(dirname "$0")/.." && pwd)"
image="$(sed -n 's#.*mcr.microsoft.com/playwright:v\([0-9.]*\)-.*#\1#p' "$root/compose.yaml" | head -n 1)"
package="$(sed -n 's#.*"@playwright/test": *"\([0-9.]*\)".*#\1#p' "$root/e2e/package.json" | head -n 1)"

if [ -z "$image" ] || [ -z "$package" ]; then
    echo "Versi Playwright tidak ditemukan (image: '${image}', paket: '${package}')." >&2
    exit 1
fi

if [ "$image" != "$package" ]; then
    echo "Versi Playwright tidak sama: image Docker v${image}, @playwright/test ${package}." >&2
    echo "Perbarui keduanya bersamaan (compose.yaml dan e2e/package.json + package-lock.json)." >&2
    exit 1
fi

echo "Versi Playwright sama: ${image}"
