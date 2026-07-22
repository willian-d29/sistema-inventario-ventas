# LaraTory - Composer Audit Final

Fecha: 2026-07-21

## Entorno

- PHP: 8.2.32
- Composer: 2.10.1
- Laravel: 10.50.2

## Resultado inicial

`composer audit` reporto 44 advisories en 15 paquetes, incluyendo Laravel, Guzzle, PSR-7, Symfony, PHPUnit, PsySH y dependencias transitivas.

## Acciones aplicadas

Actualizacion dirigida, sin `composer update` irrestricto:

- `laravel/framework`: 10.48.5 -> 10.50.2
- `guzzlehttp/guzzle`: 7.8.1 -> 7.15.1
- `guzzlehttp/psr7`: 2.6.2 -> 2.13.0
- `barryvdh/laravel-dompdf`: 3.1.1 -> 3.1.2
- `maatwebsite/excel`: 3.1.64 -> 3.1.69
- `phpoffice/phpspreadsheet`: 1.29.10 -> 1.30.6
- `pestphp/pest`: 2.34.7 -> 2.36.1
- `pestphp/pest-plugin-laravel`: 2.3.0 -> 2.4.0
- `phpunit/phpunit`: 10.5.17 -> 10.5.63
- `psy/psysh`: 0.12.3 -> 0.12.24
- `laravel/tinker`: 2.9.0 -> 2.11.1
- Symfony transitivos compatibles actualizados por Composer.

## Resultado final

`composer audit` queda con 3 advisories sobre `laravel/framework`.

Motivo: Packagist marca Laravel 10.x como afectado por advisories actuales cuya solucion limpia esta en Laravel 12.60+/12.61+ o ramas mayores. No se aplico upgrade mayor porque la fase lo prohibe.

## Recomendacion

Planificar una fase de upgrade mayor Laravel:

1. Crear rama exclusiva.
2. Subir Laravel 10 -> 11 -> 12 con guia oficial y tests por tramo.
3. Revisar Inertia Laravel, Sanctum, Breeze, Collision, Pest y Log Viewer.
4. Repetir migraciones, QA visual y suite POS/caja completa.

