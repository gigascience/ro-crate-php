<?php

namespace ROCrate;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use Exception;
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

use function PHPUnit\Framework\throwException;

/**
 * Stores the structural information of a ro-crate-metadata.json as a crate object
 */
class ROCrate
{
    private string $basePath;
    private array $entities = [];
    private mixed $context = "https://w3id.org/ro/crate/1.2/context";
    private ?Entity $descriptor = null;
    private ?Entity $rootDataset = null;
    private Graph $graph;
    private Client $httpClient;
    private bool $attached = true;
    private bool $preview = false;
    private ?Entity $website = null;
    private array $errors = [];

    /**
     * Constructs a ROCrate instance
     * @param string $directory The directory for reading and writing files
     * @param bool $lE The flag to indicate whether we construct from nothing or reading an existing file
     * @param bool $aF The flag to indicate whether RO-Crate Package is attached
     * @param bool $pF The flag to indicate whether the RO-Crate Website is needed
     */
    public function __construct(string $directory, bool $lE = false, bool $aF = true, bool $pF = false)
    {
        $loadExisting = $lE;
        $attachedFlag = $aF;
        $previewFlag = $pF;

        $this->attached = $attachedFlag;
        $this->preview = $previewFlag;

        $this->basePath = realpath($directory) ?: $directory;
        $this->graph = new Graph();
        $this->httpClient = new Client();

        RdfNamespace::set('rocrate', 'https://w3id.org/ro/crate/1.2');
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
     * Sets the context of the RO-Crate
     * @param mixed $newContext The new context
     * @return ROCrate The crate whose context is updated
     */
    public function setContext(mixed $newContext): ROCrate
    {
        $this->context = $newContext;
        return $this;
    }

    /**
     * Gets the path to the ro-crate metadata file
     * @return string The path to the ro-crate metadata file as a string
     */
    private function getMetadataPath(): string
    {
        return $this->basePath . '/ro-crate-metadata.json';
    }

    /**
     * Initializes a ro-crate instance
     * @return void
     */
    private function initializeNewCrate(): void
    {
        $this->descriptor = new Descriptor();
        $this->addEntity($this->descriptor);

        $this->rootDataset = new Dataset();
        $this->addEntity($this->rootDataset);

        if ($this->preview) {
            $this->website = new class ("ro-crate-preview.html", ["CreativeWork"]) extends ContextualEntity {
                public function toArray(): array
                {
                    return array_merge($this->baseArray(), $this->properties);
                }
            };
            $this->website->addProperty("about", ["@id" => "./"]);
            $this->addEntity($this->website);
        }

        // make values of all properties, i.e. key-value pairs, of each entity to be [...]
        foreach ($this->entities as $entity) {
            foreach (array_keys($entity->getProperties()) as $key) {
                if (is_array($entity->getProperties()[$key])) {
                    $property = $entity->getProperties()[$key];
                    if (array_keys($property) !== range(0, count($property) - 1)) {
                        // if {"@id" : "..."} by checking whether $val is an associative array
                        $entity->addProperty($key, [$entity->getProperties()[$key]]);
                    }
                    // else already [...]
                } else {
                    // literal
                    $entity->addProperty($key, [$entity->getProperties()[$key]]);
                }
            }
        }
    }

    /**
     * Reads and loads the existing ro-crate file as an instance
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return void
     */
    public function loadMetadata(): void
    {
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
        $rootId = './';
        foreach ($json['@graph'] as $entityData) {
            $condtionOne = str_contains($entityData['@id'], "ro-crate-metadata.json");
            $conditionTwo = array_key_exists("conformsTo", $entityData);
            if ($condtionOne && $conditionTwo) {
                $conformsTo = $entityData["conformsTo"]["@id"];
                $rootId = $entityData['about']['@id'];
                $this->addProfile($conformsTo, $rootId);
                continue;
            }
            $this->addEntityFromArray($entityData);
        }

        // Find root dataset
        $this->rootDataset = $this->getEntity($rootId);
        /*
        foreach ($this->entities as $entity) {
            if (in_array('Dataset', $entity->getTypes()) && ($entity->getId() === $rootId)) {
                $this->rootDataset = new Dataset($rootId);
                foreach ($entity->getTypes() as $type) $this->rootDataset->addType($type);
                foreach ($entity->getProperties() as $key => $val) $this->rootDataset->addProperty($key, $val);
                break;
            }
        }*/

        // Find preview if it exists
        $this->website = $this->getEntity("ro-crate-preview.html");
        /*
        if ($this->preview) {
            foreach ($this->entities as $entity) {
                if (in_array('CreativeWork', $entity->getTypes()) && ($entity->getProperty("about")["@id"] === $rootId)
                && (!array_key_exists("conformsTo", $entity->getProperties()))) {
                    $this->website = $this->getEntity("ro-crate-preview.html");
                    $this->website = new class("ro-crate-preview.html", ["CreativeWork"]) extends ContextualEntity {
                        public function toArray(): array {
                            return array_merge($this->baseArray(), $this->properties);
                        }
                    };
                    foreach ($entity->getTypes() as $type) $this->website->addType($type);
                    foreach ($entity->getProperties() as $key => $val) $this->website->addProperty($key, $val);
                    break;
                }
            }
        }*/

        if (!$this->descriptor) {
            throw new ROCrateException("Metadata descriptor not found in crate");
        }

        if (!$this->rootDataset) {
            throw new ROCrateException("Root dataset not found in crate");
        }

        // make values of all properties, i.e. key-value pairs, of each entity to be [...]
        foreach ($this->entities as $entity) {
            foreach (array_keys($entity->getProperties()) as $key) {
                if (is_array($entity->getProperties()[$key])) {
                    $property = $entity->getProperties()[$key];
                    if (array_keys($property) !== range(0, count($property) - 1)) {
                        // if {"@id" : "..."} by checking whether $val is an associative array
                        $entity->addProperty($key, [$entity->getProperties()[$key]]);
                    }
                    // else already [...]
                } else {
                    // literal
                    $entity->addProperty($key, [$entity->getProperties()[$key]]);
                }
            }
        }
    }

    /**
     * Adds entities to the crate given an array
     * @param array $data The given array
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return ROCrate The crate to which the entity is added
     */
    private function addEntityFromArray(array $data): ROCrate
    {
        $id = $data['@id'] ?? null;
        $types = (array)($data['@type'] ?? []);

        if (!$id) {
            throw new ROCrateException("Entity missing @id property");
        }

        if (empty($types)) {
            throw new ROCrateException("Entity missing @type property: $id");
        }

        $entity = $this->createGenericEntity($id, $types);

        // Set properties
        foreach ($data as $key => $value) {
            if (!in_array($key, ['@id', '@type'])) {
                $entity->addProperty($key, $value);
            }
        }

        $this->addEntity($entity);

        return $this;
    }

    /**
     * Creates a generic entity
     * @param string $id The ID of the entity
     * @param array $types The type(s) of the entity as an array
     * @return Entity The entity instance
     */
    public function createGenericEntity(string $id, array $types): Entity
    {
        return new class ($id, $types) extends Entity {
            public function toArray(): array
            {
                return array_merge($this->baseArray(), $this->properties);
            }
        };
    }

    /**
     * Adds an entity to the crate given an entity instacne
     * @param Entity $entity The given entity instacne
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return ROCrate The crate to which the entity is added
     */
    public function addEntity(Entity $entity): ROCrate
    {
        $id = $entity->getId();

        if (isset($this->entities[$id])) {
            throw new ROCrateException("Entity with ID $id already exists");
        }

        $entity->setCrate($this);
        $this->entities[$id] = $entity;
        return $this;
    }

    /**
     * Gets an entity instance with its ID from the crate
     * @param string $id The ID of the entity instacne to retrieve
     * @return mixed The entity instacne or null if the ID is invalid
     */
    public function getEntity(string $id): ?Entity
    {
        return $this->entities[$id] ?? null;
    }

    /**
     * Removes an entity from the crate with its ID
     * @param string $id The ID of the entity to remove
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return ROCrate The crate from which the entity is deleted
     */
    public function removeEntity(string $id): ROCrate
    {
        if (!isset($this->entities[$id])) {
            throw new ROCrateException("Entity not found: $id");
        }
        unset($this->entities[$id]);
        return $this;
    }

    /**
     * Validates the crate before saving with minimal checks only
     * @return string[] The error(s) or issue(s) found during the validation as a string array
     */
    public function validate(): array
    {
        $errors = [];

        // MUST Checks
        // RO-Crate Structure

        // 1. Metadata descriptor check
        if (!$this->descriptor) {
            $errors[] = "Missing metadata descriptor";
        }

        // 2. Root dataset check
        if (!$this->rootDataset) {
            $errors[] = "Missing root dataset";
        }

        // Metadata of the RO-Crate

        // 1. @id check
        foreach ($this->entities as $entity) {
            if (!is_string($entity->getId())) {
                $errors[] = "There is an entity without an id.";
            }
        }

        // 2. @type check
        foreach ($this->entities as $entity) {
            if ($entity->getTypes() === []) {
                $errors[] = "There is an entity without a type using id: " . $entity->getId() . ".";
            }
        }

        // 3. entity property references to other entities using {"@id": "..."} check
        // newly created property managed using addPropertyPair and removePropertyPair automatically satisfy
        // the only exceptions that require manual manipulation are encodingFormat
        // due to mixed use of literal and reference
        // and context with extra terms
        // old property imported either:
        // case 1: reset it using removeProperty and manage using ...Pair
        // case 2: the developers have to be cautious for any change

        // 4. flat @graph list is enforced in the implementation

        // Root Data Entity

        // 1. @id value of the descriptor has to be "ro-crate-metadata.json"
        // or "ro-crate-metadata.jsonld" (legacy from v1.0 or before) check
        // This is needed even if the actual metadata file maybe absent or has a prefix in detached package.
        $conditionOne = (strcmp($this->descriptor->getId(), "ro-crate-metadata.json") !== 0);
        $conditionTwo = (strcmp($this->descriptor->getId(), "ro-crate-metadata.jsonld") !== 0);
        if ($conditionOne && $conditionTwo) {
            $errors[] = "The descriptor's id is invalid.";
        }

        // 2. @type of the descriptor has to be CreativeWork check
        if (count($this->descriptor->getTypes()) == 1) {
            if (strcmp($this->descriptor->getTypes()[0], "CreativeWork") !== 0) {
                $errors[] = "The descriptor's type is invalid.";
            }
        } else {
            $errors[] = "The descriptor's type is invalid.";
        }

        // 3. The descriptor has an about property and it references the Root Data Entity's @id check
        if (array_key_exists("about", $this->descriptor->getProperties())) {
            $conditionOne = (is_array($this->getDescriptor()->getProperty("about")));
            $conditionTwo = (strcmp($this->descriptor->getProperty("about")['@id'], $this->rootDataset->getId()) !== 0);
            if ($conditionOne && $conditionTwo) {
                $errors[] = "The descriptor's about property is invalid.";
            }
        } else {
            $errors[] = "The descriptor does not have an about property.";
        }

        // 4. One of the root data entity's type(s) has to be Dataset check
        if (!in_array("Dataset", $this->rootDataset->getTypes())) {
            $errors[] = "The root data entity's type is invalid.";
        }

        // 5. The root data entity has to have the property name check
        if (!array_key_exists("name", $this->rootDataset->getProperties())) {
            $errors[] = "The root data entity does not have a name property.";
        }

        // 6. The root data entity has to have the property description check
        if (!array_key_exists("description", $this->rootDataset->getProperties())) {
            $errors[] = "The root data entity does not have a description property.";
        }

        // 7. The root data entity has to have the property datePublished check, and the property
        // has to be a string in ISO 8601 date format
        if (!array_key_exists("datePublished", $this->rootDataset->getProperties())) {
            $errors[] = "The root data entity does not have a datePublished property.";
        } elseif (is_string($this->rootDataset->getProperty("datePublished"))) {
            if (!ROCrate::isValidISO8601Date($this->rootDataset->getProperty("datePublished"))) {
                $errors[] = "The root data entity's datePublished property is not in ISO 8601 date format.";
            }
        } else {
            $errors[] = "The root data entity's datePublished property is not a string.";
        }

        // 8. The root data entity has to have the property license check
        if (!array_key_exists("license", $this->rootDataset->getProperties())) {
            $errors[] = "The root data entity does not have a license property.";
        }

        // Data Entities

        // 1. all file and folders as data entities have to be indirectly or directly linked to the root data
        // entity via hasPart
        // This is not possible to check for detached package without knowing how to access the details.
        // For attached package, since contextual entity of type Dataset with # local identifier to collectively
        // describe a bunch of files is possible and it has no strict criteria for its use, it is also not
        // possible to check.

        // 2. a file as a data entity has file as one of its type(s)
        // This satisfies if it is created using new File
        // This cannot be strictly enforced for the same reason as above

        // 3. @id have to be valid URI references
        //!!!
        //foreach($this->entities as $entity) {
        //    if (ROCrate::isValidUri($entity->getId(), false)) {
        //        $errors[] = "The entity's id (" . $entity->getId() . ") is not a valid URI.";
        //    }
        //}

        // 4. file data entity @id relative or absolute URI

        // 5. Dataset data entity has Dataset as one of its type(s)
        // This satisfies if it is created using new Dataset
        // Difficult to strictly enforce for the same reason as above

        // 6. Dataset data entity @id has to resolve to a directory present in the crate root for an attached package
        // Difficult to strictly enforce

        // 7.

        // 8.

        // Contextual Entities

        // 1. Contextual data entity as a standalone object
        // automatically enforced by treating things as entity instances

        // 2. no repeated use of @id
        $idArray = [];
        foreach ($this->entities as $entity) {
            $idArray[] = $entity->getId();
        }
        $uniqueIdArray = array_unique($idArray);
        if (sizeof($idArray) !== sizeof($uniqueIdArray)) {
            $errors[] = "There are multiple entities using the same @id value.";
        }

        // 3. The crate metadata file needs a URL as the @id of a publication using citation property if we
        // want to associate a publication with the dataset
        // It relies on disciplined use.

        // 4. subjects & keywords
        // It relies on disciplined use.

        // 5. include thumbnail if have
        // It relies on disciplined use.

        // 6. put thumbnail in the bagit manifest if it is present and it is a bagged ro-crate
        // It releis on disciplined use.

        // Provenance of entities

        // 1. A curation action, i.e. type of CreateAction or UpdateAction, has at least one object check
        foreach ($this->entities as $entity) {
            if (in_array("CreateAction", $entity->getTypes()) || in_array("UpdateAction", $entity->getTypes())) {
                if (!array_key_exists("object", $entity->getProperties())) {
                    $errors[] = "There is no object property for a curation action.";
                }
            }
        }

        // 2. An action's endTime has to be in ISO 8601 date format if this property is present, same for startTime
        foreach ($this->entities as $entity) {
            if (in_array("CreateAction", $entity->getTypes()) || in_array("UpdateAction", $entity->getTypes())) {
                // startTime
                if (!in_array("startTime", $entity->getProperties())) {
                    continue;
                }
                if (is_string($entity->getProperty("startTime"))) {
                    if (!ROCrate::isValidISO8601Date($entity->getProperty("startTime"))) {
                        $errors[] = "An action's startTime property is not in ISO 8601 date format.";
                    }
                } else {
                    $errors[] = "An action's startTime property is not in ISO 8601 date format.";
                }

                // endTime
                if (!in_array("endTime", $entity->getProperties())) {
                    continue;
                }
                if (is_string($entity->getProperty("endTime"))) {
                    if (!ROCrate::isValidISO8601Date($entity->getProperty("endTime"))) {
                        $errors[] = "An action's endTime property is not in ISO 8601 date format.";
                    }
                } else {
                    $errors[] = "An action's endTime property is not in ISO 8601 date format.";
                }
            }
        }

        // 3. if an action has an actionStatus property, the property has to be ActiveActionStatus,
        // CompletedActionStatus, FailedActionStatus or PotentialActionStatus of type ActionStatusType
        foreach ($this->entities as $entity) {
            if (in_array("CreateAction", $entity->getTypes()) || in_array("UpdateAction", $entity->getTypes())) {
                if (!array_key_exists("actionStatus", $entity->getProperties())) {
                    continue;
                }
                $actionStatus = $entity->getProperty("actionStatus")["@id"];
                if (strcmp($actionStatus, "http://schema.org/ActiveActionStatus") == 0) {
                    continue;
                }
                if (strcmp($actionStatus, "https://schema.org/ActiveActionStatus") == 0) {
                    continue;
                }
                if (strcmp($actionStatus, "http://schema.org/CompletedActionStatus") == 0) {
                    continue;
                }
                if (strcmp($actionStatus, "https://schema.org/CompletedActionStatus") == 0) {
                    continue;
                }
                if (strcmp($actionStatus, "http://schema.org/FailedActionStatus") == 0) {
                    continue;
                }
                if (strcmp($actionStatus, "https://schema.org/FailedActionStatus") == 0) {
                    continue;
                }
                if (strcmp($actionStatus, "http://schema.org/PotentialActionStatus") == 0) {
                    continue;
                }
                if (strcmp($actionStatus, "https://schema.org/PotentialActionStatus") == 0) {
                    continue;
                }
                $errors[] = "An action's actionStatus property is invalid.";
            }
        }

        // Profiles

        // 1. The profile URI, i.e. the reference of comformsTo property of the root data entity, resolves
        // to a human-readable profile description
        // It relies on disciplined use.

        // 2. If the root data entity conforms to a profile, it has to be a contextual entity having Profile
        // as one of its type(s), similarly for multiple profiles
        if (array_key_exists("conformsTo", $this->rootDataset->getProperties())) {
            if (is_array($this->rootDataset->getProperty("conformsTo"))) {
                foreach ($this->rootDataset->getProperty("conformsTo") as $profile) {
                    $flag = true;
                    foreach ($this->entities as $entity) {
                        if (strcmp($entity->getId(), $profile["@id"]) == 0) {
                            if (in_array("Profile", $entity->getTypes())) {
                                $flag = false;
                                break;
                            }
                        }
                    }
                    if ($flag) {
                        $errors[] = "The contextual entity for a profile is missing.";
                    }
                }
            } else {
                $flag = true;
                foreach ($this->entities as $entity) {
                    if (strcmp($entity->getId(), $this->rootDataset->getProperty("conformsTo")["@id"]) == 0) {
                        if (in_array("Profile", $entity->getTypes())) {
                            $flag = false;
                            break;
                        }
                    }
                }
                if ($flag) {
                    $errors[] = "The contextual entity for the profile is missing.";
                }
            }
        }

        // 3. if it is a profile crate, it has Profile as one of its type(s)
        // It relies on disciplined use.

        // 4. if it is a profile crate, its hasPart references the human-readable profile description as a data entity,
        // and this data entity has to reference the absolute URI of the root data entity of the profile crate
        // using the about property
        // It relies on disciplined use.

        // 5. any terms defined in the profile has to be used as full URIs matching @id
        // or mapped to these URIs from the conforming crate's
        // @context in the conforming crate.
        // It relies on disciplined use.

        // 6. An entity representing a JSON-LD context has to have an encodingFormat of application/ld+json and
        // has an absolute URI as @id retrievable as JSON-LD directly or indirectly
        // It relies on disciplined use.

        // Workflows and scripts

        // 1. script and workflow type, id and name
        // It relies on disciplined use.

        // 2. If a contextual entity has type ComputerLanguage and/or SoftwareApplication,\
        // it has a name, url and version
        foreach ($this->entities as $entity) {
            $conditionOne = in_array("ComputerLanguage", $entity->getTypes());
            $conditionTwo = in_array("SoftwareApplication", $entity->getTypes());
            if ($conditionOne || $conditionTwo) {
                if (!array_key_exists("name", $entity->getProperties())) {
                    $errors[] = "The name property for the contextual entity of type ComputerLanguage 
                    and/or SoftwareApplication is missing.";
                }
                if (!array_key_exists("url", $entity->getProperties())) {
                    $errors[] = "The url property for the contextual entity of type ComputerLanguage 
                    and/or SoftwareApplication is missing.";
                }
                if (!array_key_exists("version", $entity->getProperties())) {
                    $errors[] = "The version property for the contextual entity of type ComputerLanguage 
                    and/or SoftwareApplication is missing.";
                }
            }
        }

        // 3. complying with the Bioschemas computational workflow profile
        // Difficult to check and less generic to check for a particular profile

        // 4. complying with the Bioschemas formal parameter profile
        // same as above

        // Changelog

        // 1. The descriptor has conformsTo to indicate RO-Crate version
        if (!array_key_exists("conformsTo", $this->descriptor->getProperties())) {
            $errors[] = "The conformsTo property for the descriptor is missing.";
        }

        // Handling relative URI references

        // 1. When we have to parse as RDF, if ro-crate-metadata.json is not recognised, we rename it to jsonld
        // it relies on disciplined use

        // Implementation notes

        // 1. Bagit enforcement
        // It relies on disciplined use.

        // RO-Crate JSON-LD

        // 1. / and escape character care (should: utf-8 encoded, i.e. #, space, ... encoded with %)
        // It relies on disiplined use.

        // 2. if (present) generate ro-crate website, use sameAs for the term.
        // it relies on disiplined use.

        // 3. if there is extra / ad-hoc term / vocab, put them in context.
        // it relies on disiplined use.

        return $errors;
    }

    /**
     * Saves the crate object as a ro-crate metadata file
     * @param mixed $path The path to save the crate object if the default base path is not used
     * @param string $prefix The prefix of the metadata file, needed for a detached ro-crate package
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return void
     */
    public function save(?string $path = null, string $prefix = ""): void
    {
        $this->errors = [];

        // make values of all properties, i.e. key-value pairs, of each entity to be without [...] if there
        // is only a single literal or {"@id" : "..."}
        foreach ($this->entities as $entity) {
            foreach (array_keys($entity->getProperties()) as $key) {
                if (strcmp($key, "hasPart") == 0) {
                    continue;
                }
                // safety check if $val is an array
                if (is_array($entity->getProperty($key))) {
                    if (!array_key_exists('@id', $entity->getProperty($key))) {
                        // safety check if $val is not an associative array
                        if (count($entity->getProperty($key)) === 1) {
                            // there is only a single item
                            $entity->addProperty($key, $entity->getProperty($key)[0]);
                            //$this->printNestedArray($this->descriptor->getProperties());
                        }
                    }
                }
            }
        }


        if (!$this->attached) {
            if (strcmp($prefix, "") == 0) {
                throw new ROCrateException("The prefix cannot be empty for a detached RO-Crate Package.");
            }
        }

        $this->errors = $this->validate();
        if (!($this->errors === [])) {
            throw new ROCrateException("Validation before saving failed.");
        }

        $target = $path ? realpath($path) : $this->basePath;

        if (!$target) {
            throw new ROCrateException("Invalid target directory: $path");
        }

        // Ensure metadata directory exists
        if (!is_dir($target) && !mkdir($target, 0755, true)) {
            throw new ROCrateException("Failed to create directory: $target");
        }

        // Generate JSON-LD
        $rootId = "";
        $first = [];
        $second = [];
        $last = [];

        $graph = [];
        foreach ($this->entities as $entity) {
            $conditionOne = str_contains($entity->getId(), "ro-crate-metadata.json");
            $conditionTwo = array_key_exists("conformsTo", $entity->getProperties());
            if ($conditionOne && $conditionTwo) {
                $rootId = $entity->getProperty("about")["@id"];
                $first[] = $entity->toArray();
                $key = array_search($entity, $this->entities, true);
                unset($this->entities[$key]);
                break;
            }
        }

        foreach ($this->entities as $entity) {
            $conditionOne = in_array('CreativeWork', $entity->getTypes());
            $conditionTwo = (!array_key_exists("conformsTo", $entity->getProperties()));
            if ($conditionOne && $conditionTwo) {
                if (!array_key_exists("about", $entity->getProperties())) {
                    continue;
                }
                if (!($entity->getProperty("about")["@id"] === $rootId)) {
                    continue;
                }

                $first[] = $entity->toArray();
                $key = array_search($entity, $this->entities, true);
                unset($this->entities[$key]);
                break;
            }
        }

        foreach ($this->entities as $entity) {
            if (in_array('Dataset', $entity->getTypes()) && ($entity->getId() === $rootId)) {
                $first[] = $entity->toArray();
                continue;
            }

            if (in_array("Dataset", $entity->getTypes()) && (strcmp($entity->getId()[0], '#') !== 0)) {
                $second[] = $entity->toArray();
                continue;
            } elseif (in_array("File", $entity->getTypes()) && (strcmp($entity->getId()[0], '#') !== 0)) {
                $second[] = $entity->toArray();
                continue;
            }

            $last[] = $entity->toArray();
        }

        $graph = array_merge($graph, $first);
        $graph = array_merge($graph, $second);
        ;
        $graph = array_merge($graph, $last);
        ;

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
        //!!!
        if (strcmp($prefix, "") == 0) {
            file_put_contents($target . '/ro-crate-metadata-out.json', $json);
        } else {
            file_put_contents($target . '/' . $prefix . '-ro-crate-metadata-out.json', $json);
        }
    }

    /**
     * Gets the metadata descriptor instance from the crate
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return Entity|null The metadata descriptor instance or null if the instance does not exist
     */
    public function getDescriptor(): Entity
    {
        if (!$this->descriptor) {
            throw new ROCrateException("Metadata descriptor not initialized");
        }
        return $this->descriptor;
    }

    /**
     * Gets the root data entity instance from the crate
     * @throws \Exceptions\ROCrateException Exceptions with specific messages to indicate possible errors
     * @return Entity|null The root data entity instance or null if the instance does not exist
     */
    public function getRootDataset(): Entity
    {
        if (!$this->rootDataset) {
            throw new ROCrateException("Root dataset not initialized");
        }
        return $this->rootDataset;
    }

    /**
     * Adds a metadata descriptor to the crate
     * @param string $profile The ro-crate standard used with the specific version
     * @param string $about The dataset to describe
     * @return void
     */
    public function addProfile(string $profile = 'https://w3id.org/ro/crate/1.2', string $about = './'): void
    {
        $this->descriptor->addProperty('conformsTo', ['@id' => $profile]);
        $this->descriptor->addProperty('about', ['@id' => $about]);
    }

    /**
     * Sets the base path
     * @param string $basePath The base path as a string
     * @return void
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * Tells if a text is valid according to ISO 8601 standard and allows only up-to-day, up-to-month
     * and up-to-day specification for more flexibility and compatibility
     * @param string $dateString The text to be validated
     * @return bool The flag that indicates the result of validation
     */
    public static function isValidISO8601Date(string $dateString): bool
    {

        $MM = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];

        if (strcmp(substr($dateString, 0, 1), "-") == 0) {
            $dateString = substr($dateString, 1);
        }

        $year = "";
        $month = "";
        $day = "";

        // It does not check for the presence of day 29, 30 and 31 in the particular month for day-only,
        // month-only and year-only cases
        $flag = true;
        switch (strlen($dateString)) {
            case 4:
                $year = $dateString;
                if (!ctype_digit($year)) {
                    $flag = false;
                }
                break;
            case 7:
                if (strcmp(substr($dateString, 4, 1), "-") !== 0) {
                    $flag = false;
                }
                $year = substr($dateString, 0, 4);
                $month = substr($dateString, 5, 2);
                if (!ctype_digit($year)) {
                    $flag = false;
                } elseif (!in_array($month, $MM)) {
                    $flag = false;
                }
                break;
            case 10:
                if (strcmp(substr($dateString, 4, 1), "-") !== 0) {
                    $flag = false;
                } elseif (strcmp(substr($dateString, 7, 1), "-") !== 0) {
                    $flag = false;
                }
                $year = substr($dateString, 0, 4);
                $month = substr($dateString, 5, 2);
                $day = substr($dateString, 8, 2);
                if (!ctype_digit($year)) {
                    $flag = false;
                } elseif (!in_array($month, $MM)) {
                    $flag = false;
                } elseif (!in_array($day, $MM)) {
                    if (!ctype_digit($year)) {
                        $flag = false;
                    } elseif (((int)$day < 13) || ((int)$day > 31)) {
                        $flag = false;
                    }
                }
                break;
            default:
                $flag = false;
                break;
        }
        if ($flag) {
            return true;
        }


        // Regex to match the structure: optional minus, date, time, and optional timezone
        $pattern = '/^(-)?(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|([+-]\d{2}:\d{2}))?$/';
        if (!preg_match($pattern, $dateString, $matches)) {
            return false;
        }

        // Extract components from matches
        $hasMinus = ($matches[1] === '-');
        $yearStr = $matches[2];
        $month = $matches[3];
        $day = $matches[4];
        $hour = $matches[5];
        $minute = $matches[6];
        $second = $matches[7];
        $timezone = $matches[8] ?? '';

        // Convert year to integer (accounting for optional minus)
        $year = (int)$yearStr;
        if ($hasMinus) {
            $year = -$year;
        }

        // Validate month (01-12)
        $monthInt = (int)$month;
        if ($monthInt < 1 || $monthInt > 12) {
            return false;
        }

        // Validate day based on month/year
        $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        // Adjust February days for leap years
        if ($monthInt === 2) {
            $isLeap = ($year % 4 === 0) && ($year % 100 !== 0 || $year % 400 === 0);
            $daysInMonth[1] = $isLeap ? 29 : 28;
        }
        $dayInt = (int)$day;
        $maxDay = $daysInMonth[$monthInt - 1];
        if ($dayInt < 1 || $dayInt > $maxDay) {
            return false;
        }

        // Validate time (00-23 for hour, 00-59 for minute/second)
        if ($hour < '00' || $hour > '23') {
            return false;
        }
        if ($minute < '00' || $minute > '59') {
            return false;
        }
        if ($second < '00' || $second > '59') {
            return false;
        }

        // Validate timezone if present
        if ($timezone !== '') {
            if ($timezone === 'Z') {
                return true; // UTC is valid
            }
            // Check offset structure: [+-]hh:mm
            $offsetParts = explode(':', $timezone);
            if (count($offsetParts) !== 2) {
                return false;
            }
            $offsetSignPart = $offsetParts[0];
            $offsetMinute = $offsetParts[1];
            // Validate sign and hour part (00-23)
            $sign = $offsetSignPart[0];
            $offsetHour = substr($offsetSignPart, 1);
            if (
                ($sign !== '+' && $sign !== '-') ||
                $offsetHour < '00' || $offsetHour > '23'
            ) {
                return false;
            }
            // Validate minute part (00-59)
            if ($offsetMinute < '00' || $offsetMinute > '59') {
                return false;
            }
        }

        return true;
    }

