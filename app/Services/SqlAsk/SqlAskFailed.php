<?php

namespace App\Services\SqlAsk;

use App\Models\AskQuery;
use RuntimeException;

class SqlAskFailed extends RuntimeException
{
    public function __construct(public readonly AskQuery $query)
    {
        parent::__construct('The SQL request could not be completed.');
    }
}
