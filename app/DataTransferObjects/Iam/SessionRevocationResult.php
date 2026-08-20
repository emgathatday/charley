<?php

namespace App\DataTransferObjects\Iam;

readonly class SessionRevocationResult
{
    public function __construct(public bool $changed)
    {
    }
}
