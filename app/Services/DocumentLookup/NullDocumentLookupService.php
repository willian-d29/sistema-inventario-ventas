<?php

namespace App\Services\DocumentLookup;

use App\Contracts\DocumentLookupServiceInterface;
use App\Data\DocumentLookupResult;

class NullDocumentLookupService implements DocumentLookupServiceInterface
{
    public function lookupDni(string $dni): DocumentLookupResult
    {
        return DocumentLookupResult::notConfigured();
    }

    public function lookupRuc(string $ruc): DocumentLookupResult
    {
        return DocumentLookupResult::notConfigured();
    }
}
