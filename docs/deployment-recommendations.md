# LaraTory - Deployment Recommendations

Fecha: 2026-07-21

## Entorno recomendado

- Docker Compose con servicios `app`, `db`, `webserver`.
- PHP 8.2 dentro del contenedor `app`.
- MySQL 8.0.
- Node 22.x para instalar y compilar assets.
- No usar `npm run dev` en produccion; usar `npm run build`.

## Comandos de despliegue local

```bash
cd /Users/willan/Documents/sistema-inventario-ventas
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
npm ci
npm run build
```

## Variables importantes

- `APP_ENV=production` en despliegue real.
- `APP_DEBUG=false` fuera de desarrollo.
- `APP_URL` debe apuntar al dominio o puerto real.
- `DB_HOST=db` dentro de Docker Compose.
- `DB_PORT=3306`.
- Configurar credenciales MySQL iguales entre `.env` y `docker-compose.yml`.

## Antes de publicar

| Estado | Verificacion | Resultado esperado |
|---|---|---|
| OK | `php artisan migrate:status` | Todas las migraciones `Ran`. |
| OK | `php artisan test` | Suite completa en verde. |
| OK | `npm ci` | Instalacion limpia. |
| OK | `npm run build` | Build client + SSR exitoso. |
| OK | `npm audit --omit=dev` | 0 vulnerabilidades. |
| WARNING | `composer audit` | Quedan advisories de Laravel 10 hasta upgrade mayor. |
| WARNING | `npm audit` | Queda Vite/esbuild dev-only hasta upgrade mayor de Vite. |

## Recomendacion de operacion

Mantener el proyecto en Docker para evitar diferencias entre macOS y Linux. Para produccion real, agregar proxy HTTPS, backups de MySQL, rotacion de logs, usuario no-root para runtime, almacenamiento persistente de `storage/app/public` y monitoreo de fallos de cola/log.

