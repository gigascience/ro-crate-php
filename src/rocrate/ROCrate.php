<?php

namespace ROCrate;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Exceptions\ROCrateException;
use ROCrate\Entity;
use ROCrate\DataEntity;
use ROCrate\Dataset;
use ROCrate\File;
use ROCrate\Descriptor;
use ROCrate\Person;

/**
 * Stores the structural information of a ro-crate-metadata.json as a crate object
 */
class ROCrate {
    private string $basePath;
    private array $entities = [];
    private string $context = "https://w3id.org/ro/crate/1.2/context";
    private ?Descriptor $descriptor = null;
    private ?Dataset $rootDataset = null;
    private Graph $graph;
    private Client $httpClient;

    /**
     * Constructs a ROCrate instance
     * @param string $directory The directory for reading and writing files
     * @param bool $loadExisting The flag to indicate whether we construct from nothing or reading an existing file
     */
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

    /**
     * Gets the path to the ro-crate metadata file
     * @return string The path to the ro-crate metadata file as a string
     */
    private function getMetadataPath(): string {
        return $this->basePath . '/ro-crate-metadata.json';
    }

    /**
     * Initializes a ro-crate instance
     * @return void
     */
    private function initializeNewCrate(): void {
        $this->descriptor = new Descriptor();
        $this->addEntity($this->descriptor);

        $this->rootDataset = new Dataset();
        $this->addEntity($this->rootDataset);
    }

    /**
     * Reads and loads the existing ro-crate file as an instance
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return void
     */
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

        $this->descriptor = new Descriptor();
        $this->addEntity($this->descriptor);
        $this->addProfile();
        
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

    /**
     * Adds entities to the crate given an array
     * @param array $data The given array
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return void
     */
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
            in_array('Person', $types) => $this->createGenericEntity($id, ['Person'])->addProperty('name', $data['name'] ?? 'Unknown'),
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

    /**
     * Creates a file entity
     * @param string $id The ID of the file entity
     * @param array $data The attributes of the file entity
     * @return File The file entity instacne
     */
    private function createFileEntity(string $id, array $data): File {
        $source = null;
        
        // Resolve local file path
        if (isset($data['contentSize'])) $source = $this->basePath . '/' . ltrim($id, '/');
        
        return new File($id, $source);
    }

    /**
     * Creates a generic entity
     * @param string $id The ID of the entity
     * @param array $types The type(s) of the entity as an array
     * @return Entity The entity instance
     */
    public function createGenericEntity(string $id, array $types): Entity {
        return new class($id, $types) extends Entity {
            public function toArray(): array {
                return array_merge($this->baseArray(), $this->properties);
            }
        };
    }

    /**
     * Adds an entity to the crate given an entity instacne
     * @param Entity $entity The given entity instacne
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return void
     */
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

    /**
     * Gets an entity instance with its ID from the crate
     * @param string $id The ID of the entity instacne to retrieve
     * @return ?Entity The entity instacne or null if the ID is invalid
     */
    public function getEntity(string $id): ?Entity {
        return $this->entities[$id] ?? null;
    }

    /**
     * Removes an entity from the crate with its ID
     * @param string $id The ID of the entity to remove
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return void
     */
    public function removeEntity(string $id): void {
        if (!isset($this->entities[$id])) {
            throw new ROCrateException("Entity not found: $id");
        }
        
        // Remove from RDF graph !!!
        //$this->graph->deleteResource($this->graph->resource($id));
        //print("The removal of a resource from the RDF graph is not implemented.");
        unset($this->entities[$id]);
    }

    //!!! not proper
    /**
     * Adds a file entity to the crate
     * @param string $source The path to the file
     * @param mixed $destPath The destination path
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return File The file entity instance of the file entity added to the crate
     */
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

    // Directory is not dataset !!! need sep context from data !!! remote need a class for web-based? apart from file directory
    /**
     * Adds a directory entity to the crate
     * @param string $path The path to the directory
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return Dataset The directory entity instance of the directory entity added to the crate
     */
    public function addDirectory(string $path): Dataset {
        $fullPath = $this->basePath . '/' . trim($path, '/');
        
        if (!file_exists($fullPath) && !mkdir($fullPath, 0755, true)) {
            throw new ROCrateException("Failed to create directory: $fullPath");
        }
        
        $dataset = new Dataset($path);
        $this->addEntity($dataset);
        return $dataset;
    }

    /**
     * Adds a remote entity to the crate
     * @param string $url
     * @throws \Exceptions\ROCrateException
     * @return Entity
     */
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

    /**
     * Validates the crate before saving
     * @return string[] The error(s) or issue(s) found during the validation as a string array
     */
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

    /**
     * Saves the crate object as a ro-crate metadata file
     * @param mixed $path The path to save the crate object if the default base path is not used
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return void
     */
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

    /**
     * Gets the metadata descriptor instance from the crate
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return Descriptor|null The metadata descriptor instance or null if the instance does not exist
     */
    public function getDescriptor(): Descriptor {
        if (!$this->descriptor) {
            throw new ROCrateException("Metadata descriptor not initialized");
        }
        return $this->descriptor;
    }

    /**
     * Gets the root data entity instance from the crate
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return Dataset|null The root data entity instance or null if the instance does not exist
     */
    public function getRootDataset(): Dataset {
        if (!$this->rootDataset) {
            throw new ROCrateException("Root dataset not initialized");
        }
        return $this->rootDataset;
    }

    //!!! not useful at least for now
    /**
     * Gets the SQL engine
     * @return \EasyRdf\Sparql\Client The SQL engine
     */
    public function getSparqlQueryEngine(): \EasyRdf\Sparql\Client {
        return new \EasyRdf\Sparql\Client($this->graph);
    }

    /**
     * Adds a metadata descriptor to the crate
     * @param string $profile The ro-crate standard used with the specific version
     * @param string $about The dataset to describe
     * @return void
     */
    public function addProfile(string $profile = 'https://w3id.org/ro/crate/1.2', string $about = './'): void {
        $this->descriptor->addProperty('conformsTo', ['@id' => $profile]);
        $this->descriptor->addProperty('about', ['@id' => $about]);
    }

    /**
     * Sets the base path, particularly useful for remote dataset to describe
     * @param string $basePath The base path as a string
     * @return void
     */
    public function setBasePath(string $basePath): void 
    {
        $this->basePath = $basePath;
    }

    /*
    public function importFromZip(string $zipPath): void {
        $zip = new \ZipArchive();
        
        if ($zip->open($zipPath) !== true) {
            throw new ROCrateException("Failed to open ZIP file: $zipPath");
        }
        
        $zip->extractTo($this->basePath);
        $zip->close();
        $this->loadMetadata();
    }

    public function exportToZip(string $outputPath): void {
        $zip = new \ZipArchive();
        
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new ROCrateException("Failed to create ZIP file: $outputPath");
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($this->basePath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();
    }*/
}