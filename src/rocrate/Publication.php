<?php

namespace ROCrate;

use ROCrate\Entity;
use Exceptions\ROCrateException;

/**
 * Extends the ContextualEntity class
 */
class Publication extends ContextualEntity {
    /**
     * Constructs an instance of ScholarlyArticle type (or CreativeWork type)
     * @param string $id The ID of the Publication entity
     * @param string $type The type of the publication entity
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     */
    public function __construct(string $id, string $type = 'ScholarlyArticle') {
        if (strcmp($type, 'ScholarlyArticle') !== 0) {
            if (strcmp($type, 'CreativeWork') !== 0) {
                throw new ROCrateException("The given type is not valid for a publication entity.");
            }
        }
        parent::__construct($id, [$type]);
    }

    /**
     * Gets the information of the Publication entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array {
        return array_merge(parent::baseArray(), $this->properties);
    }
}