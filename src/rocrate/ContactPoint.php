<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the ContextualEntity class and is recommended to be associated with a Dataset indirectly via author
 * or publisher property to respect the specification of properties of the Dataset type in Schema.org (not must)
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
        return parent::toArray();
    }
}