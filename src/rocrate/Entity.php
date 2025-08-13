<?php

namespace ROCrate;

/**
 * Handles the entities of a ro-crate object
 */
abstract class Entity
{
    protected string $id;
    protected array $types;
    protected array $properties = [];
    protected ?ROCrate $crate = null;

    /**
     * Constructs an entity instance
     * @param string $id The ID of the entity
     * @param array $types The type(s) of the entity
     */
    public function __construct(string $id, array $types)
    {
        $this->id = $id;
        $this->types = $types;
    }

    /**
     * Gets the ID of the entity instance
     * @return string The ID string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Sets the ID of the entity instance
     * @param string $id
     * @return Entity The entity instance itself
     */
    public function setId(string $id): Entity
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Gets the type(s) of the entity instance
     * @return array The type(s) as an array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Sets the type(s) ot the entity instance
     * @param array $newTypes The new type(s)
     * @return Entity The entity instance itself
     */
    public function setTypes(array $newTypes): Entity
    {
        $this->types = $newTypes;
        return $this;
    }

    /**
     * Adds a new type to the existing type(s) of the entity instance, or does nothing when the type already exists
     * @param string $type The type to add
     * @return Entity The entity instance itself
     */
    public function addType(string $type): Entity
    {
        if (!in_array($type, $this->types, true)) {
            $this->types[] = $type;
        }
        return $this;
    }

    /**
     * Removes a new type from the existing type(s) of the entity instance, or does nothing when the type does not exist
     * @param string $type The type to be removed
     * @return Entity The entity instance itself
     */
    public function removeType(string $type): Entity
    {
        if (in_array($type, $this->types, true)) {
            $key = array_search($type, $this->types, true);
            unset($this->types[$key]);
        }
        return $this;
    }

    /**
     * Gets a property value of the entity instance given the key string
     * @param string $key The key string
     * @return mixed The value corresponding to the key or null if there is no such key
     */
    public function getProperty(string $key): mixed
    {
        return $this->properties[$key] ?? null;
    }

    /**
     * Gets all the properties of the entity instance
     * @return array The properties as an array of key-value pair(s)
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Adds a new property to the entity instance, or overwrites the old property of the same key, i.e. sets the property
     * @param string $key The key string of the new property
     * @param mixed $value The value of the property
     * @return Entity The entity instance itself
     */
    public function addProperty(string $key, $value): Entity
    {
        $this->properties[$key] = $value;
        return $this;
    }

    /**
     * Adds a new key-value pair to a property, or does nothing when the pair alraedy exists in the property
     * @param string $propertyKey The key string of the property
     * @param mixed $value The value to be added of the property
     * @param mixed $flag The flag is true if @id, or is false if literal
     * @return Entity The entity instance itself
     */
    public function addPropertyPair(string $propertyKey, $value, ?bool $flag = null): Entity
    {

        if (array_key_exists($propertyKey, $this->properties)) {
            if (!is_array($this->properties[$propertyKey])) {
                return $this;
            }
            if ($this->properties[$propertyKey] === []) {
                return $this;
            }

            if (!is_null($flag)) {
                if ($flag) {
                    if (in_array(['@id' => $value], $this->properties[$propertyKey], true)) {
                        return $this;
                    }
                    $this->properties[$propertyKey][] = ['@id' => $value];
                } else {
                    if (in_array($value, $this->properties[$propertyKey], true)) {
                        return $this;
                    }
                    $this->properties[$propertyKey][] = $value;
                }
                return $this;
            }

            if (!is_array($this->properties[$propertyKey][0])) {
                if (in_array($value, $this->properties[$propertyKey], true)) {
                    return $this;
                }
                $this->properties[$propertyKey][] = $value;
            } else {
                if (in_array(['@id' => $value], $this->properties[$propertyKey], true)) {
                    return $this;
                }
                $this->properties[$propertyKey][] = ['@id' => $value];
            }
        } else {
            if (is_null($flag)) {
                return $this;
            }
            if ($flag) {
                $this->addProperty($propertyKey, [['@id' => $value]]);
            } else {
                $this->addProperty($propertyKey, [$value]);
            }
        }

        return $this;
    }

    /**
     * Removes a property from the entity instance, or does nothing when the key does not exist
     * @param string $key The key string of the property to remove
     * @return Entity The entity instance itself
     */
    public function removeProperty(string $key): Entity
    {
        if (array_key_exists($key, $this->properties)) {
            unset($this->properties[$key]);
        }
        return $this;
    }

    /**
     * Removes a key-value pair from a property of the entity instance, or does nothing when the either key does not exist or there is no inner array
     * @param string $propertyKey The key string of the property to remove
     * @param string $key The key string of the property to remove
     * @param mixed $value The value to be deleted of the property
     * @return Entity The entity instance itself
     */
    public function removePropertyPair(string $propertyKey, $value): Entity
    {

        if (array_key_exists($propertyKey, $this->properties)) {
            if (!is_array($this->properties[$propertyKey])) {
                return $this;
            }

            if (array_search($value, $this->properties[$propertyKey]) !== false) {
                // is literal
                unset($this->properties[$propertyKey][array_search($value, $this->properties[$propertyKey])]);
                if ($this->properties[$propertyKey] === []) {
                    $this->removeProperty($propertyKey);
                }
            } elseif (array_search(["@id" => $value], $this->properties[$propertyKey]) !== false) {
                // is ["@id" => "..."]
                unset($this->properties[$propertyKey][array_search(["@id" => $value], $this->properties[$propertyKey])]);
                $this->properties[$propertyKey] = array_values($this->properties[$propertyKey]);
                if ($this->properties[$propertyKey] === []) {
                    $this->removeProperty($propertyKey);
                }
            }

            /*
            foreach($this->properties[$propertyKey] as $pair) {
                // pair should be ['@id' => ...] We do not check
                // or pair is string literal
                if (!is_array($pair)) {
                    if ($pair == $value) {
                        unset($this->properties[$propertyKey][array_search($pair, $this->properties[$propertyKey])]);
                        if ($this->properties[$propertyKey] === []) $this->removeProperty($propertyKey);
                        break;
                    }
                }
                else if (array_key_exists('@id', $pair)) {
                    if ($pair['@id'] == $value) {
                        unset($this->properties[$propertyKey][array_search($pair, $this->properties[$propertyKey])]);
                        $this->properties[$propertyKey] = array_values($this->properties[$propertyKey]);
                        if ($this->properties[$propertyKey] === []) $this->removeProperty($propertyKey);
                        break;
                    }
                }
            }*/
        }

        return $this;
    }

    /**
     * Sets the crate object to which the entity instance belongs
     * @param \ROCrate\ROCrate $crate The crate object
     * @return void
     */
    public function setCrate(ROCrate $crate): void
    {
        $this->crate = $crate;
    }

    /**
     * Gets the information of the entity as an array for printing, debugging and inheritance and is to be overriden
     * @return array The information array
     */
    abstract public function toArray(): array;

    /**
     * Gets the basic information of the entity as an array
     * @return array{@id: string, @type: array} The array consisting of the ID and type(s) of the entity instance
     */
    protected function baseArray(): array
    {
        if ($this->types) {
            // there is at least one type
            return [
                '@id' => $this->id,
                '@type' => $this->types
            ];
        }
        // types array is empty
        return [
            '@id' => $this->id
        ];
    }
}
