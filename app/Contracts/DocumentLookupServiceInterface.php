<?php

namespace App\Contracts;

use App\Data\DocumentLookupResult;

interface DocumentLookupServiceInterface
{
    public function lookupDni(string $dni): DocumentLookupResult;

    public function lookupRuc(string $ruc): DocumentLookupResult;
}
