<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the ContextualEntity class
 */
class ContactPoint extends ContextualEntity {
    /**
     * Constructs an instance of ContactPoint type
     * @param string $id The ID of the contact point entity
     */
    public function __construct(string $id) {
        parent::__construct($id, ['ContactPoint']);
    }

    /**
     * Gets the information of the contact point entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array {
        return array_merge(parent::baseArray(), $this->properties);
    }
}