<?php

namespace ROCrate;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Json\{FileHandler, Flattener, Unflattener};

/**
 * Specifies the exceptions for ROCrate
 */
class ROCrateException extends \RuntimeException {}

/**
 * Stores the structural information of a ro-crate-metadata.json as a crate object
 */

 /**
  * Handles the entities of a ro-crate object
  */
abstract class Entity {
    protected string $id;
    protected array $types;
    protected array $properties = [];
    protected ?ROCrate $crate = null;

    public function __construct(string $id, array $types) {
        $this->id = $id;
        $this->types = $types;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getTypes(): array {
        return $this->types;
    }

    public function addType(string $type): void {
        if (!in_array($type, $this->types, true)) {
            $this->types[] = $type;
        }
    }

    public function getProperty(string $key) {
        return $this->properties[$key] ?? null;
    }

    public function getProperties() {
        return $this->properties;
    }

    public function addProperty(string $key, $value): void {
        $this->properties[$key] = $value;
    }

    public function removeProperty(string $key): void {
        unset($this->properties[$key]);
    }

    public function setCrate(ROCrate $crate): void {
        $this->crate = $crate;
    }

    abstract public function toArray(): array;

    protected function baseArray(): array {
        return [
            '@id' => $this->id,
            '@type' => $this->types
        ];
    }
}

/**
 * Extends the Entity class
 */
class DataEntity extends Entity {
    private ?string $sourcePath;

    public function __construct(string $id, array $types, ?string $source = null) {
        parent::__construct($id, $types);
        $this->sourcePath = $source;
    }

    public function getSourcePath(): ?string {
        return $this->sourcePath;
    }

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

/**
 * Extends the DataEntity class
 */
class File extends DataEntity {
    public function __construct(string $id, ?string $source = null) {
        parent::__construct($id, ['File'], $source);
    }
}

/**
 * Extends the Entity class
 */
class Descriptor extends Entity {
    public function __construct(string $id = 'ro-crate-metadata.json') {
        parent::__construct($id, ['CreativeWork']);
    }

    public function toArray(): array {
        return array_merge(parent::baseArray(), $this->properties);
    }
}

/**
 * Extends the Entity class
 */
class Dataset extends Entity {
    public function __construct(string $id = './') {
        parent::__construct($id, ['Dataset']);
    }

    public function toArray(): array {
        $data = parent::baseArray();
        //$data['datePublished'] = date('c'); // !!!
        return array_merge($data, $this->properties);
    }
}

class Person extends Entity {
    public function __construct(string $id, string $name) {
        parent::__construct($id, ['Person']);
        $this->addProperty('name', $name);
    }

    public function toArray(): array {
        return array_merge(parent::baseArray(), $this->properties);
    }
}

class ROCrate {
    private string $basePath;
    private array $entities = [];
    private string $context = "https://w3id.org/ro/crate/1.2/context";
    private ?Descriptor $descriptor = null;
    private ?Dataset $rootDataset = null;
    private Graph $graph;
    private Client $httpClient;

    public function __construct(string $directory, bool $loadExisting = true) {
        $this->basePath = realpath($directory) ?: $directory;
        $this->graph = new Graph();
        $this->httpClient = new Client();
        
        RdfNamespace::set('rocrate', 'https://w3id.org/ro/crate/');
        RdfNamespace::set('schema', 'http://schema.org/');
        
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
        
        if ($loadExisting && file_exists($this->getMetadataPath())) {
            $this->loadMetadata();
        } else {
            $this->initializeNewCrate();
        }
    }

    private function getMetadataPath(): string {
        return $this->basePath . '/ro-crate-metadata.json';
    }

    private function initializeNewCrate(): void {
        $this->descriptor = new Descriptor();
        $this->addEntity($this->descriptor);

        $this->rootDataset = new Dataset();
        $this->addEntity($this->rootDataset);
    }

    public function loadMetadata(): void {
        $path = $this->getMetadataPath();
        
        if (!file_exists($path)) {
            throw new ROCrateException("Metadata file not found: $path");
        }
        
        try {
            $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ROCrateException("Invalid JSON in metadata: " . $e->getMessage());
        }
        
        // Set context
        $this->context = $json['@context'] ?? $this->context;
        
        // Parse entities
        foreach ($json['@graph'] as $entityData) {
            //if ($entityData['@id'] == "ro-crate-metadata.json") continue; // !!!
            if ($entityData['@id'] == "ro-crate-metadata.json") continue;
            $this->addEntityFromArray($entityData);
        }
        
        // Find metadata descriptor !!!
        /*
        foreach ($this->entities as $entity) {
            if (in_array('CreativeWork', $entity->getTypes()) && $entity->getId() === 'ro-crate-metadata.json') {
                //$this->descriptor = $entity;
                break;
            }
        }*/
        $this->descriptor = new Descriptor();
        $this->addEntity($this->descriptor);
        $this->addProfile();

        // Find root dataset
        foreach ($this->entities as $entity) {
            if (in_array('Dataset', $entity->getTypes()) && $entity->getId() === './') {
                $this->rootDataset = $entity;
                break;
            }
        }
        
        if (!$this->descriptor) {
            throw new ROCrateException("Metadata descriptor not found in crate");
        }

        if (!$this->rootDataset) {
            throw new ROCrateException("Root dataset not found in crate");
        }
    }

    private function addEntityFromArray(array $data): void {
        $id = $data['@id'] ?? null;
        $types = (array)($data['@type'] ?? []);
        
        if (!$id) {
            throw new ROCrateException("Entity missing @id property");
        }
        
        if (empty($types)) {
            throw new ROCrateException("Entity missing @type property: $id");
        }
        
        $entity = match(true) {
            in_array('Dataset', $types) => new Dataset($id),
            in_array('File', $types) => $this->createFileEntity($id, $data),
            in_array('Person', $types) => new Person($id, $data['name'] ?? 'Unknown'),
            default => $this->createGenericEntity($id, $types)
        };
        
        // Set properties
        foreach ($data as $key => $value) {
            if (!in_array($key, ['@id', '@type'])) {
                $entity->addProperty($key, $value);
            }
        }
        
        $this->addEntity($entity);
    }

