# LaraTory

LaraTory es un sistema de gestion para minimarket, tienda o punto de venta presencial. Esta construido con Laravel 10, Vue 3, Inertia, Vite, Tailwind CSS, MySQL y Docker Compose.

El flujo actual esta enfocado en operacion presencial: administrador, cajero, punto de venta, caja, productos, inventario, reportes, tickets, boletas internas y facturas internas.

## Caracteristicas principales

- Punto de venta con busqueda, lectura por codigo de barras y pagos mixtos.
- Metodos de pago: efectivo, Yape, Plin, tarjeta y transferencia.
- Emision interna de boleta y factura.
- Control de caja por empleado, movimientos, apertura, cierre y arqueo.
- Inventario con productos, categorias, unidades, proveedores, stock y costos.
- Dashboard por rol.
- Reportes con PDF y Excel.
- Tickets termicos y documentos PDF.
- Roles principales: administrador y cajero.

## Stack tecnico

- PHP 8.2
- Laravel 10
- MySQL 8.0
- Vue 3
- Inertia.js
- Vite 5
- Tailwind CSS
- Docker Compose
- Node.js 22.x

## Requisitos

Para macOS:

- Docker Desktop para Mac
- Git
- Node.js 22.x
- NPM

Para Windows:

- Docker Desktop para Windows con WSL 2 habilitado
- Git for Windows
- Node.js 22.x
- NPM
- PowerShell o Windows Terminal

## Puertos usados

| Servicio | Puerto host | Puerto contenedor |
|---|---:|---:|
| Nginx | 8080 | 80 |
| MySQL | 3306 | 3306 |
| PHP-FPM | interno | 9000 |
| Vite dev server | 5173 | local |

Si el puerto `3306` ya esta ocupado, cambia el puerto externo de MySQL en `docker-compose.yml`:

```yaml
ports:
  - "3307:3306"
```

Dentro de Docker, `DB_PORT` debe seguir siendo `3306`.

## Instalacion en macOS

```bash
git clone https://github.com/willian-d29/sistema-inventario-ventas.git
cd sistema-inventario-ventas

cp .env.example .env

docker compose build
docker compose up -d

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link

npm ci
npm run dev
```

Abre:

```text
http://localhost:8080
```

## Instalacion en Windows

Ejecuta estos comandos en PowerShell:

```powershell
git clone https://github.com/willian-d29/sistema-inventario-ventas.git
cd sistema-inventario-ventas

Copy-Item .env.example .env

docker compose build
docker compose up -d

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link

npm ci
npm run dev
```

Abre:

```text
http://localhost:8080
```

Tambien puedes abrirlo desde PowerShell:

```powershell
Start-Process http://localhost:8080
```

## Credenciales iniciales

Las credenciales creadas por los seeders son:

| Rol | Correo | Contrasena |
|---|---|---|
| Administrador | admin@admin.com | password |
| Cajero | cajero@empresa.com | password |

## Variables importantes de Docker

El archivo `.env.example` esta preparado para Docker Compose:

```env
APP_URL=http://localhost:8080
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventario2
DB_USERNAME=root
DB_PASSWORD=root
```

El servicio MySQL de `docker-compose.yml` usa:

```yaml
MYSQL_DATABASE: inventario2
MYSQL_ROOT_PASSWORD: root
```

## Comandos utiles

Ver contenedores:

```bash
docker compose ps
```

Ver logs:

```bash
docker compose logs -f
```

Entrar al contenedor Laravel:

```bash
docker compose exec app bash
```

Ejecutar migraciones:

```bash
docker compose exec app php artisan migrate
```

Recrear base de datos con datos demo:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Compilar frontend para produccion:

```bash
npm run build
```

Detener contenedores:

```bash
docker compose down
```

Detener y borrar volumen de base de datos:

```bash
docker compose down -v
```

## Flujo de desarrollo recomendado

1. Levanta Laravel, Nginx y MySQL con Docker Compose.
2. Ejecuta `npm run dev` en tu maquina host para Vite.
3. Trabaja desde `http://localhost:8080`.
4. Antes de subir cambios, ejecuta:

```bash
npm run build
docker compose exec app php artisan test
```

## Despliegue tipo produccion

Para un entorno que no sea desarrollo:

```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
npm ci
npm run build
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

En produccion real ajusta:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
```

Tambien debes configurar HTTPS, backups de MySQL, rotacion de logs y credenciales seguras.

## Problemas comunes

Error `no configuration file provided`:

```bash
cd sistema-inventario-ventas
docker compose ps
```

Error `permission denied` al ejecutar la ruta del proyecto:

```bash
cd /ruta/al/proyecto
docker compose build
```

No ejecutes la carpeta como si fuera un comando.

Error con extension PHP `ext-zip`:

```bash
docker compose build --no-cache
docker compose exec app composer install
```

El Dockerfile instala `zip` y `pdo_mysql`.

Error de MySQL por puerto ocupado:

1. Cambia `3306:3306` por `3307:3306` en `docker-compose.yml`.
2. Ejecuta:

```bash
docker compose down
docker compose up -d
```

Pantalla sin estilos o assets:

```bash
npm ci
npm run dev
```

Para produccion:

```bash
npm run build
```

## Validacion actual

Ultima validacion local:

```bash
npm run build
docker compose exec app php artisan test tests/Feature/PointOfSaleTest.php tests/Feature/FunctionalSimplificationTest.php tests/Feature/UiComponentTanda2Test.php
```

Resultado:

- Build de Vite correcto.
- 99 pruebas pasadas.
- 644 assertions.
