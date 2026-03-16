# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 16-03-206
### Added
- Full implementation of the development agent ecosystem (#FRT-7)
- Defined and standardized the agent handoff workflow (`planner -> implementer -> guardian -> release-manager`) 
- Implement MCP protocols using DigitalOcean, Github, Supabase and Laravel services (#FRT-7)
- Added specialized skills for Laravel/PHP, PHPUnit, PostgreSQL, agent governance, and iterative anti(#FRT-7)
- Integrated Laravel AI runtime support (`laravel/ai`) with provider configuration in `config/ai.php`(#FRT-7)
- Updated agent/AI operational documentation (`AGENTS.md`, `copilot-instructions.md`, `llms.txt`, `llms-full.txt`) (#FRT-7)

## [07/02/2026]
### Added
- Run Laravel empty project without started-kit (#FRT-0)
- Enviroment development into a Dev Container (#FRT-1)
- Internachi package used for create DDA system (#FRT-2)
- Identity models, migrations, factories implemented (#FRT-3)
- Activity models, migrations, factories, serviceProvider implemented. Tinker and PhpUnit test passed (#FRT-4)
