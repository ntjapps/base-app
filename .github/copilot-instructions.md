<!--
  Auto-generated guidance for AI coding assistants working on ntjapps/base-app.
  Keep this file short and specific. When updating, preserve examples and references.
-->

# Copilot / AI assistant instructions for base-app

Purpose: help an AI agent be immediately productive in this Laravel (PHP) + Vue/Vite frontend template.

- Project type: Laravel 12 application (PHP 8.2+), optional Octane, Passport, Pennant. Frontend built with Vite + Vue 3 + TypeScript in `resources/ts` and `resources/vue`.
- Key entry points: `public/index.php` (app bootstrap), `bootstrap/app.php` (routing + exception wiring), `routes/web.php` and `routes/api.php` (route surface).

Quick workflows (commands you'll commonly suggest or run):

- Start local dev stack (uses docker compose dev file shown in `README.md`):
  - docker compose -f .github/docker-dev.yaml up
- PHP dev helpers (non-Docker / inside container):
  - composer dev (see composer.json `scripts.dev` — runs `php artisan serve`, queue:listen, pail and `npm run dev` concurrently)
  - run PHP tests: `composer test` (runs `php artisan test` after clearing config)
- Frontend scripts (run from repo root):
  - `pnpm run dev` or `npm run dev` (Vite dev server)
  - `pnpm run build` (Vite build — builds into `public/build`)
  - `pnpm test` (vitest)

Project conventions and patterns (concrete, discoverable):

- Feature flags and environment:
  - `app/Features/DevSystem.php` is used to resolve dev-only features. It returns true when the user has `hasSuperPermission` gate, when `config('app.debug')` is true, or when `app()->environment('testing')` is set.
- Routing and middleware:
  - `bootstrap/app.php` wires routes and configures middleware (trusted proxies, web middleware additions like `CreateFreshApiToken`, Sentry exception integration).
  - `routes/web.php` groups routes by `guest` and `auth` middleware and uses `ProfileFillIfEmpty` to force profile completion before normal dashboard flows.
- Auth and permission model:
  - Laravel Passport is used (see `composer.json`) alongside gates such as `hasSuperPermission`. Look for authorization logic under `app/Policies` and middleware usage in `routes/*` when editing permissions-related code.
- Tests and CI:
  - PHPUnit configuration in `phpunit.xml` sets `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, and `TELESCOPE_ENABLED=false` for tests. Prefer modifying tests to work with these environment defaults.
  - Frontend unit tests use Vitest (see `package.json` scripts). E2E tests are configured with Cypress under `tests/cypress` and `cypress.config.ts` uses baseUrl `http://docker.localhost`.

Files & directories to inspect first when working on a task:

- Backend runtime and wiring: `bootstrap/app.php`, `public/index.php`, `app/Providers/*`
- Routes and controllers: `routes/web.php`, `routes/api.php`, `app/Http/Controllers/*`
- Policies / Permissions: `app/Policies/`, `app/Providers/AuthServiceProvider.php`, `app/Models/User.php`
- Features and conventions: `app/Features/` (small feature toggles like `DevSystem`), `app/Traits/`
- Frontend: `resources/ts/` (TypeScript entry files), `resources/vue/`, `vite.config.ts`, `package.json` for scripts
- Tests: `tests/Unit`, `tests/Feature`, `tests/cypress` (Cypress e2e)

What to do when you change behavior:

- Update tests (PHPUnit / Pest for PHP, Vitest for frontend) and run them locally. Use the project's test presets: `composer test` for PHP and `pnpm test` for frontend.
- If you change routes or middleware, update `bootstrap/app.php` or `routes/*` and ensure trusted-proxy settings and exception handlers remain intact.

Common pitfalls and specific guidance:

- Do not assume synchronous queue behavior in production — tests and `phpunit.xml` force `QUEUE_CONNECTION=sync`, but composer dev scripts may run workers or `queue:listen`.
- Sentry integration: `bootstrap/app.php` wires Sentry integration if `app()->bound('sentry')`. Avoid modifying exception handling without running tests and verifying logging side-effects.
- Environment & secrets: the README points to an env decryption helper (`php artisan env:decrypt --key=...`). For local dev the repo expects Docker and `.env` from `.env.example` (see composer post-root install hook).
- Frontend assets path: Vite builds into `public/build`. Dev script removes `public/build` and runs Vite dev server.

Examples (lookups and edits):

- To add a new route that requires profile completion, add it under the `ProfileFillIfEmpty` middleware group in `routes/web.php`.
- To add a dev-only UI feature, prefer using `app/Features/DevSystem.php` as the gate so behavior remains consistent across environments.

If you cannot find explicit guidance in the files above:

- Check `composer.json` and `package.json` scripts — they capture many of the developer commands in use.
- Inspect `app/Providers` for bootstrapping conventions (bindings, feature toggles, view composers).

When in doubt, ask the repo owner which Docker profile to use (the README suggests `.github/docker-dev.yaml`) — the dev container's networking (docker.localhost) is relied upon by Cypress and other tests.

Feedback
---
If any part of these instructions is unclear or missing a critical workflow you use, tell me which task you expect the AI to perform and I'll expand or adjust this file.
