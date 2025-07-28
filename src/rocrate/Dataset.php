<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the DataEntity class and is also known as directory
 */
class Dataset extends DataEntity {
    /**
     * Constructs a dataset entity (a more specfic data entity), for instance, the root data entity
     * @param string $id The ID of the dataset entity
     */
    public function __construct(string $id = './') {
        parent::__construct($id, ['Dataset']);
    }

    /**
     * Gets the information of the dataset entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array {
        $data = parent::baseArray();
        return array_merge($data, $this->properties);
    }
}