    /**
     * Tells if a string is a valid uri
     * @param string $uri The uri string to be examined
     * @param bool $absoluteOnly The flag to indicate whether only absolute uri is allowed
     * @return bool The flag that indicates the validation result
     */
    public static function isValidUri(string $uri, bool $absoluteOnly = true): bool
    {
        // Validate absolute URI (requires a scheme like http, ftp, etc.)
        if (filter_var($uri, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        // If only absolute URIs are allowed, return false here
        if ($absoluteOnly) {
            return false;
        }

        // Validate relative URI: checks for allowed characters and proper percent-encoding
        $pattern = '/^([a-zA-Z0-9._~!$&\'()*+,;=:@\/?#\[\]-]|%[0-9a-fA-F]{2})*$/';
        return (bool) preg_match($pattern, $uri);
    }

    /**
     * Tells if a string is a valid url
     * @param string $url The url string to be exmained
     * @return bool The flag that indicates the checking result
     */
    public static function isValidUrl(string $url): bool
    {
        // Validate URL structure using filter_var
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // Ensure the URL has a valid scheme
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return $scheme !== null;
    }

    /**
     * Lays out and prints the structure of a nested array for debugging
     * @param mixed $array The array to be examined
     * @param mixed $indent The indentation
     * @return void
     */
    public function printNestedArray($array, $indent = 0): void
    {
        foreach ($array as $key => $value) {
            // Add indentation for better readability of nested levels
            echo str_repeat("  ", $indent);

            if (is_array($value)) {
                echo "Key: " . $key . " (Array):\n";
                $this->printNestedArray($value, $indent + 1); // Recursively call for nested arrays
            } else {
                echo "Key: " . $key . ", Value: " . $value . "\n";
            }
        }
    }

    /**
     * Returns the validation error(s)
     * @return array The array of all the error message(s)
     */
    public function showErrors(): array
    {
        return $this->errors;
    }

    /**
     * Saves with the error message explicitly returned for further examination
     * @return array The array consisting of error messages
     */
    public function saveWithErrorMessage(): array
    {
        $errors = [];

        try {
            $this->save();
        } catch (Exception $e) {
            $errors = $this->showErrors();
        }

        return $errors;
    }
}
