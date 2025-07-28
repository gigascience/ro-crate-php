<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the ContextualEntity class
 */
class Place extends ContextualEntity {
    /**
     * Constructs an instance of Place type
     * @param string $id The ID of the place entity
     */
    public function __construct(string $id) {
        parent::__construct($id, ['Place']);
    }

    /**
     * Gets the information of the Place entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array {
        return array_merge(parent::baseArray(), $this->properties);
    }
}