import React, { useState, useEffect } from "react";
import { Link, Outlet, useLocation, useNavigate, Navigate } from "react-router-dom";
import { Layout, Menu, Badge, Typography, Button, Space, Avatar, message, Tooltip } from "antd";
import {
  ShoppingCartOutlined,
  HistoryOutlined,
  DatabaseOutlined,
  WarningOutlined,
  LogoutOutlined,
  UserOutlined,
  CrownOutlined,
} from "@ant-design/icons";
import axiosClient from "../../axiosClient";
import { useStateContext } from "../context/ContextProvider";

const { Sider, Content } = Layout;
const { Text } = Typography;

export default function DefaultLayout() {
  const location = useLocation();
  const navigate = useNavigate();
  const { user, token, setUser, setToken } = useStateContext();
  const [lowStockCount, setLowStockCount] = useState(0);
  const [loggingOut, setLoggingOut] = useState(false);

  // Authentication Guard: Redirect to login if token is missing
  if (!token) {
    return <Navigate to="/login" replace />;
  }

  // Fetch current admin user profile and low-stock count
  useEffect(() => {
    let mounted = true;

    const fetchUserProfile = async () => {
      try {
        const res = await axiosClient.get("/user");
        if (mounted && res.data) {
          setUser(res.data);
        }
      } catch (err) {
        // Handled by 401 interceptor
      }
    };

    const fetchLowStockCount = async () => {
      try {
        const res = await axiosClient.get("/products/low-stock");
        if (mounted) {
          setLowStockCount(res.data.meta?.total_low_stock_items || 0);
        }
      } catch (e) {
        // silent
      }
    };

    fetchUserProfile();
    fetchLowStockCount();

    const interval = setInterval(fetchLowStockCount, 30000);
    return () => {
      mounted = false;
      clearInterval(interval);
    };
  }, []);

  const handleLogout = async () => {
    setLoggingOut(true);
    try {
      await axiosClient.post("/logout");
      message.success("Logged out successfully");
    } catch (err) {
      console.warn("Logout request completed with warning:", err);
    } finally {
      setUser(null);
      setToken(null);
      setLoggingOut(false);
      navigate("/login", { replace: true });
    }
  };

  const selectedKey = location.pathname.startsWith("/orders")
    ? "orders"
    : location.pathname.startsWith("/inventory")
    ? "inventory"
    : "counter";

  const menuItems = [
    {
      key: "counter",
      icon: <ShoppingCartOutlined />,
      label: <Link to="/counter">Counter / POS</Link>,
    },
    {
      key: "orders",
      icon: <HistoryOutlined />,
      label: <Link to="/orders">Order History</Link>,
    },
    {
      key: "inventory",
      icon: <DatabaseOutlined />,
      label: (
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", width: "100%" }}>
          <Link to="/inventory" style={{ color: "inherit", flex: 1 }}>Inventory</Link>
          {lowStockCount > 0 && (
            <Badge
              count={lowStockCount}
              size="small"
              style={{ backgroundColor: "#faad14", boxShadow: "none" }}
            />
          )}
        </div>
      ),
    },
  ];

  return (
    <Layout style={{ minHeight: "100vh" }}>
      <Sider
        width={240}
        style={{
          background: "linear-gradient(180deg, #1e1b4b 0%, #312e81 60%, #4c1d95 100%)",
          boxShadow: "2px 0 10px rgba(0,0,0,0.18)",
          display: "flex",
          flexDirection: "column",
        }}
      >
        <div style={{ display: "flex", flexDirection: "column", height: "100%" }}>
          {/* Brand Header */}
          <div
            style={{
              padding: "24px 20px 18px",
              borderBottom: "1px solid rgba(255,255,255,0.12)",
            }}
          >
            <div style={{ fontSize: 20, fontWeight: 800, color: "#fff", letterSpacing: "-0.5px" }}>
              StoreCounter
            </div>
            <Text style={{ color: "rgba(255,255,255,0.6)", fontSize: 12 }}>
              Retail Order & Inventory
            </Text>
          </div>

          {/* Navigation Menu */}
          <div style={{ flex: 1, paddingTop: 12 }}>
            <Menu
              mode="inline"
              selectedKeys={[selectedKey]}
              onClick={({ key }) => navigate(`/${key}`)}
              items={menuItems}
              style={{
                background: "transparent",
                border: "none",
                color: "rgba(255,255,255,0.85)",
              }}
              theme="dark"
            />

            {/* Low stock warning banner in sidebar */}
            {lowStockCount > 0 && (
              <div
                onClick={() => navigate("/inventory")}
                title="Click to manage low-stock inventory"
                style={{
                  margin: "16px 14px",
                  padding: "10px 12px",
                  backgroundColor: "rgba(250, 173, 20, 0.15)",
                  border: "1px solid rgba(250, 173, 20, 0.35)",
                  borderRadius: 6,
                  fontSize: 12,
                  color: "#ffe58f",
                  cursor: "pointer",
                  transition: "all 0.2s",
                }}
              >
                <WarningOutlined style={{ marginRight: 6 }} />
                {lowStockCount} SKU(s) low on stock
              </div>
            )}
          </div>

          {/* Admin User Details & Logout in Sidebar Footer */}
          <div
            style={{
              padding: "16px 18px",
              borderTop: "1px solid rgba(255,255,255,0.12)",
              backgroundColor: "rgba(0,0,0,0.15)",
            }}
          >
            <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 12 }}>
              <Avatar
                style={{ backgroundColor: "#6366f1" }}
                icon={<CrownOutlined />}
                size={38}
              />
              <div style={{ overflow: "hidden", lineHeight: 1.3 }}>
                <div
                  style={{
                    color: "#fff",
                    fontWeight: 600,
                    fontSize: 14,
                    whiteSpace: "nowrap",
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                  }}
                  title={user?.name || "Admin"}
                >
                  {user?.name || "Admin"}
                </div>
                <div
                  style={{
                    color: "rgba(255,255,255,0.6)",
                    fontSize: 11,
                    whiteSpace: "nowrap",
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                  }}
                  title={user?.email || "admin@example.com"}
                >
                  {user?.email || "admin@example.com"}
                </div>
              </div>
            </div>

            <Button
              type="primary"
              danger
              icon={<LogoutOutlined />}
              onClick={handleLogout}
              loading={loggingOut}
              block
              size="small"
              style={{
                borderRadius: 6,
                height: 32,
                fontSize: 12,
                fontWeight: 500,
                background: "rgba(239, 68, 68, 0.85)",
                borderColor: "rgba(239, 68, 68, 0.85)",
              }}
            >
              Sign Out
            </Button>
          </div>
        </div>
      </Sider>

      <Layout>
        <Content
          style={{
            padding: "28px 32px",
            backgroundColor: "#f5f6fa",
            minHeight: "100vh",
          }}
        >
          <Outlet />
        </Content>
      </Layout>
    </Layout>
  );
}
