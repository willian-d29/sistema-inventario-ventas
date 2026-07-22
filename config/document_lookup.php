<?php

return [
    'provider' => env('DOCUMENT_LOOKUP_PROVIDER', 'null'),
    'api_url' => env('DOCUMENT_LOOKUP_API_URL'),
    'api_token' => env('DOCUMENT_LOOKUP_API_TOKEN'),
    'timeout' => (int) env('DOCUMENT_LOOKUP_TIMEOUT', 5),
    'dni_path' => env('DOCUMENT_LOOKUP_DNI_PATH'),
    'ruc_path' => env('DOCUMENT_LOOKUP_RUC_PATH'),
];
