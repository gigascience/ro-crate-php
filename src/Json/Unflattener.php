<?php

// src/Json/Unflattener.php
namespace Json;

/**
 * Rebuilds nested JSON structures from dot-notated key-value pairs
 */
class Unflattener
{
    /**
     * Unflatten dot-notated keys into a nested array
     *
     * @param array $data Flattened key-value pairs
     * @param string $separator Key separator
     * @return array Nested array structure
     */
    public function unflatten(array $data, string $separator = '.'): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $this->assignValueByPath($result, (string) $key, $value, $separator);
        }

        return $result;
    }

    /**
     * Assign value to nested array path
     */
    private function assignValueByPath(array &$array, string $path, $value, string $separator): void
    {
        $keys = explode($separator, $path);
        $current = &$array;
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }

            $current = &$current[$key];
        }

        $current[array_shift($keys)] = $value;
    }
}
