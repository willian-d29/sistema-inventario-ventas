<?php

namespace App\Data;

class DocumentLookupResult
{
    public function __construct(
        public readonly bool $found,
        public readonly bool $configured,
        public readonly string $message,
        public readonly ?string $type = null,
        public readonly ?string $number = null,
        public readonly array $data = [],
    ) {
    }

    public static function notConfigured(): self
    {
        return new self(
            found: false,
            configured: false,
            message: 'La consulta automática no está configurada. Puedes registrar los datos manualmente.',
        );
    }

    public static function notFound(bool $configured = true, ?string $message = null): self
    {
        return new self(
            found: false,
            configured: $configured,
            message: $message ?: 'No se encontraron datos para el documento.',
        );
    }

    public static function found(string $type, string $number, array $data): self
    {
        return new self(
            found: true,
            configured: true,
            message: 'Datos encontrados.',
            type: $type,
            number: $number,
            data: $data,
        );
    }

    public function toResponse(): array
    {
        $response = [
            'found' => $this->found,
            'configured' => $this->configured,
            'message' => $this->message,
        ];

        if ($this->found) {
            $response['type'] = $this->type;
            $response['number'] = $this->number;
            $response['data'] = $this->data;
        }

        return $response;
    }
}
