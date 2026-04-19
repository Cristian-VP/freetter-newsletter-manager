# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)

## [Unreleased]
### Added
- Publishing Home Feed (`GET /home`) with recent posts from other authors, a 30-item limit, and like metrics (`likes_count`, `liked_by_me`) (#FRT-14)
- Demo seeder to populate the initial feed (`HomeFeedDemoSeeder`) (#FRT-14)
- Visual/UI references in `.context/images_references` to guide upcoming design iterations (#FRT-14)
- Automatic synchronisation of the authentication modal after sending a magic link (#FRT-15)
- `/auth/session-status` endpoint for session polling from the frontend and synchronisation after logging in via a magic link (#FRT-15)
### Changed
- The `home` route was moved under Publishing module ownership, and the duplicate global route definition was removed (#FRT-14)
- The Magic Link flow now redirects to Publishing Home (`route('home')`) after authentication (#FRT-14)
- 7-day persistent session policy for inactivity (similar to Substack) (#FRT-15)
- Updated modular Inertia page resolution to improve module page loading (#FRT-14)
### Fixed
- Fixed the issue where a double tab appeared after logging in via a magic link (#FRT-15)
- Fixed the confusion regarding the CTA and authentication modal mode (#FRT-15)

## [16/04/2026]
### Added
- Endpoints for campaigns and bounce webhooks, with validation and idempotent handling (FRT-10)
- Migrations, Eloquent modeles, HTTP Controllers and Form Requests for comments, likes, follows and moderation in Community Domain(FRT-11)
- Delivery module with migrations, models, factories, events, listeners, jobs, and HTTP controllers (FRT-10)

### Changed
- Event-driven integration between Delivery, Publishing, Audience, and Activity using domain events and centralized listeners (FRT-10)

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
