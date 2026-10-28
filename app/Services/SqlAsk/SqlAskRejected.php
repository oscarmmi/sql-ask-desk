<?php

namespace App\Services\SqlAsk;

use App\Models\AskQuery;
use RuntimeException;

class SqlAskRejected extends RuntimeException
{
    public function __construct(public readonly AskQuery $query)
    {
        parent::__construct('The generated SQL was rejected by the read-only guard.');
    }
}
