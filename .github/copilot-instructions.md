<!--
  Model instructions for AI agents working on ntjapps/base-app.
  Keep this file short, actionable, and up-to-date.
-->

# AI assistant instructions for base-app (Laravel + Vue)

Purpose: Help an AI agent be productive quickly in this Laravel + Vite Vue app. Focus on safe edits, tests, and avoid leaking secrets or making destructive database changes.

Project snapshot
- Laravel 12 app (PHP 8.2+), optional Octane. Frontend: Vite + Vue 3 + TypeScript. Key entry points: `public/index.php`, `bootstrap/app.php`, `routes/web.php`, `routes/api.php`.

Quick workflows (common commands)
- Start the Docker dev stack: `docker compose -f .github/docker-dev.yaml up`
- PHP (inside container): `composer dev` (runs `php artisan serve`, `queue:listen`, other dev tasks)
- Run PHP tests: `composer test`
- Frontend: `pnpm run dev` (Vite dev server), `pnpm run build`, `pnpm test` (vitest)

Key patterns and places to inspect
- Feature flags: `app/Features/DevSystem.php` (use it to gate dev-only behavior).
- Routing/middleware: `bootstrap/app.php`, `routes/*`, `app/Http/Middleware/` (e.g., `ProfileFillIfEmpty`).
- Auth/permissions: Passport and gates; check `app/Policies/`, `app/Providers/AuthServiceProvider.php` and `app/Models/User.php`.
- Frontend: `resources/ts/*`, `resources/vue/*`, `vite.config.ts`, `package.json`.
- Backend wiring & providers: `app/Providers/*`, `bootstrap/app.php`.

Tests & CI guidelines
- Use `composer test` for backend tests; `pnpm test` for frontend. The CI uses `phpunit.xml` defaults (e.g., `QUEUE_CONNECTION=sync`) — make sure tests adhere to them.
- Update or add tests for changed behavior (Feature/Unit/Pest where appropriate). For frontend components/additions, add Vitest unit tests and update Cypress e2e tests if necessary.

Important rules for AI-generated changes
- Avoid hardcoding secrets or environment values — use `.env` and config files.
- Assume tests run with `QUEUE_CONNECTION=sync` in CI; do not depend on background workers in tests unless necessary and well-mocked.
- Avoid changing exception handling or Sentry configuration without tests that verify telemetry side-effects.
- When adding or changing routes/middleware, update `bootstrap/app.php` only if you fully understand middleware order and trusted proxy configurations.

Common pitfalls & gotchas
- Don’t rely on a synchronous queue in production; tests may differ from runtime behavior.
- Vite dev removes `public/build` during dev — asset locations differ between dev and build.
- Some dev-only features use `DevSystem` gate; changes here can be environment-dependent; validate with `php artisan`/feature toggles.

If you’re unsure
- Read `composer.json` and `package.json` scripts for common developer commands.
- Inspect `app/Providers/*` and `routes/*` for bootstrapping logic and middleware ordering.
- Ask the maintainer which Docker profile to use (README recommends `.github/docker-dev.yaml`) for local network-dependent tests.

Contact
- If instructions are unclear or you need an environment secret or docker profile, ask the repo owner for the correct dev setup.

Feedback
- If anything missing, ask to clarify specific scenarios or expected workflows.
