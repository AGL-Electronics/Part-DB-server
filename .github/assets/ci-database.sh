#!/bin/bash
# Start or stop a throwaway database for PHPUnit on a Docker-backed runner.
# Usage: ci-database.sh start <mysql|postgres> <container-name>
#        ci-database.sh stop <container-name>
set -eu

cmd="${1:?start or stop}"

gateway() {
    if command -v ip >/dev/null 2>&1; then
        ip -4 route show default | awk '{print $3; exit}'
        return
    fi
    hex=$(awk '$2 == "00000000" { print $3; exit }' /proc/net/route)
    printf '%d.%d.%d.%d\n' "$((0x${hex:6:2}))" "$((0x${hex:4:2}))" "$((0x${hex:2:2}))" "$((0x${hex:0:2}))"
}

wait_ready() {
    name="$1"
    shift
    for _ in $(seq 1 60); do
        if docker exec "$name" "$@" >/dev/null 2>&1; then
            return 0
        fi
        sleep 2
    done
    echo "Database container $name did not become ready" >&2
    docker logs "$name" >&2 || true
    return 1
}

if [ "$cmd" = "stop" ]; then
    docker rm -f "${2:?container name}" >/dev/null 2>&1 || true
    exit 0
fi

if [ "$cmd" != "start" ]; then
    echo "Unknown command: $cmd" >&2
    exit 1
fi

kind="${2:?mysql or postgres}"
name="${3:?container name}"
host="$(gateway)"
docker rm -f "$name" >/dev/null 2>&1 || true

case "$kind" in
    mysql)
        docker run -d --name "$name" \
            -e MYSQL_ROOT_PASSWORD=root \
            -e MYSQL_DATABASE=partdb \
            -p 3306 \
            mysql:8.0.35
        wait_ready "$name" mysqladmin ping -uroot -proot --silent
        port="$(docker port "$name" 3306/tcp | awk -F: 'NR == 1 { print $NF }')"
        url="mysql://root:root@${host}:${port}/partdb?serverVersion=8.0.35"
        ;;
    postgres)
        docker run -d --name "$name" \
            -e POSTGRES_PASSWORD=postgres \
            -e POSTGRES_DB=partdb \
            -p 5432 \
            postgres:14
        wait_ready "$name" pg_isready -U postgres
        port="$(docker port "$name" 5432/tcp | awk -F: 'NR == 1 { print $NF }')"
        url="postgresql://postgres:postgres@${host}:${port}/partdb?serverVersion=14&charset=utf8"
        ;;
    *)
        echo "Unknown database: $kind" >&2
        exit 1
        ;;
esac

if [ -z "${host}" ] || [ -z "${port}" ]; then
    echo "Could not find the published database address" >&2
    exit 1
fi

{
    echo "DATABASE_URL=${url}"
    echo "MYSQL_HOST=${host}"
    echo "MYSQL_PORT=${port}"
    echo "MYSQL_CONTAINER=${name}"
} >> "$GITHUB_ENV"
