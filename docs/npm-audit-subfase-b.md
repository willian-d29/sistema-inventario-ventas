# Informe npm - Subfase B

No se ejecutaron actualizaciones mayores ni `npm audit fix --force`.

## Resumen detectado

| Alcance | Total | Low | Moderate | High | Critical |
| --- | ---: | ---: | ---: | ---: | ---: |
| Todas las dependencias | 58 | 4 | 27 | 24 | 3 |
| Produccion (`--omit=dev`) | 7 | 0 | 3 | 4 | 0 |

## Dependencias directas relevantes

| Paquete | Severidad | Alcance | Comentario |
| --- | --- | --- | --- |
| `@inertiajs/progress` | High | Produccion | Arrastra `@inertiajs/inertia` y `axios`; conviene reemplazar/actualizar junto con Inertia en una fase controlada. |
| `lodash` | High | Produccion | Dependencia directa; revisar uso real y actualizar patch/minor si no rompe build. |
| `postcss` | Moderate | Produccion/build | Directa; afecta principalmente procesamiento CSS/build, no implica automaticamente explotacion en navegador. |
| `axios` | High | Desarrollo directo y transitiva en produccion | En `devDependencies`, pero tambien llega transitivamente por Inertia antiguo. |
| `gulp` | High | Desarrollo | Tooling heredado; revisar si se usa realmente. |
| `@vue/cli-plugin-babel` | Moderate | Desarrollo | Paquete antiguo de Vue CLI, probablemente heredado y no necesario para Vite. |
| `node-ipc` | Critico/alto en arbol dev | Desarrollo | Riesgo de cadena de suministro historico; confirmar si entra solo por tooling obsoleto antes de retirar. |

## Impacto

- No todas las vulnerabilidades son explotables en el navegador.
- Las 7 de produccion merecen atencion antes de una publicacion real.
- La mayor parte del ruido viene de tooling antiguo o paquetes duplicados que quedaron de la plantilla original.

## Recomendacion

1. Crear una fase tecnica solo para dependencias.
2. Quitar Vue CLI/Gulp si el build Vite no los usa.
3. Actualizar Inertia/Vue/Vite en bloque con pruebas visuales.
4. Mantener Node 22 como version recomendada y validar `npm ci && npm run build` bajo esa version.
