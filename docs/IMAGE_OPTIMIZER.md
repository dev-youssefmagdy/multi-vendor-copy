# Image optimizer (AVIF)

All raster images stored by the app (central panel, tenant panel, storefront uploads, importers) are written as AVIF.

## Rules
- Use `App\Support\ImageOptimizer` for every image write:
  - `store($uploadedFile, $dir)` replaces `$file->store($dir, 'public')`.
  - `storeAs($file, $dir, $basename)` when sibling derivatives share a basename (product gallery + thumb).
  - `putBinary($bytes, $pathWithoutExt, $fallbackExt)` for downloads / generated images.
  - Derivatives built with Intervention use `->toAvif(ImageOptimizer::QUALITY)`.
- Passed through unchanged: SVG, ICO, animated GIF, PDFs/docs/video, and anything that fails to decode.
- Images are capped at 2560px on the longest side (`ImageOptimizer::MAX_DIMENSION`).
- `App\Models\File` normalises `extension` / `mime_type` / `size` for any `.avif` path on create.
- Validation `mimes:` lists accept `avif` uploads too.

## Converting existing data
`php artisan images:convert-avif [tenant] [--central-only|--tenants-only] [--dry-run] [--keep-originals]`

- Per database (central, then each tenant inside tenancy so the tenant `public` disk is used):
  1. `files` rows: file converted, `path`/`extension`/`mime_type`/`size` updated.
  2. Every other varchar/text/json column: image paths/URLs (`/storage/…`, `/tenancy/assets/…`, relative, JSON-escaped) are rewritten.
- Originals are deleted after the DB update unless `--keep-originals`. Always `--dry-run` first and back up storage.
- After the central pass run `php artisan tenants:sync-data --all` so synced catalog rows pick up the new paths.
- Not covered: static theme assets under `public/` and the seeders/default content that reference them.

## Orphan cleanup
`php artisan storage:clean-orphans [tenant] [--central-only|--tenants-only] [--min-age=48] [--exclude=dir] [--delete] [--purge]`

- Reports public-disk files that no database column references (central + each tenant). Report-only unless `--delete`.
- `--delete` moves them to `storage/app/orphan-trash/<timestamp>/<scope>/…` (restorable); `--purge` unlinks permanently.
- Skips files newer than `--min-age` hours, dotfiles, `livewire-tmp`, `orphan-trash`.
- Scheduled daily (bootstrap/app.php): 02:00 `images:convert-avif`, 03:00 `storage:clean-orphans --delete`. Empty `orphan-trash` periodically after verifying.
