# LaraTory - Final QA Report

Fecha de cierre: 2026-07-21

## Resumen ejecutivo

LaraTory queda funcional en Docker para el flujo POS de minimarket con dos roles operativos: administrador y cajero. Se valido con PHP 8.2 dentro del contenedor, Laravel 10.50.2, Composer 2.10.1 y Node 22.23.1 aislado para cumplir `.nvmrc` y `package.json`.

## Cambios correctivos aplicados en TANDA 4

| Estado | Componente | Descripcion | Solucion aplicada |
|---|---|---|---|
| OK | Node | El host tenia Node 25.9.0, pero el proyecto exige Node 22. | Se ejecuto la validacion con Node 22.23.1 aislado en `/tmp`, sin tocar la instalacion global. |
| OK | npm produccion | `npm audit --omit=dev` inicio con 7 vulnerabilidades. | Se retiro `@inertiajs/progress`, Vue CLI, Gulp y paquetes legacy; produccion quedo en 0 vulnerabilidades. |
| WARNING | npm dev | `npm audit` completo queda con 2 vulnerabilidades en Vite/esbuild. | No se aplico `npm audit fix --force` porque exige Vite 8, upgrade mayor prohibido. |
| OK | Composer | `composer audit` inicio con 44 advisories. | Se actualizaron paquetes directos y transitivos dentro de ramas permitidas: Laravel 10.50.2, Guzzle 7.15.1, Symfony compatibles, PHPSpreadsheet, Pest/PHPUnit, etc. |
| WARNING | Laravel | `composer audit` final conserva 3 advisories sobre `laravel/framework`. | No se hizo upgrade mayor a Laravel 12 porque la fase lo prohibe; queda recomendado como siguiente fase tecnica. |
| OK | Frontend | `@inertiajs/progress` traia Inertia antigua y Axios vulnerable. | Se uso el `progress` nativo de `createInertiaApp`. |
| OK | UI comun | `AppButton` enviaba `as=null` a `Link` de Inertia y causaba `toLowerCase()` sobre null. | Se normalizo `as` a `a` cuando no se define. |
| OK | Modal comun | `AppModal` asumia titulo string. | Se normalizo el titulo para evitar estados nulos transitorios y mantener `aria-labelledby`. |

## QA visual

Evidencia guardada en `docs/tanda4-artifacts/qa-screenshots`.

Pantallas verificadas:

- Login
- Dashboard administrador
- Dashboard cajero
- POS
- Ventas listado
- Caja
- Conciliacion
- Productos listado y creacion
- Clientes
- Proveedores
- Empleados
- Gastos
- Reportes
- Configuracion
- Perfil y apariencia
- Historial de impresiones

Matriz POS ejecutada:

- Viewports: 390x844, 768x1024, 1366x768, 1440x900, 1920x1080
- Temas: sistema, claro, oscuro, alto contraste, daltonismo
- Escalas: 100, 125, 150
- Densidad: comoda y compacta
- Movimiento: normal y reducido

Resultado final focalizado:

- 310 checks OK
- 300 combinaciones POS OK
- 0 errores de consola despues del fix de `AppButton`
- Sin overflow horizontal en ventas, productos y POS

## Bloqueos residuales

| Estado | Componente | Descripcion | Recomendacion |
|---|---|---|---|
| WARNING | Laravel 10 | Advisories actuales no quedan limpios dentro de Laravel 10. | Planificar upgrade controlado a Laravel 12 en una fase separada. |
| WARNING | Vite 5 | `npm audit` completo reporta Vite/esbuild dev-only. | Mantener Vite 5 por ahora; evaluar migracion a Vite 8 solo con fase frontend mayor. |

