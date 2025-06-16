<?php

namespace ROCrate;

use Json\{Flattener, FileHandler};
use Exceptions\JsonFileException;

/**
 * Handles the reading of a ro-crate-metadata file
 */
class ROCrateReader
{
    /**
     * Read the ro-crate-metadata file, and decode it into a ROCrate object for manipulation
     * 
     * @param string $filePath Absolute file path
     * @return ROCrate Decoded JSON data as a ROCrate object
     * @throws JsonFileException
     */
    public static function readFile(string $filePath): ROCrate 
    {
        
    }
}