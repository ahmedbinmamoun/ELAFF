# Runbook — Deploy (draft, completed in Phase 7)

Target: one VPS (≥ 2 GB RAM, 4 GB recommended) with Docker Engine + Compose plugin.

1. Create `/srv/elaf/.env` from `.env.example`: `APP_ENV=production`, `APP_DEBUG=false`,
   a new `APP_KEY` (`docker run --rm ghcr.io/<org>/<repo> php artisan key:generate --show`),
   strong DB passwords, real `APP_URL`, `SESSION_SECURE_COOKIE=true`, SMTP settings.
2. Copy `docker-compose.prod.yml` to `/srv/elaf/`.
3. Set `ELAF_IMAGE`, `ELAF_WEB_IMAGE`, `ELAF_TAG` in that `.env` (images built by CI on `main`).
4. `cd /srv/elaf && docker compose -f docker-compose.prod.yml --env-file .env up -d`
   (the `app` container runs migrations on start; `RUN_MIGRATIONS=true`).
5. Check `curl -fsS http://127.0.0.1/up`.

TLS: either Cloudflare in front (SSL mode Full (strict) with an origin certificate on
the host proxy), or a Caddy/Traefik container with Let's Encrypt. Details in Phase 7.

Back up the named volumes `mysql_data` and `storage_data` (Spatie Backup, Phase 7).
