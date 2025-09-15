# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- This **[CHANGELOG.md](https://github.com/gigascience/ro-crate-php/blob/Alex/CHANGELOG.md)** to document the essential changes of this project.
- Missing thumbnail entity in **[index.php](https://github.com/gigascience/ro-crate-php/blob/Alex/src/index.php)**.
- Usage guide, particularly for using with GigaDB datasets.
- GigaDB example of metadata file and preview using the dataset 102736 in assets directory in the repository.
- PHPCS for coding standard checks (PSR12) through composer.
- Docker-compose configuration for running tests.
- Preliminary HTML render with PHP documentation.
- Additional unit tests for the ROCrate class.
- RO-Crate exception class.
- Partial validation of RO-Crate metadata when saving the manipulated crate.
- Chaining capability for adding and removing entities.
- Inline PHPDoc documentation for main code, i.e. the ROCrate class.
- Basic unit test cases for ROCrate class.
- Contributing documentation.
- Code of conduct document.
- CODEOWNERS file with GigaScience developers as owners.

### Fixed

- Multiple typos in **[Guide.md](https://github.com/gigascience/ro-crate-php/blob/Alex/Guide.md)** and **[README.md](https://github.com/gigascience/ro-crate-php/blob/Alex/README.md)**.
- Minor formatting issues in generated HTML previews.
- String concatenation and variable naming issues.
- PHPCS warnings about coding style.
- PHPDoc inline comment typos.
- ISO 8601 DateTime validation's issues.

### Changed

- Update **[Guide.md](https://github.com/gigascience/ro-crate-php/blob/Alex/Guide.md)** to specify that it is primarily designed for the latest RO-Crate v1.2 but not earlier versions.
- Enhance generated HTML formatting with type hyperlinks to [schema.org](https://schema.org/) definitions as specified in the **[RO-Crate v1.2 context](https://www.researchobject.org/ro-crate/specification/1.2/context.jsonld)**.
- Abstract property-add/remove-pair methods to hide formatting details.
- Replace Person class with a generic class implementation.
- Update composer.json name and license information.
- Improve **[README.md](https://github.com/gigascience/ro-crate-php/blob/Alex/README.md)** with installation section updates and downstream task compatibility.

### Removed

- Person class to be replaced with generic implementation.
- Some unnecessary and development-only comments.

[unreleased]: https://github.com/gigascience/ro-crate-php/tree/Alex