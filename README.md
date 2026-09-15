# StoreCounter POS & Inventory Management System

StoreCounter is a full-stack retail management application built with Laravel and React. It helps store administrators manage products, process customer orders, review order history, and monitor low-stock inventory through a clean dashboard and POS-style interface.

## Overview

This project combines:

- A Laravel 12 backend API with Sanctum authentication
- A React + Vite frontend powered by Ant Design components
- Mysql Database
- Inventory-aware order creation with stock validation and atomic transactions
- Customer order history lookup by email
- Automated order confirmation email dispatch after checkout

## Business Workflow

The application is designed around a small retail flow:

1. Admin logs in to the system.
2. Staff browse the product catalog and search for items.
3. Items are added to a customer cart in the POS counter.
4. Order totals are calculated including tax.
5. The backend validates stock before finalizing the order.
6. Products are decremented atomically and the order is saved.
7. A confirmation email is queued for the customer.
8. Admin can review order history and manage inventory levels.

## Core Features

### Admin Authentication
- Secure login using Laravel Sanctum
- Token-based authenticated API access
- Protected routes for product and order operations

### POS / Counter Workflow
- Product catalog with search and stock visibility
- Add-to-cart behavior with stock limits
- Customer name and email capture
- Tax and total calculation before order placement
- Real-time inventory refresh after order creation

### Inventory Management
- Create, update, and delete products
- Adjust product stock manually
- Track low-stock items using configurable thresholds
- Search and filter inventory records

### Order Tracking
- Detailed order records with order numbers and totals
- Customer order history lookup by email
- Itemized order detail view
- Financial summary including subtotal, tax, and grand total

### Reliability
- Database transactions to prevent overselling
- Locking logic on product rows during order creation
- Deadlock-safe ordering of product UUID locks
- Insufficient stock handling with custom exception logic

## Tech Stack

### Backend
- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- Mysql
- PHPUnit

### Frontend
- React 19
- Vite
- Ant Design
- React Router
- Axios

## Project Structure

```text
inventory-system/
├── README.md
├── backend/
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── tests/
│   ├── .env.example
│   ├── artisan
│   ├── composer.json
│   └── package.json
├── frontend/
│   ├── src/
│   ├── public/
│   ├── index.html
│   ├── package.json
│   ├── vite.config.js
│   └── README.md
└── .git/
```

## Default Admin Credentials

The seeded default admin is:

- Email: admin@example.com
- Password: admin12345

These values are configured in the seeder and can be adjusted via environment variables.

## Requirements

Before running the project, install:

- PHP 8.2+
- Composer
- Node.js 18+
- npm

## Quick Start

### 1. Backend Setup

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The Laravel API will be available at:

- http://127.0.0.1:8000

### 2. Frontend Setup

In a second terminal:

```bash
cd frontend
npm install
npm run dev
```

The frontend app will be available at:

- http://localhost:3000

## Environment Configuration

The API uses environment variables defined in the backend `.env` file. Key values include database, app URL, and admin credentials. The default configuration is focused on SQLite and local development.

Example backend `.env` values:

```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=mysql
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=admin12345
```

Frontend requests use a configured base URL through the Vite environment. By default the app calls `/api` when running locally, and it also reads `VITE_API_BASE_URL` when set.

## Core API Features

The backend exposes inventory and order endpoints under `/api`.

### Authentication
- `POST /api/login` — admin login
- `GET /api/user` — current authenticated user
- `POST /api/logout` — logout

### Products
- `GET /api/products` — list all products
- `POST /api/products` — create a product
- `PUT /api/products/{product}` — update a product
- `PATCH /api/products/{product}/stock` — update stock
- `DELETE /api/products/{product}` — delete product
- `GET /api/products/low-stock` — list low-stock products

### Orders
- `POST /api/orders` — create order from cart items
- `GET /api/orders/{order}` — fetch one order
- `GET /api/customers/{email}/orders` — customer order history

### Health
- `GET /api/ping` — backend health check

## Key Business Rules

- Product stock cannot go below zero during order creation.
- Orders are created in a database transaction to prevent race conditions.
- Product row locks are used to prevent double-booking stock.
- Multiple items in a single order are aggregated by product UUID.
- Order confirmation emails are queued after commit so the order is persisted before sending mail.

## Main Screens

### Counter / POS
Used to:
- browse stock
- add products to the cart
- collect customer details
- place an order
- review totals and receipt details

### Order History
Used to:
- search orders by customer email
- view totals and line items
- inspect order status and creation time

### Inventory
Used to:
- add or edit products
- adjust stock values
- identify low-stock alerts
- clean up inventory records

## Development Notes

- Laravel uses UUIDs for product and order route keys, improving API safety and decoupling from integer IDs.
- The backend can dispatch queue jobs for email confirmation; the default queue configuration uses the database driver.
- The seeded sample data includes example products and customers to demo the application immediately after migration.


## License

This project is intended for internal retail workflow demonstration and local development use.

---

For more detailed backend API guidance, see [backend/README.md](backend/README.md). For frontend app setup and screen details, see [frontend/README.md](frontend/README.md).
