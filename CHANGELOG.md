# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [08/02/206]

### Added
- Run Laravel empty project without started-kit (#FRT-0)
- Enviroment development into a Dev Container (#FRT-1)
- Internachi package used for create DDA system (#FRT-2)
- Unit test for the identity module  (#FRT-6)
- Identity models, migrations, factories implemented (#FRT-3)
- Activity models, migrations, factories, serviceProvider implemented. Tinker and PhpUnit test passed (#FRT-4)
- Implemented Event-Driven Architecture for decoupled inter-module communication between Identity and Activity domains (#FRT-5)
- Added domain events: `UserRegistered`, `UserEmailVerified`, `WorkspaceCreated`, and `MembershipCreated` (#FRT-5)
- Created Eloquent observers to automatically dispatch domain events on model lifecycle changes (FRT-5)

### Changed
- Refactored module communication from direct dependencies to event-based messaging (#FRT-5)
- Identity module now emits events without any knowledge of Activity module (full decoupling) (#FRT%)

### Fixed
- Migrations, models, factories of the Identity and Activity domains that violate the principle of modularity  DDD (#FRT-6)
- Indexes named in identity models (#FRT-6)
