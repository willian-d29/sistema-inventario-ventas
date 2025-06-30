# ✨ Sistema de Inventario y Ventas (SPA)

Un sistema robusto de gestión de inventario y punto de venta, construido con **Laravel 10**, **MySQL**, **Inertia.js**, **Vue.js**, **Vite**, **Tailwind CSS**, y **JavaScript**, diseñado para optimizar los procesos de ventas, compras e inventario. Es una **aplicación de una sola página (SPA)** que permite gestionar productos, clientes, empleados y transacciones de forma eficiente.

> ⚠️ Este sistema está basado en el proyecto original de [@mamun724682](https://github.com/mamun724682), al cual se le han realizado modificaciones y mejoras por parte de nuestro equipo.

---

## 🛠️ Tecnologías utilizadas

- **Laravel 10** (Backend y lógica del sistema)
- **MySQL** (Base de datos relacional)
- **Vue 3 + Inertia.js** (Frontend reactivo)
- **Vite** (Empaquetador moderno de assets)
- **Tailwind CSS** (Framework CSS)
- **JavaScript (ES6+)**
- **Composer & NPM** (Manejo de dependencias)
- **SPA (Single Page Application)**

---

## 🌟 Funcionalidades Clave

- **Dashboard o panel principal**
- **Punto de venta (POS)**
- **Gestión de órdenes**
  - Pagos pendientes
  - Liquidación de deuda
- **Historial de transacciones**
- **Módulo de categorías**
- **Tipos de unidades**
- **Gestión de productos**
- **Gestión de clientes**
- **Gestión de empleados**
- **Módulo de sueldos**
- **Control de gastos**
- **Configuración general del sistema**

---

## 🚀 Guía de instalación (entorno local)

Sigue estos pasos para instalar y ejecutar el proyecto en tu máquina local:

```bash
# 1. Clona el repositorio
git clone https://github.com/willian-d29/sistema-inventario-ventas.git

# 2. Ingresa a la carpeta del proyecto
cd sistema-inventario-ventas

# 3. Instala dependencias de PHP
composer install

# 4. Copia el archivo de entorno
cp .env.example .env

# 5. Genera la clave de la aplicación
php artisan key:generate

# 6. Configura tus credenciales de base de datos en el archivo .env

# 7. Ejecuta migraciones y carga datos de prueba
php artisan migrate:fresh --seed

# 8. Enlaza el almacenamiento de archivos públicos
php artisan storage:link

# 9. Instala dependencias de JavaScript y CSS
npm install && npm run dev

# 10. Inicia el servidor de desarrollo
php artisan serve