    private function createFileEntity(string $id, array $data): File {
        $source = null;
        
        // Resolve local file path
        if (isset($data['contentSize'])) $source = $this->basePath . '/' . ltrim($id, '/');
        
        return new File($id, $source);
    }

    private function createGenericEntity(string $id, array $types): Entity {
        return new class($id, $types) extends Entity {
            public function toArray(): array {
                return array_merge($this->baseArray(), $this->properties);
            }
        };
    }

    public function addEntity(Entity $entity): void {
        $id = $entity->getId();
        
        if (isset($this->entities[$id])) {
            throw new ROCrateException("Entity with ID $id already exists");
        }
        
        $entity->setCrate($this);
        $this->entities[$id] = $entity;
        
        // Add to RDF graph !!!
        /*
        $resource = $this->graph->resource($id);
        foreach ($entity->getTypes() as $type) {
            $resource->addType($type);
        }
        foreach ($entity->getProperties() as $key => $value) {
            $resource->add($key, $value);
        }*/
    }

    public function getEntity(string $id): ?Entity {
        return $this->entities[$id] ?? null;
    }

    public function removeEntity(string $id): void {
        if (!isset($this->entities[$id])) {
            throw new ROCrateException("Entity not found: $id");
        }
        
        // Remove from RDF graph !!!
        //$this->graph->deleteResource($this->graph->resource($id));
        //print("The removal of a resource from the RDF graph is not implemented.");
        unset($this->entities[$id]);
    }

    public function addFile(string $source, ?string $destPath = null): File {
        if (!file_exists($source)) {
            throw new ROCrateException("Source file not found: $source");
        }
        
        $destPath = $destPath ?? basename($source);
        $destFullPath = $this->basePath . '/' . ltrim($destPath, '/');
        
        if (!copy($source, $destFullPath)) {
            throw new ROCrateException("Failed to copy file to $destFullPath");
        }
        
        $file = new File($destPath, $destFullPath);
        $this->addEntity($file);
        return $file;
    }

    public function addDirectory(string $path): Dataset {
        $fullPath = $this->basePath . '/' . trim($path, '/');
        
        if (!file_exists($fullPath) && !mkdir($fullPath, 0755, true)) {
            throw new ROCrateException("Failed to create directory: $fullPath");
        }
        
        $dataset = new Dataset($path);
        $this->addEntity($dataset);
        return $dataset;
    }

    public function addRemoteEntity(string $url): Entity {
        try {
            $response = $this->httpClient->get($url, ['headers' => ['Accept' => 'application/ld+json']]);
            $json = json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new ROCrateException("Failed to fetch remote entity: " . $e->getMessage());
        }
        
        if (!isset($json['@id'], $json['@type'])) {
            throw new ROCrateException("Invalid JSON-LD response from $url");
        }
        
        $entity = $this->createGenericEntity($json['@id'], (array)$json['@type']);
        
        foreach ($json as $key => $value) {
            if (!in_array($key, ['@id', '@type', '@context'])) {
                $entity->addProperty($key, $value);
            }
        }
        
        $this->addEntity($entity);
        return $entity;
    }

    public function validate(): array {
        $errors = [];
        
        // 1. Metadata descriptor check
        if (!$this->descriptor) {
            $errors[] = "Missing metadata descriptor";
        }

        // 2. Root dataset check
        if (!$this->rootDataset) {
            $errors[] = "Missing root dataset";
        }

        // No required property check
        
        // 3. File existence check
        foreach ($this->entities as $entity) {
            if ($entity instanceof DataEntity && $entity->getSourcePath()) {
                if (!file_exists($entity->getSourcePath())) {
                    $errors[] = "File not found: " . $entity->getSourcePath();
                }
            }
        }
        
        return $errors;
    }

    public function save(?string $path = null): void {
        $target = $path ? realpath($path) : $this->basePath;
        
        if (!$target) {
            throw new ROCrateException("Invalid target directory: $path");
        }
        
        // Ensure metadata directory exists
        if (!is_dir($target) && !mkdir($target, 0755, true)) {
            throw new ROCrateException("Failed to create directory: $target");
        }
        
        // Generate JSON-LD
        $graph = [];
        foreach ($this->entities as $entity) {
            $graph[] = $entity->toArray();
        }
        
        $metadata = [
            '@context' => $this->context,
            '@graph' => $graph
        ];
        
        // Save metadata file
        try {
            $json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ROCrateException("JSON encoding failed: " . $e->getMessage());
        }
        
        // !!!
        file_put_contents($target . '/ro-crate-metadata-out.json', $json);
    }

    public function getDescriptor(): Descriptor {
        if (!$this->descriptor) {
            throw new ROCrateException("Metadata descriptor not initialized");
        }
        return $this->descriptor;
    }

    public function getRootDataset(): Dataset {
        if (!$this->rootDataset) {
            throw new ROCrateException("Root dataset not initialized");
        }
        return $this->rootDataset;
    }

    public function getSparqlQueryEngine(): \EasyRdf\Sparql\Client {
        return new \EasyRdf\Sparql\Client($this->graph);
    }

    public function addProfile(string $profile = 'https://w3id.org/ro/crate/1.2', string $about = './'): void {
        $this->descriptor->addProperty('conformsTo', ['@id' => $profile]);
        $this->descriptor->addProperty('about', ['@id' => $about]);
    }
}