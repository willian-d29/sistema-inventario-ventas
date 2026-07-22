# LaraTory - npm Audit Final

Fecha: 2026-07-21

## Entorno

- Node validado: 22.23.1
- npm validado: 10.9.8
- `.nvmrc`: 22
- `package.json`: `>=22 <23`

## Resultado inicial

| Comando | Resultado |
|---|---|
| `npm audit --omit=dev` | 7 vulnerabilidades: 3 moderate, 4 high |
| `npm audit` | 58 vulnerabilidades: 4 low, 27 moderate, 24 high, 3 critical |

## Acciones aplicadas

- Eliminado `@inertiajs/progress`.
- Reemplazado por `progress` nativo de `createInertiaApp`.
- Eliminados paquetes legacy no usados: Vue CLI, Gulp, `gulp-append-prepend`, `core-js`, `@popperjs/core`.
- Actualizados parches/minores permitidos de Vue 3, Vite 5, Tailwind 3, PostCSS, Axios, lodash, Notivue y Vue Datepicker.
- Ejecutado `npm audit fix` sin `--force`.

## Resultado final

| Comando | Resultado |
|---|---|
| `npm ci` | OK |
| `npm run build` | OK client + SSR |
| `npm audit --omit=dev` | 0 vulnerabilidades |
| `npm audit` | 2 vulnerabilidades dev-only: Vite/esbuild |

## Riesgo residual

Las 2 vulnerabilidades restantes se corrigen solo con `npm audit fix --force`, que instalaria Vite 8.1.5. No se aplico porque la fase prohibe upgrades mayores de Vite.

Recomendacion: abrir una fase separada de migracion frontend mayor para Vite 8 cuando se decida actualizar el stack completo.

