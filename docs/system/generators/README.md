# Generators for the reference docs

These read-only scripts produce `routes-inventory.md`, `database-schema.md` and `module-inventory.md`.
They only run `route:list`, PHP reflection and `SELECT`s against `information_schema`. They never write to the database.

```bash
OUT=$(mktemp -d)
php artisan route:list --json > $OUT/routes.json
php docs/system/generators/schema.php $OUT/schema.json          # central schema (live); tenant sample if a tenant DB exists
php docs/system/generators/models.php $OUT/models.json          # models, tables, traits, relations
php docs/system/generators/components.php $OUT/components.json  # Livewire / controllers / FormRequests
python3 docs/system/generators/parse_mig.py database/migrations/tenant $OUT/tenant_mig.json

python3 docs/system/generators/gen_routes.py  $OUT/routes.json > docs/system/routes-inventory.md
python3 docs/system/generators/gen_db2.py     $OUT/schema.json $OUT/models.json $OUT/tenant_mig.json > docs/system/database-schema.md
python3 docs/system/generators/gen_modules.py $OUT/routes.json $OUT/components.json > docs/system/module-inventory.md
```

Notes:
- `routes-inventory.md` lists only the `localhost` copy of central-domain routes (they are registered once per central domain).
- The tenant schema comes from migrations when no tenant database exists locally. Replace it with a live dump (`php artisan tenants:schema-dump`) when one is available.
