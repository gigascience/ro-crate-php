<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the Entity class
 */
class DataEntity extends Entity {
    private ?string $sourcePath;

    /**
     * Constructs a data/(contextual) entity instance
     * @param string $id The ID of the data entity
     * @param array $types The type(s) of the data entity
     * @param mixed $source The path to the data entity or null for a contextual entity
     */
    public function __construct(string $id, array $types, ?string $source = null) {
        parent::__construct($id, $types);
        $this->sourcePath = $source;
    }

    /**
     * Gets the path to the data entity
     * @return string|null The path to the data entity or null for a contextual entity
     */
    public function getSourcePath(): ?string {
        return $this->sourcePath;
    }

    /**
     * Gets the information of the data entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array {
        $data = parent::baseArray();
        
        // Add file-specific properties
        if ($this->sourcePath && file_exists($this->sourcePath)) {
            $data['contentSize'] = filesize($this->sourcePath);
            $data['sha256'] = hash_file('sha256', $this->sourcePath);
        }
        
        return array_merge($data, $this->properties);
    }
}