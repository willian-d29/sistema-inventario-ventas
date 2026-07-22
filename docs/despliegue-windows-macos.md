# Despliegue de LaraTory en Windows y macOS

Esta guia usa Docker Compose para Laravel, Nginx y MySQL. Node/NPM se ejecutan en la maquina host para servir o compilar assets con Vite.

## Requisitos

macOS:

- Docker Desktop
- Git
- Node.js 22.x
- NPM

Windows:

- Docker Desktop con WSL 2
- Git for Windows
- Node.js 22.x
- NPM
- PowerShell

## Variables esperadas

`.env` debe quedar asi para Docker:

```env
APP_URL=http://localhost:8080
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventario2
DB_USERNAME=root
DB_PASSWORD=root
```

`DB_HOST=db` es obligatorio porque Laravel se conecta al servicio MySQL por el nombre del contenedor dentro de la red de Docker Compose.

## macOS

```bash
git clone https://github.com/willian-d29/sistema-inventario-ventas.git
cd sistema-inventario-ventas
cp .env.example .env

docker compose build
docker compose up -d
docker compose ps

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link

npm ci
npm run dev
```

URL:

```text
http://localhost:8080
```

## Windows PowerShell

```powershell
git clone https://github.com/willian-d29/sistema-inventario-ventas.git
cd sistema-inventario-ventas
Copy-Item .env.example .env

docker compose build
docker compose up -d
docker compose ps

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link

npm ci
npm run dev
```

URL:

```text
http://localhost:8080
```

## Credenciales demo

| Rol | Correo | Contrasena |
|---|---|---|
| Administrador | admin@admin.com | password |
| Cajero | cajero@empresa.com | password |

## Produccion o entrega final

Para compilar assets en vez de usar Vite dev server:

```bash
npm ci
npm run build
```

En el servidor:

```bash
docker compose build
docker compose up -d
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Usa estos valores en produccion real:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
```

## Puertos en conflicto

Si MySQL local ya usa `3306`, cambia solo el puerto externo:

```yaml
db:
  ports:
    - "3307:3306"
```

No cambies `DB_PORT=3306`, porque ese puerto es el interno dentro de Docker.

Si Nginx ya usa `8080`, cambia:

```yaml
webserver:
  ports:
    - "8081:80"
```

Y actualiza:

```env
APP_URL=http://localhost:8081
```

## Comandos de diagnostico

Ver estado:

```bash
docker compose ps
```

Ver logs:

```bash
docker compose logs -f
```

Probar Laravel:

```bash
docker compose exec app php artisan about
docker compose exec app php artisan migrate:status
```

Ejecutar pruebas:

```bash
docker compose exec app php artisan test
```

Reiniciar contenedores:

```bash
docker compose down
docker compose up -d
```

Recrear todo, incluyendo base de datos:

```bash
docker compose down -v
docker compose up -d
docker compose exec app php artisan migrate:fresh --seed
```

