<?php

namespace Stdimitrov\Orm\Exceptions;

use RuntimeException;
use Throwable;

class DatabaseException extends RuntimeException
{
    protected readonly ?string $httpCode;
    private static ?string $query = null;
    private static ?array $params = null;


    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getQuery(): string
    {
        return self::$query;
    }

    public function getParams(): array
    {
        return self::$params;
    }

    public function setQuery(string $query): static
    {
        self::$query = $query;
        return $this;
    }

    public function setParams(array $params): static
    {
        self::$params = $params;
        return $this;
    }

}