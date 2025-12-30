# Ultimate Character Generator (DB-first)

This project refactors the original `chargen2.html` generator into a PHP + MariaDB stack with a rule engine and admin UI.

## Features
- MariaDB schema for taxonomy (species, ancestry), genders, life stages, computed keys, rules, dependencies, and base profiles.
- Seed script that migrates values from the legacy generator (heights, weights, proportions, distributions).
- PHP rule engine with deterministic seeded RNG, multiple distributions (gaussian, uniform, ratio, linear, piecewise, sigmoid, choice), and dependency resolution.
- Bootstrap-based admin UI with CRUD for taxonomy, computed keys, base profiles, rules, and preview runner.
- Public generator UI (`index.php`) calling `/api/generate.php` to return the same shape as the former JS output.

## Setup
1. Create a MariaDB database (default DSN expects `ucg` database).
   ```sql
   CREATE DATABASE ucg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Configure environment variables for PHP:
   - `DB_DSN` (default: `mysql:host=localhost;dbname=ucg;charset=utf8mb4`)
   - `DB_USER` / `DB_PASS`
3. Run the schema and seed from the project root:
   ```bash
   php install.php      # loads schema.sql and runs the seed
   ```
4. Ensure your web server points to the repository root so that `/index.php`, `/api/generate.php`, and `/admin/*` are reachable.

## Admin UI
- Login: `/admin/login.php` (seed does not create a default user; insert one manually with `password_hash`).
- Manage taxonomy: `/admin/species.php` and `/admin/life_stages.php`.
- Manage computed keys: `/admin/computed_keys.php`.
- Manage base profiles: `/admin/base_profiles.php` (JSON editor included).
- Manage rules: `/admin/rules.php` (JSON editor with manual validation).
- Preview results & legacy placeholder: `/admin/preview.php`.

## Rule Engine
- Inputs: `species_id`, `ancestry_group_id`, `gender_id`, `life_stage_id`, `age`, and optional `seed`.
- Distributions: gaussian, uniform, linear, ratio, piecewise, sigmoid, choice.
- Safety: params JSON is declarative; no dynamic code execution. CSRF tokens and HTML escaping are applied in the admin.

## Legacy File
The original `chargen2.html` remains for reference; its constants and formulas were transferred into `db/seed.php`.
