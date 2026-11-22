#!/usr/bin/env bash
# Runs the PHPUnit suite in N parallel processes, each with its own database (dokan_v2_testing_w<i>), so tests that
# create/drop tenant databases never collide. Usage: scripts/test-parallel.sh [workers=8] [path-filter=tests/]
# Examples: scripts/test-parallel.sh 12 tests/Feature/Tenant     scripts/test-parallel.sh
set -u
cd "$(dirname "$0")/.."
N="${1:-8}"; ROOT="${2:-tests}"
OUT="storage/framework/testing-parallel"; rm -rf "$OUT"; mkdir -p "$OUT"
mapfile -t FILES < <(find "$ROOT" -name '*Test.php' | sort)
[ "${#FILES[@]}" -gt 0 ] || { echo "no tests under $ROOT"; exit 1; }
for ((i=0;i<N;i++)); do
  {
    echo '<?xml version="1.0" encoding="UTF-8"?>'
    echo "<phpunit bootstrap=\"$PWD/vendor/autoload.php\" colors=\"false\"><testsuites><testsuite name=\"w\">"
    for ((j=i;j<${#FILES[@]};j+=N)); do echo "<file>$PWD/${FILES[$j]}</file>"; done
    echo '</testsuite></testsuites></phpunit>'
  } > "$OUT/w$i.xml"
  # inherit the env block (APP_ENV, DB creds, array cache, sync queue...) from phpunit.xml via the shell instead
done
export APP_ENV=testing APP_MAINTENANCE_DRIVER=file BCRYPT_ROUNDS=4 BROADCAST_CONNECTION=null CACHE_STORE=array \
  DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD=root DB_URL= MAIL_MAILER=array \
  QUEUE_CONNECTION=sync SESSION_DRIVER=array PULSE_ENABLED=false TELESCOPE_ENABLED=false NIGHTWATCH_ENABLED=false
pids=()
for ((i=0;i<N;i++)); do
  mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS dokan_v2_testing_w$i" 2>/dev/null
  DB_DATABASE="dokan_v2_testing_w$i" php vendor/bin/phpunit -c "$OUT/w$i.xml" > "$OUT/w$i.log" 2>&1 &
  pids+=($!)
done
fail=0; for p in "${pids[@]}"; do wait "$p" || fail=1; done
for ((i=0;i<N;i++)); do echo "== w$i: $(sed 's/\x1b\[[0-9;]*m//g' "$OUT/w$i.log" | grep -a 'Tests:\|^OK' | tail -1)"; done
grep -ahE '^[0-9]+\) ' "$OUT"/w*.log | head -60
echo "logs: $OUT/w*.log"; exit $fail
