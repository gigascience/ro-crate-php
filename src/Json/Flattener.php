<?php
// src/Json/Flattener.php
namespace Json;

/**
 * Flattens nested JSON structures into dot-notated key-value pairs
 */
class Flattener
{
    /**
     * Flatten a nested array into dot-notated keys
     * 
     * @param array $data Input data
     * @param string $prefix Internal use for recursion
     * @param string $separator Key separator
     * @return array Flattened key-value pairs
     */
    public function flatten(array $data, string $prefix = '', string $separator = '.'): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            $newKey = $prefix ? $prefix . $separator . $key : $key;
            
            if (is_array($value)) {
                $result = array_merge(
                    $result, 
                    $this->flatten($value, $newKey, $separator)
                );
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }
}