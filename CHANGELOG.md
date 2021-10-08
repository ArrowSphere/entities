# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Added automatic tests for PHP 8.1

### Changed

- Changed the way exceptions are thrown while constructing an entity:
  - if some fields are provided but were not described in any annotation, a `NonExistingFieldException` is thrown
  - if some required fields are missing, a `MissingFieldException` is thrown
  - if a class described in an annotation is not found, an `InvalidEntityException` is thrown

## [0.1.0] - 2021-10-06

### Added

- Initialize project with GitHub workflows and all
- Added first version of the `AbstractEntity` and `Property` annotation
- When using PHP >= 7.4, it is possible to add types to properties, which allows in some cases to avoid specifying the type in the `@Property` annotation.

[Unreleased]: https://github.com/ArrowSphere/entities/compare/0.1.0...HEAD
[0.1.0]: https://github.com/ArrowSphere/entities/compare/2d2d56c91df841c14b741771cb8c55a1899b0915...0.1.0
