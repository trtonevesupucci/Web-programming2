# AGENTS.md

Context for AI coding assistants working on this repository.

## Project

Restaurant single-page web application. Customers browse the menu, place orders and book tables. Admins manage users, categories, menu items, orders, order items and reservations. Recipe inspiration comes from TheMealDB.

## Technology Stack

- Frontend: HTML, CSS, JavaScript, jQuery, jQuery SPApp. No frameworks, no build step.
- Backend: PHP 8, FlightPHP 3, PDO, firebase/php-jwt.
- Database: MySQL / MariaDB, schema and sample data in `restaurant_db.sql`.
- API documentation: OpenAPI 3 in `backend/public/v1/docs/openapi.yaml`, shown with Swagger UI.

## Folder Structure

```
frontend/
  assets/        css, js libraries, images
  pages/         SPA views
  services/      frontend services that call the REST API
  utils/         constants, rest client, helpers
backend/
  logs/          info.log, error.log (ignored by git)
  public/v1/docs Swagger UI and openapi.yaml
  rest/
    index.php    bootstrap, service registration, error handler, authentication hook
    config.php   secrets, ignored by git; config.example.php is the committed template
    dao/         one DAO per table, extends BaseDao
    services/    one service per entity, extends BaseService
    routes/      one route file per entity
    middleware/  AuthMiddleware
    utils/       Logger, HttpException
    data/        Roles
```

## Architecture Rules

- Never mix backend and frontend code. Backend returns JSON only.
- Layers: route → middleware → service → DAO. Routes never call DAO classes. Services are the only layer that calls DAO classes.
- All SQL uses PDO prepared statements. Never concatenate user input into SQL.
- Services whitelist writable columns with the `$fields` property before calling the DAO.
- Validation and business rules live in services and throw `HttpException` with the correct status code.
- Routes check roles with `Flight::auth_middleware()->authorizeRole()` or `authorizeOwnerOrAdmin()`.
- Status codes: 200 read/update/delete, 201 create, 400 validation, 401 missing or invalid token, 403 wrong role, 404 not found, 500 unexpected error, 503 external API unavailable.
- The error handler never exposes stack traces, SQL or file paths to the client.
- Secrets and API keys are read only from `Config`.
- Every new or changed endpoint must be documented in `openapi.yaml`.
- Frontend views do not call AJAX directly. Only files in `frontend/services` use `RestClient`.

## Naming Conventions

- PHP classes: PascalCase (`MenuItemService`), files named after the class.
- Service and DAO methods: camelCase.
- Database tables and columns: snake_case.
- REST paths: plural, kebab-case (`/menu-items`, `/order-items`).
- Flight service names: camelCase (`menuItemService`).

## Rules for AI Assistants

- Do not add code comments.
- Do not commit `backend/rest/config.php`, logs or `vendor`.
- Do not change the database schema without updating `restaurant_db.sql`.
- Keep changes small and explain them so they can be defended.
- Test endpoints against a separate test database, never against `restaurant_db`.
