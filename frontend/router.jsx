import { createBrowserRouter, Navigate } from "react-router-dom";
import DefaultLayout from "./src/components/DefaultLayout";
import GuestLayout from "./src/components/GuestLayout";
import Login from "./src/Auth/Login";
import CounterView from "./src/views/CounterView";
import OrderHistoryView from "./src/views/OrderHistoryView";
import InventoryView from "./src/views/InventoryView";

const router = createBrowserRouter([
  {
    path: "/",
    element: <DefaultLayout />,
    children: [
      {
        path: "/",
        element: <Navigate to="/counter" replace />,
      },
      {
        path: "/counter",
        element: <CounterView />,
      },
      {
        path: "/orders",
        element: <OrderHistoryView />,
      },
      {
        path: "/inventory",
        element: <InventoryView />,
      },
    ],
  },
  {
    path: "/",
    element: <GuestLayout />,
    children: [
      {
        path: "/login",
        element: <Login />,
      },
    ],
  },
  {
    path: "*",
    element: <Navigate to="/counter" replace />,
  },
]);

export default router;
