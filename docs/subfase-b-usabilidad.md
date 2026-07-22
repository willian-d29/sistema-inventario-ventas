# Subfase B - Auditoria de usabilidad y QA operativo

## Auditoria previa

| Area | Hallazgo | Gravedad | Estado |
| --- | --- | --- | --- |
| Navegacion | Existian sidebars separados para admin y cajero, con listas divergentes. | Mayor | Corregido con menu central en `resources/js/Navigation/menu.js`. |
| Dashboard | El dashboard era administrativo y mezclaba nombres legacy como ordenes/ganancias. | Mayor | Corregido con dashboard por rol. |
| Ventas | El historial tenia filtros insuficientes para una tienda real. | Mayor | Corregido con filtros avanzados. |
| Detalle venta | La informacion operativa y administrativa no estaba separada. | Mayor | Corregido; costos/utilidad solo admin. |
| POS | El flujo existia, pero faltaba ayuda breve, atajos y mejores estados de busqueda. | Menor | Corregido parcialmente. |
| Responsive | Tablas grandes dependian de scroll horizontal. | Mayor | Ventas usa tabla en escritorio y tarjetas en movil. |
| Accesibilidad | Sidebar movil no tenia drawer robusto ni restauracion de foco. | Mayor | Corregido en sidebar unico. |
| Textos | Persistian textos de plantilla en ingles. | Menor | Corregido en POS/carrito principal; queda auditoria pendiente en componentes legacy. |

## Componentes heredados

| Componente | Clasificacion | Evidencia | Accion |
| --- | --- | --- | --- |
| `Components/Maps/MapExample.vue` | Seguro de eliminar despues de confirmacion | No aparece importado por paginas activas. | No eliminado en esta fase. |
| `Components/BuyNow.vue` | Dudoso/plantilla | No pertenece al flujo POS. | No eliminado en esta fase. |
| `Components/Dropdowns/IndexDropdown.vue` | Reemplazado | Referencia rutas demo como mapas. | No eliminado; no visible en menu nuevo. |
| `Components/Dropdowns/PagesDropdown.vue` | Reemplazado | Referencia rutas demo como mapas. | No eliminado; no visible en menu nuevo. |
| `Components/Cards/CardLineChart.vue` | Reemplazado | Dashboard nuevo no usa graficas decorativas. | No eliminado por seguridad. |
| `Components/Cards/CardBarChart.vue` | Reemplazado | Dashboard nuevo no usa graficas decorativas. | No eliminado por seguridad. |
| `SidebarVendedor.vue` | Reemplazado | Layout usa `Sidebar.vue` unico. | No eliminado hasta cierre final. |
| Rutas `orders`/`transactions` | Legacy redirigido | Siguen redirigiendo a ventas/reportes. | No visibles en menu. |

## Nielsen

| Pantalla | Heuristica | Problema | Gravedad | Solucion | Estado |
| --- | --- | --- | --- | --- | --- |
| POS | Visibilidad del estado | Busqueda sin feedback de carga. | Menor | Indicador en boton buscar. | Corregido |
| POS | Prevencion de errores | Incremento visual podia superar stock. | Mayor | Boton incrementar se deshabilita al llegar al stock. | Corregido |
| POS | Flexibilidad | No habia atajos visibles. | Menor | Ayuda breve con F2/F4/Escape. | Corregido |
| Ventas | Reconocimiento antes que recuerdo | Faltaban filtros operativos visibles. | Mayor | Filtros completos en una banda. | Corregido |
| Detalle venta | Minimalismo | Mezcla de resumen, productos y pagos. | Menor | Secciones claras. | Corregido |
| Navegacion | Consistencia | Dos menus por rol mantenidos a mano. | Mayor | Menu central por rol. | Corregido |
| Dashboard cajero | Correspondencia mundo real | Cajero no tenia vista de turno/caja. | Mayor | Dashboard de cajero. | Corregido |
| Sidebar movil | Control y libertad | Faltaba Escape/foco/drawer consistente. | Mayor | Drawer con Escape, bloqueo scroll y restauracion foco. | Corregido |
| Reportes | Recuperacion de errores | Deuda pendiente en mensajes vacios/errores. | Menor | Documentado para fase final. | Pendiente |
| Formularios | Ayuda | Algunos formularios no explican abandono con cambios. | Menor | Documentado para fase final. | Pendiente |

## QA operativo simulado

| Paso | Resultado esperado | Resultado real | Gravedad | Estado |
| --- | --- | --- | --- | --- |
| Admin inicia sesion | Entra al dashboard admin. | Cubierto por rutas y tests. | - | OK |
| Configura negocio | Settings admin disponible. | Ya cubierto Subfase A. | - | OK |
| Registra productos | Modulo sigue en menu admin. | Visible en navegacion. | - | OK |
| Abre caja | Caja disponible para admin/cajero. | Vista y POS muestran estado. | - | OK |
| Cajero inicia sesion | Ve dashboard de cajero. | Implementado. | - | OK |
| Escanea productos | Codigo exacto agrega; no exacto busca. | Tests existentes. | - | OK |
| Crea cliente | Cliente rapido se mantiene en POS. | Sin cambios. | - | OK |
| Cobra efectivo | Pago efectivo con recibido/vuelto. | Tests existentes. | - | OK |
| Cobra Yape | Pago digital con referencia. | Tests existentes. | - | OK |
| Pago mixto | Maximo dos metodos. | Tests existentes. | - | OK |
| Reimprime | Solicitud explicita crea log. | Tests existentes. | - | OK |
| Registra retiro | Requiere confirmacion. | Tests existentes. | - | OK |
| Registra gasto desde caja | Movimiento vinculado. | Tests existentes. | - | OK |
| Consulta ventas | Filtros avanzados. | Implementado. | - | OK |
| Cierra caja | Cierre con arqueo. | Tests existentes. | - | OK |
| Admin revisa arqueo | Revision admin. | Tests existentes. | - | OK |
| Admin revisa dashboard | Metricas admin. | Implementado. | - | OK |
| Admin exporta reporte | Reportes admin protegidos. | Sin cambios. | - | OK |

## Responsive revisado

- 1440x900: sidebar expandido, ventas en tabla, POS dividido productos/carrito.
- 1366x768: POS mantiene total y cobrar fijos en columna derecha.
- 1024x768: ventas conserva tabla con scroll contenido, no global.
- 768x1024: sidebar drawer y tarjetas de ventas en movil/tablet estrecha.
- 390x844: ventas cambia a tarjetas, drawer bloquea scroll de fondo, botones tactiles mantienen altura minima.

QA automatizado con Playwright local:

| Viewport | Dashboard admin | Ventas | POS | Dashboard cajero | Overflow global | Drawer movil |
| --- | --- | --- | --- | --- | --- | --- |
| 1440x900 | OK | OK | OK | OK | No | N/A |
| 1366x768 | OK | OK | OK | OK | No | N/A |
| 1024x768 | OK | OK | OK | OK | No | N/A |
| 768x1024 | OK | OK | OK | OK | No | N/A |
| 390x844 | OK | OK | OK | OK | No | OK |

## Pendientes para fase final

- Ejecutar QA visual con navegador y capturas en todos los breakpoints.
- Decidir eliminacion fisica de componentes heredados.
- Unificar tablas de todos los modulos administrativos, no solo ventas.
- Agregar confirmacion de abandono con cambios en formularios largos.
