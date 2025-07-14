<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the Entity class and is mainly for convenience during testing
 */
class Person extends Entity {
    /**
     * Constructs an instance of Person type
     * @param string $id The ID of the person entity
     * @param string $name The name of the person
     */
    public function __construct(string $id, string $name) {
        parent::__construct($id, ['Person']);
        $this->addProperty('name', $name);
    }

    /**
     * Gets the information of the Person entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array {
        return array_merge(parent::baseArray(), $this->properties);
    }
}