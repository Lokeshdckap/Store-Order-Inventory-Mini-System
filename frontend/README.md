# Frontend Application

This React frontend provides the admin interface for the StoreCounter POS and inventory system. It is built with Vite, React Router, and Ant Design and communicates with the Laravel API through Axios.

## Purpose

The frontend allows store admins to:

- log in securely
- manage the retail counter and checkout flow
- create new orders for customers
- search inventory items
- review customer order history
- monitor low-stock thresholds
- add, edit, and delete products

## Stack

- React 19
- Vite 7
- React Router DOM
- Ant Design
- Axios

## Project Structure

```text
frontend/
├── public/
├── src/
│   ├── Auth/
│   ├── components/
│   ├── context/
│   ├── views/
│   ├── App.jsx
│   ├── main.jsx
│   └── index.css
├── axiosClient.js
├── index.html
├── package.json
├── vite.config.js
├── eslint.config.js
└── README.md
```

## Setup

### Install dependencies

```bash
cd frontend
npm install
```

### Start in development mode

```bash
npm run dev
```

The development server usually runs here:

- http://localhost:5173

### Build for production

```bash
npm run build
```

## Configuration

The app reads API configuration from Vite env variables.

```env
VITE_API_BASE_URL=http://127.0.0.1:8000
```

If this variable is not set, the app falls back to `/api` for a same-origin API request.

## Main Routes

The app uses React Router with authenticated and guest layouts.

| Route | Screen | Access |
| --- | --- | --- |
| `/login` | Admin login | Guest |
| `/counter` | POS / sales counter | Authenticated |
| `/orders` | Customer order history | Authenticated |
| `/inventory` | Inventory management | Authenticated |

## Authentication Flow

- User enters admin email/password.
- Frontend calls `POST /api/login`.
- The token is saved in localStorage.
- Axios attaches the Bearer token to all authenticated requests.
- If a request returns 401, the app clears the token and redirects to `/login`.

## Screens

### Login Screen

The admin login screen includes:

- email and password form
- authentication error handling
- secure redirect behavior

### Counter Screen

The POS counter interface supports:

- browsing products
- searching by name or code
- adding items to cart
- adjusting item quantities
- entering customer data
- calculating totals including tax
- posting the order to the backend

### Order History Screen

This screen lets the admin:

- enter a customer's email
- fetch all matching orders
- review totals and line items
- inspect the created date and status of each order

### Inventory Screen

This screen supports:

- viewing all products and low-stock products
- creating new products
- editing existing products
- applying stock changes
- deleting products when allowed
- filtering by search criteria

## Shared Client

The frontend uses an axios client configured in `axiosClient.js`.

This client:

- sets the API base URL
- includes the Bearer token automatically
- clears stale auth on 401 responses
- redirects unauthenticated users to login

## Styling and UI

The interface uses Ant Design components with custom layout styling to create a dashboard-like retail experience. The app includes status indicators for low stock and uses a dark sidebar navigation for the authenticated app shell.

## Scripts

```bash
npm run dev
npm run build
npm run preview
npm run lint
```

## Notes

- Sample customer emails are prefilled in the order history screen to make exploration easier.
- The app uses `useEffect` fetches to refresh inventory and low-stock counts in the main layout.
- The default seeded admin account is `admin@example.com` / `admin12345`.

## Production Considerations

- Set `VITE_API_BASE_URL` to your deployed backend URL.
- Ensure the Laravel backend is configured with the correct CORS and Sanctum settings for your domain.
- Consider using environment-specific build variables for staging and production deployments.

---

For project-wide setup steps and overview, see [../README.md](../README.md).
