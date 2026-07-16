<?php

namespace Eksprt\FileManager\Exceptions;

use Exception;

class InvalidUrlmageException extends Exception
{
    public static function image(): self
    {
        return new self("Url must be a valid image.");
    }
}
