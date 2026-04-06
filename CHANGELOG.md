# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)

## [Unreleased]


## [06/04/2026]
### Added
- Full implementation of the development agent ecosystem (#FRT-7)
- Defined and standardized the agent handoff workflow (`planner -> implementer -> guardian -> release-manager`) 
- Implement MCP protocols using DigitalOcean, Github, Supabase and Laravel services (#FRT-7)
- Added specialized skills for Laravel/PHP, PHPUnit, PostgreSQL, agent governance, and iterative anti (#FRT-7)
- Integrated Laravel AI runtime support (`laravel/ai`) with provider configuration in `config/ai.php` (#FRT-7)
- Audience module MVP: persistence, models, factories, events, jobs, listeners, HTTP endpoints, and event-driven integration (#FRT-9)
- Feature and integration tests for subscriber import, duplicate/invalid handling, and event/listener flows (#FRT-9)
- Updated agent/AI operational documentation (`AGENTS.md`, `copilot-instructions.md`, `llms.txt`, `llms-full.txt`) (#FRT-7)
- Fixed authentication bug in `WorkspaceObserver` that prevented correct event dispatch on workspace creation (FRT-8)
- Validated HTTP and integration test coverage for workspace and invitation flows (FRT-8)
- Confirmed no regressions or module boundary violations after the fix (FRT-8)

## [07/02/2026]
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
