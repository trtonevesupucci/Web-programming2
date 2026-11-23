# AI Usage Log

## Milestone 3

### Tools

- Claude Code (Claude Opus) in the terminal.

### What it was used for

- Reviewing the backend against the Milestone 3 requirements.
- Fixing services that called methods which did not exist (`ReservationService`, `OrderItemService`) and the wrong column name `number_of_guests` instead of `guests`.
- Adding field whitelisting and validation to all services, with `HttpException` for correct status codes.
- Adding the missing `OrderItem` routes.
- Fixing the public route check in `index.php` (every route was treated as public because every path starts with `/`).
- Implementing `RecipeService` for TheMealDB with timeout, error handling and logging.
- Writing `openapi.yaml` for all endpoints and the Swagger UI page.
- Moving secrets out of git with `config.example.php` and `.gitignore`.

### Important prompts

- "Go through the project requirements document and check if everything for Milestone 3 is correct, fix it if not, do not leave comments anywhere."

### Cases where the AI was wrong

1. **Tests ran against the real database.** The AI chained the SQL import and the config change into one command. The import failed, so the config change never happened, and the following test requests ran against `restaurant_db` and created a test user there. It was noticed because the responses contained old data from `restaurant_db` instead of the fresh sample data. The test user was deleted and tests were repeated against a separate `restaurant_db_m3test` database.
2. **Wrong URL rewriting with the PHP built-in server.** The first version of the base path fix in `index.php` stripped `SCRIPT_NAME` from the URL without checking that it is a PHP file. With the built-in server `SCRIPT_NAME` can be the request path itself, so `GET /users/email/jane@example.com` returned the root endpoint. It was noticed in the endpoint tests and fixed by stripping only when `SCRIPT_NAME` ends with `.php`, then verified on Apache.
