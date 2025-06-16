<?php
// src/exceptions/JsonFileException.php
namespace Exceptions;

/**
 * Custom exception for JSON file operations
 */
class JsonFileException extends \Exception
{
    public const FILE_NOT_FOUND = 1;
    public const INVALID_JSON = 2;
    public const WRITE_FAILURE = 3;
}