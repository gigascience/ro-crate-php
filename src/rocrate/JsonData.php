<?php

namespace ROCrate;

/**
 * Supports nested objects/arrays with array and object access
 * 
 * Implements ArrayAccess, IteratorAggregate, Countable, and JsonSerializable interfaces
 * to provide JSON-like behavior in PHP
 */
class JsonData implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * Internal data storage
     * @var array
     */
    protected array $data = [];

    /**
     * Constructor
     * @param array $data Initial data
     */
    public function __construct(array $data = [])
    {
        $this->merge($data);
    }

    /**
     * Merge data into the structure
     * @param array $data Data to merge
     */
    public function merge(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    // ArrayAccess implementation
    public function offsetSet($offset, $value): void
    {
        if (is_array($value)) {
            $value = new self($value);
        }
        
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    // IteratorAggregate implementation
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->data);
    }

    // JsonSerializable implementation
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert to JSON string
     * @param int $options JSON encoding options
     * @return string JSON representation
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Create from JSON string
     * @param string $json JSON string
     * @return self JsonData instance
     * @throws \InvalidArgumentException on JSON decode error
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                'JSON decode error: ' . json_last_error_msg()
            );
        }
        
        return new self($data);
    }

    /**
     * Convert to plain PHP array
     * @return array Plain array representation
     */
    public function toArray(): array
    {
        $result = [];
        
        foreach ($this->data as $key => $value) {
            if ($value instanceof self) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    // Magic methods for object-style access
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    /**
     * String representation for debugging
     * @return string JSON representation
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}