#  Instalación con Docker (`docker-setup`)

Este proyecto Laravel ha sido completamente dockerizado. La rama `docker-setup` contiene todo lo necesario para levantar el sistema sin necesidad de XAMPP o configuraciones manuales de entorno.

---

##  Requisitos previos

- Tener instalado [Docker](https://www.docker.com/)
- Tener instalado [Docker Compose](https://docs.docker.com/compose/)

---

## ⚙️ Estructura del proyecto

```
sistema-inventario-ventas/
├── docker/                  # Configuración de servicios Docker
│   ├── nginx/               # Configuración NGINX
│   └── php/                 # Configuración PHP-FPM y supervisord
├── src/                     # Proyecto Laravel completo
├── Dockerfile               # Imagen PHP personalizada
├── docker-compose.yml       # Orquestador de contenedores
├── .dockerignore            # Ignorar archivos en build
```

---

##  Pasos para levantar el entorno

### 1. Clonar el repositorio y cambiar a la rama Docker

```bash
git clone https://github.com/willian-d29/sistema-inventario-ventas.git
cd sistema-inventario-ventas
git checkout docker-setup
```

### 2. Levantar los contenedores

```bash
docker-compose up -d --build
```

### 3. Entrar al contenedor de Laravel

```bash
docker exec -it laravel-app bash
```

### 4. Configurar Laravel dentro del contenedor

```bash
cd src
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev
```

---

## 🌐 Acceso

- Navega a: [http://localhost](http://localhost)
- Laravel está contenido dentro de la carpeta `src/`
- NGINX está configurado para servir desde `/src/public`

---

##  Actualizaciones de código y Pull Requests

Para guardar y subir cambios:

```bash
git add -A
git commit -m "feat: actualización en entorno docker"
git push origin docker-setup
```

Puedes crear un **Pull Request** desde GitHub para fusionar tus cambios a la rama 
