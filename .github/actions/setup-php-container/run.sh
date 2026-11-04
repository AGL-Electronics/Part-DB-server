#!/bin/bash
# Runs php, composer or symfony inside the CI PHP image.
# The runner shares /tmp/runner with the Docker host at the same path, and the
# container joins the runner's network so database addresses resolve the same way.
set -euo pipefail

tool="$(basename "$0")"
self="$(cat /etc/hostname)"

args=(--rm -i --network "container:${self}" -v /tmp/runner:/tmp/runner -w "$PWD")
while IFS= read -r name; do
    case "$name" in
        APP_*|SYMFONY_*|COMPOSER_*|DATABASE_URL|CHECK_FOR_UPDATES|DB_TYPE|MYSQL_*|CI|GITHUB_*|RUNNER_*)
            args+=(-e "$name")
            ;;
    esac
done < <(compgen -e)

exec docker run "${args[@]}" "${PARTDB_CI_PHP_IMAGE:?}" "$tool" "$@"
