#!/usr/bin/env bash
set -euo pipefail

# Disposable test services only. Credentials exist only in process/container environments.
name="fihex-test-${RANDOM}-$$"
cleanup() {
    docker rm -f "$name-app" "$name-db" "$name-missing" >/dev/null 2>&1 || true
    docker network rm "$name" >/dev/null 2>&1 || true
}
trap cleanup EXIT
export POSTGRES_USER=fihex_test POSTGRES_DB=fihex_test
POSTGRES_PASSWORD="$(openssl rand -hex 24)"
export POSTGRES_PASSWORD
DATABASE_URL="postgresql://${POSTGRES_USER}:${POSTGRES_PASSWORD}@${name}-db:5432/${POSTGRES_DB}"
export DATABASE_URL

docker build -t fihex:test .
docker network create "$name" >/dev/null
docker run -d --name "$name-db" --network "$name" \
    -e POSTGRES_USER -e POSTGRES_DB -e POSTGRES_PASSWORD postgres:17-bookworm >/dev/null
docker run -d --name "$name-app" --network "$name" \
    -e DATABASE_URL -p 127.0.0.1:8080:8080 fihex:test >/dev/null

wait_healthy() {
    for attempt in {1..60}; do
        state="$(docker inspect --format '{{.State.Status}} {{.State.Health.Status}}' "$name-app")"
        if [[ "$state" == 'running healthy' ]]; then return; fi
        if [[ "$state" == exited* ]]; then docker logs "$name-app"; return 1; fi
        sleep 2
    done
    docker logs "$name-app"
    return 1
}
wait_healthy
test "$(docker exec "$name-app" id -u)" != 0
test "$(docker exec "$name-app" sh -c 'test ! -e /var/www/html/public/debug_settings.txt && echo clean')" = clean
python3 tests/deployment_smoke.py --allow-writes
before="$(docker exec "$name-db" psql -U fihex_test -d fihex_test -Atc 'SELECT count(*) FROM migrations; SELECT count(*) FROM transactions; SELECT count(*) FROM print_products;')"
docker restart "$name-app" >/dev/null
wait_healthy
after="$(docker exec "$name-db" psql -U fihex_test -d fihex_test -Atc 'SELECT count(*) FROM migrations; SELECT count(*) FROM transactions; SELECT count(*) FROM print_products;')"
test "$before" = "$after"
curl --fail --silent --output /dev/null http://127.0.0.1:8080/

# A missing database must fail before Apache starts, with a nonzero exit status.
docker run -d --name "$name-missing" fihex:test >/dev/null
test "$(docker wait "$name-missing")" != 0
echo 'Container healthcheck, HTTP flows, persistence and fail-fast startup passed.'
