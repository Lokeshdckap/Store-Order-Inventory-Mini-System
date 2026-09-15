# Backend API Documentation

This Laravel backend powers the StoreCounter inventory and retail order system. It exposes authenticated endpoints for inventory management, order creation, customer history lookup, and admin authentication.

## Purpose

The backend is responsible for:

- authenticating administrators with Sanctum tokens
- managing product inventory and stock levels
- validating stock before creating orders
- recording sales and order line items
- returning order history for each customer
- dispatching order confirmation email jobs

## Stack

- PHP 8.2
- Laravel 12
- Laravel Sanctum
- SQLite (default local setup)
- PHPUnit for feature tests

## Project Structure

```text
backend/
├── app/
│   ├── Exceptions/
│   ├── Http/
│   ├── Jobs/
│   ├── Mail/
│   ├── Models/
│   ├── Providers/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── .env.example
├── artisan
├── composer.json
├── phpunit.xml
└── vite.config.js
```

## Setup

### Install dependencies

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### Database setup

The project uses SQLite by default and ships with example configuration.

```bash
php artisan migrate --seed
```

This creates the schema and seeds:

- default admin user
- product catalog
- sample customers
- sample order history

### Start the API server

```bash
php artisan serve
```

Default local URL:

- http://127.0.0.1:8000

## Authentication

Admin login is public and returns a token in JSON. Once a user is authenticated, the token must be passed as a Bearer token on protected routes.

### Login request

```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "admin12345"
}
```

### Success response

```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com"
  },
  "token": "<sanctum-token>"
}
```

## API Routes

All routes under `/api` are defined in `routes/api.php`.

### Public routes

| Method | Route | Description |
| --- | --- | --- |
| GET | `/api/ping` | Health check |
| POST | `/api/login` | Admin login |

### Protected routes

| Method | Route | Description |
| --- | --- | --- |
| GET | `/api/user` | Logged-in admin profile |
| POST | `/api/logout` | Logout and revoke token |
| GET | `/api/products` | List products |
| POST | `/api/products` | Create product |
| GET | `/api/products/low-stock` | List low-stock items |
| PUT | `/api/products/{product}` | Update product |
| PATCH | `/api/products/{product}/stock` | Adjust stock |
| DELETE | `/api/products/{product}` | Delete product |
| POST | `/api/orders` | Create order |
| GET | `/api/orders/{order}` | View order details |
| GET | `/api/customers/{email}/orders` | Customer order history |

## Product Model

Products include:

- unique UUID
- name
- code
- price
- tax percentage
- stock quantity

The `Product` model uses UUID route keys and exposes a `lowStock` query scope for threshold-based checks.

## Order Creation Flow

The backend creates orders using `OrderService` and follows a transaction-safe flow:

1. Normalize customer email and name.
2. Aggregate requested quantities by product UUID.
3. Lock matching product rows for update.
4. Validate each product exists and has enough stock.
5. Calculate line subtotal, tax, and totals.
6. Deduct stock in the transaction.
7. Create the order and order items.
8. Dispatch the email job after commit.

This is designed to prevent overselling when multiple requests compete for the same inventory.

## Business Rules

- Orders must contain at least one product.
- Product stock cannot drop below zero.
- Customer records are created or reused by email.
- Order totals are computed from product unit price and tax percentage.
- Email confirmation jobs are queued once the order transaction is committed.

## Sample Payloads

### Create product

```http
POST /api/products
Authorization: Bearer <token>

{
  "name": "Mechanical Keyboard RGB",
  "code": "SKU-KB01",
  "price": 79.99,
  "tax_percentage": 18,
  "stock": 15
}
```

### Create order

```http
POST /api/orders
Authorization: Bearer <token>

{
  "customer_name": "Jane Doe",
  "customer_email": "jane@example.com",
  "items": [
    { "product_uuid": "<uuid>", "quantity": 2 },
    { "product_uuid": "<uuid>", "quantity": 1 }
  ]
}
```

## Testing

Run the feature suite:

```bash
php artisan test
```

The project includes tests for:

- admin authentication
- order creation
- stock validation
- low-stock endpoints
- customer order history
- email dispatch behavior

## Queue and Mail

The default queue driver is `database`, and order confirmation emails are queued using a dedicated job and Mailable class.

To process queued jobs:

```bash
php artisan queue:listen --tries=1
```

## Notes

- The default seeder populates realistic product and customer data for demo use.
- Low stock threshold is configurable through `config/inventory.php`.
- The app stores product and order UUIDs in addition to standard IDs for safer route usage.

## Recommended Commands

```bash
php artisan migrate:fresh --seed
php artisan test
php artisan queue:listen
php artisan serve
```

---

For a full project overview and frontend setup, see [../README.md](../README.md) and [../frontend/README.md](../frontend/README.md).
