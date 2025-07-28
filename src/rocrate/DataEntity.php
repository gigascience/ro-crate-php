<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the Entity class, is the entities that are considered as some forms of actual data within 
 * the root dataset regardless of physical preference and can be also a contextual entity in certain 
 * circumstances
 */
class DataEntity extends Entity {
    /**
     * Constructs a data entity instance
     * @param string $id The ID of the data entity
     * @param array $types The type(s) of the data entity
     */
    public function __construct(string $id, array $types) {
        parent::__construct($id, $types);
    }

    /**
     * Gets the information of the data entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array {
        $data = parent::baseArray();        
        return array_merge($data, $this->properties);
    }
}