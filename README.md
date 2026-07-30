# LaraTory

LaraTory es un sistema de gestion presencial para minimarket, tienda o punto de venta. Esta construido con Laravel 10, Vue 3, Inertia, Vite, Tailwind CSS, MySQL y Docker Compose.

El proyecto esta enfocado en venta fisica: administrador, cajero, punto de venta, caja, inventario, reportes, tickets, boletas internas y facturas internas. No incluye comercio electronico ni flujo de clientes obligatorio.

## Funcionalidades

- Punto de venta con busqueda, lector de codigo de barras por teclado/app movil y pagos mixtos.
- Metodos de pago: efectivo, Yape, Plin, tarjeta y transferencia.
- Documentos internos: boleta y factura.
- Caja automatica por empleado con apertura, cierre, movimientos y arqueo.
- Panel de control por rol.
- Productos, categorias, unidades, proveedores, stock, costo historico y utilidad.
- Reportes visuales, exportacion PDF/Excel y documentos de venta.
- Tour guiado, atajos de teclado e interfaz responsive.
- 200 productos demo con imagenes locales optimizadas para evitar lentitud por URLs externas.
- Roles operativos: administrador y cajero.

## Stack

- PHP 8.2
- Laravel 10
- MySQL 8.0
- Vue 3
- Inertia.js
- Vite 5
- Tailwind CSS
- Docker Compose
- Node.js 22.x recomendado

## Requisitos

macOS:

- Docker Desktop para Mac
- Git
- Node.js 22.x
- NPM

Windows:

- Docker Desktop para Windows con WSL 2 habilitado
- Git for Windows
- Node.js 22.x
- NPM
- PowerShell o Windows Terminal

## Puertos

| Servicio | Host | Contenedor |
|---|---:|---:|
| Nginx | 8080 | 80 |
| MySQL | 3306 | 3306 |
| PHP-FPM | interno | 9000 |
| Vite | 5173 | 5173 |

Si `3306` esta ocupado, cambia solo el puerto externo en `docker-compose.yml`:

```yaml
ports:
  - "3307:3306"
```

Dentro de Docker, `DB_HOST=db` y `DB_PORT=3306` deben mantenerse.

## Instalacion En macOS

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

## Instalacion En Windows

Ejecuta en PowerShell:

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

Desde PowerShell tambien puedes usar:

```powershell
Start-Process http://localhost:8080
```

## Credenciales Demo

En el login escribe solo el alias. LaraTory agrega `@laratory.pe` automaticamente.

| Rol | Alias de login | Correo completo | Contrasena |
|---|---|---|---|
| Administrador | `willan.a` | `willan.a@laratory.pe` | `password` |
| Cajera | `maria.c` | `maria.c@laratory.pe` | `password` |
| Cajero | `luis.c` | `luis.c@laratory.pe` | `password` |

## Variables Importantes

`.env.example` esta preparado para Docker:

```env
APP_URL=http://localhost:8080
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=inventario2
DB_USERNAME=root
DB_PASSWORD=root
SESSION_DRIVER=file
SESSION_COOKIE=laratory_file_session
```

El servicio MySQL usa:

```yaml
MYSQL_DATABASE: inventario2
MYSQL_ROOT_PASSWORD: root
```

## Datos Demo E Imagenes

El seeder principal crea usuarios, categorias, unidades, proveedores, 200 productos y ventas demo:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Las imagenes demo estan en:

```text
storage/app/public/products
```

El sistema guarda en base de datos solo el nombre del archivo. Laravel las sirve mediante:

```text
http://localhost:8080/storage/products/nombre.jpg
```

Si necesitas descargar/reemplazar imagenes reales desde fuentes externas y dejarlas locales:

```bash
docker compose exec app php artisan products:download-images
```

Opciones utiles:

```bash
docker compose exec app php artisan products:download-images --force
docker compose exec app php artisan products:download-images --limit=20
docker compose exec app php artisan products:download-images --dry-run
```

## Comandos Utiles

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

Ejecutar migraciones pendientes:

```bash
docker compose exec app php artisan migrate
```

Recrear base demo:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Optimizar Laravel:

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Limpiar cache:

```bash
docker compose exec app php artisan optimize:clear
```

Compilar frontend:

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

## Desarrollo

1. Levanta Docker con `docker compose up -d`.
2. Ejecuta `npm run dev` en tu maquina host.
3. Trabaja desde `http://localhost:8080`.
4. Usa el POS con scanner por teclado o una app movil que envie el codigo al input activo.
5. Antes de subir cambios, ejecuta:

```bash
npm run build
docker compose exec app php artisan test
```

Los tests estan aislados para usar SQLite en memoria y no borrar la base MySQL local.

## Despliegue Tipo Produccion

Para preparar una instancia nueva:

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

En produccion real cambia:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
DB_PASSWORD=una_contrasena_segura
```

Tambien configura HTTPS, backups de MySQL, rotacion de logs y credenciales seguras.

## Problemas Comunes

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

Login no funciona:

```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan db:seed --class=LaraToryDemoSeeder
docker compose exec app php artisan config:cache
```

Luego entra con `willan.a` / `password`.

Error con extension PHP `ext-zip`:

```bash
docker compose build --no-cache
docker compose exec app composer install
```

El Dockerfile instala `zip`, `gd`, `pdo_mysql` y OPcache.

Pantalla sin estilos:

```bash
npm ci
npm run dev
```

Para produccion:

```bash
npm run build
```

## Validacion Local

Validacion recomendada:

```bash
npm run build
docker compose exec app php artisan test tests/Feature/Auth/AuthenticationTest.php
docker compose exec app php artisan test tests/Feature/PointOfSaleTest.php --filter="the scanner adds a product by its exact barcode|pos and navigation include keyboard and accessibility affordances"
```

Estado actual verificado:

- Login real redirige a `/sistema/dashboard`.
- Sesiones configuradas con `SESSION_DRIVER=file`.
- Seed demo crea 3 usuarios y 200 productos.
- Productos sin imagen externa: `0`.
- Productos con archivo faltante: `0`.
