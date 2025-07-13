#!/bin/sh
set -e

# Colores para mensajes
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Crear carpetas necesarias para supervisor
mkdir -p /var/run /var/log/supervisor

echo "Esperando a que MySQL (db:3306) esté listo..."
until nc -z db 3306; do
  echo "MySQL no disponible aún... esperando 1 segundo"
  sleep 1
done
echo "${GREEN}MySQL está disponible${NC}"

cd /var/www || exit 1

# Validar archivo .env
if [ ! -f ".env" ]; then
  echo "${RED}Archivo .env no encontrado. Creando desde .env.example...${NC}"
  cp .env.example .env
else
  echo ".env existente, ok"
fi

# Permisos para Laravel
echo "Corrigiendo permisos en storage y bootstrap/cache..."
chown -R www-data:www-data storage bootstrap/cache || echo "${RED}Error cambiando propietario${NC}"
chmod -R 775 storage bootstrap/cache || echo "${RED}Error cambiando permisos${NC}"

# Dependencias frontend
if [ ! -d "node_modules" ]; then
  echo "Instalando dependencias de frontend (npm install)..."
  if command -v npm >/dev/null 2>&1; then
    npm install || echo "${RED}Fallo al ejecutar npm install${NC}"
  else
    echo "${RED}npm no está instalado en el contenedor${NC}"
  fi
else
  echo "Dependencias ya instaladas, omitiendo npm install"
fi

# Compilar assets
if [ ! -d "public/build" ]; then
  echo "Compilando assets con Vite (npm run build)..."
  npm run build || echo "${RED}Fallo al ejecutar npm run build${NC}"
else
  echo "Assets ya compilados, omitiendo build"
fi

# Cachés Laravel
echo "Generando cachés de Laravel..."
php artisan config:clear
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true
echo "${GREEN}Cachés generadas correctamente${NC}"

# Control de migraciones y seeders
if [ "$RUN_DB_SETUP" = "true" ]; then
  echo "${GREEN}RUN_DB_SETUP habilitado. Ejecutando migraciones y seeders...${NC}"
  php artisan migrate --force || echo "${RED}Error en migraciones${NC}"
  php artisan db:seed --force || echo "${RED}Error al ejecutar seeders${NC}"
else
  # Detectar si hay datos antes de correr seeders
  if php artisan tinker --execute="\App\Models\User::first()" | grep -q "null"; then
    echo "${GREEN}Base de datos vacía. Ejecutando migraciones y seeders...${NC}"
    php artisan migrate --force
    php artisan db:seed --force
  else
    echo "${GREEN}Base de datos ya poblada. Omitiendo migraciones y seeders.${NC}"
  fi
fi

# Ejecutar supervisord u otro comando si se especifica
if [ $# -eq 0 ]; then
  echo "No se pasó ningún comando al contenedor. Iniciando supervisord..."
  exec supervisord -n -c /etc/supervisor/supervisord.conf
else
  echo "Ejecutando comando: $@"
  exec "$@"
fi
