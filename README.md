# Restaurant Web Application

A responsive single-page web application for a restaurant. Customers browse the menu, place orders and book tables. Administrators manage users, categories, menu items, orders and reservations.

## Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript, jQuery, jQuery SPApp
- **Backend:** PHP 8, FlightPHP, PDO
- **Database:** MySQL / MariaDB
- **Authentication:** JWT (firebase/php-jwt)
- **API documentation:** OpenAPI 3 with Swagger UI
- **External API:** TheMealDB

## Entities and Relationships

| Entity | Table | Relationships |
|---|---|---|
| User | `users` | has many orders, has many reservations |
| Category | `categories` | has many menu items |
| Menu item | `menu_items` | belongs to a category, appears in many order items |
| Order | `orders` | belongs to a user, has many order items |
| Order item | `order_items` | belongs to an order and to a menu item |
| Reservation | `reservations` | belongs to a user |

## Roles and Test Accounts

| Role | Email | Password |
|---|---|---|
| admin | admin@restaurant.com | password123 |
| customer | john@example.com | password123 |
| customer | jane@example.com | password123 |

- **admin** manages all entities.
- **customer** browses the menu and categories, places and cancels own pending orders, manages own reservations and own profile.

## Backend Architecture

```
backend/
├── logs/                 info.log and error.log (not committed)
├── public/v1/docs/       Swagger UI and openapi.yaml
└── rest/
    ├── config.php        local configuration (not committed)
    ├── config.example.php
    ├── index.php         bootstrap, error handler, authentication
    ├── dao/              data access with PDO prepared statements
    ├── data/             roles
    ├── middleware/       JWT authentication and role checks
    ├── routes/           FlightPHP routes
    ├── services/         business logic and validation
    └── utils/            logger and HTTP exceptions
```

Request flow: route → middleware → service → DAO → database. Services are the only layer that uses DAO classes.

All responses are JSON with status codes `200`, `201`, `400`, `401`, `403`, `404`, `500` and `503` for an unavailable external API.

## Business Rules

- Passwords are hashed with bcrypt and never returned by the API.
- Registration always creates a customer. Only an admin can assign roles.
- Category names are unique. A category that still has menu items cannot be deleted.
- Menu item prices must be positive and the category must exist.
- Order prices are taken from the menu, not from the client. The order total is recalculated whenever items change.
- Only available menu items can be ordered. Items can only be changed while the order is pending.
- Delivered and cancelled orders are final. Customers can cancel only pending orders.
- Reservations must be in the future, between 10:00 and 22:00, at most 6 months ahead, for 1 to 20 guests, with at most 5 reservations per time slot.

## External API

The backend integrates [TheMealDB](https://www.themealdb.com/api.php) to provide recipe inspiration. In the UI it is shown as a "Chef's inspiration" section on the home page (connected in Milestone 4).

| Endpoint | Description |
|---|---|
| `GET /external/recipes/random` | random recipe |
| `GET /external/recipes/search?name=pasta` | search recipes by name |
| `GET /external/recipes/{id}` | recipe details |

The API key, base URL and timeout are stored in `config.php`. Requests time out after 5 seconds. Failures are written to `backend/logs/error.log` and the endpoint returns `503` with a user friendly message while the rest of the application keeps working.

## API Documentation

Swagger UI: http://localhost/RijadTrtic/Web-Programming/backend/public/v1/docs/

## Running Locally

1. Copy the project into `C:\xampp\htdocs\RijadTrtic\Web-Programming`.
2. Start Apache and MySQL in XAMPP.
3. Create a database named `restaurant_db` and import `restaurant_db.sql`.
4. Copy `backend/rest/config.example.php` to `backend/rest/config.php` and fill in the database credentials, a JWT secret and the TheMealDB API key (`1` is the public test key).
5. Install dependencies:
   ```
   cd backend/rest
   composer install
   ```
6. Open http://localhost/RijadTrtic/Web-Programming/frontend/

## Deployed Application

Will be added in Milestone 5.
