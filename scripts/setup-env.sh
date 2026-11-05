#!/bin/sh
# Membuat .env dari .env.example dengan kata sandi database acak.
# Tidak pernah menimpa .env yang sudah ada.
set -eu

cd "$(dirname "$0")/.."

if [ -f .env ]; then
    echo ".env sudah ada; tidak diubah."
    exit 0
fi

cp .env.example .env

random_secret() {
    LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32
}

set_env() {
    tmp_file=$(mktemp)
    awk -v key="$1" -v value="$2" 'BEGIN { FS = OFS = "=" } $1 == key { $0 = key "=" value } { print }' .env >"$tmp_file"
    mv "$tmp_file" .env
}

set_env DB_PASSWORD "$(random_secret)"
set_env DB_ROOT_PASSWORD "$(random_secret)"
set_env HOST_UID "$(id -u)"
set_env HOST_GID "$(id -g)"

chmod 600 .env
echo ".env dibuat dengan kata sandi database acak."
