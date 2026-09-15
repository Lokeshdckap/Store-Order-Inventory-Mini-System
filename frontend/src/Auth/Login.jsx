import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { Card, Form, Input, Button, Alert, Typography, Space, Divider } from "antd";
import { UserOutlined, LockOutlined, LoginOutlined, SafetyCertificateOutlined } from "@ant-design/icons";
import axiosClient from "../../axiosClient";
import { useStateContext } from "../context/ContextProvider";

const { Title, Text } = Typography;

export default function Login() {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const { setUser, setToken } = useStateContext();
  const navigate = useNavigate();

  const handleSubmit = async (values) => {
    setLoading(true);
    setErrorMessage(null);

    try {
      const response = await axiosClient.post("/login", {
        email: values.email,
        password: values.password,
      });

      const { user, token } = response.data;
      setUser(user);
      setToken(token);
      navigate("/counter");
    } catch (error) {
      if (error.response) {
        if (error.response.status === 401) {
          setErrorMessage("Invalid email or password. Please verify your admin credentials.");
        } else if (error.response.status === 422) {
          const errors = error.response.data.errors;
          const firstErr = errors ? Object.values(errors)[0]?.[0] : "Validation error";
          setErrorMessage(firstErr);
        } else {
          setErrorMessage(error.response.data.message || "An unexpected error occurred.");
        }
      } else {
        setErrorMessage("Network error: Cannot reach the backend API server.");
      }
    } finally {
      setLoading(false);
    }
  };

  const fillDefaultAdmin = () => {
    form.setFieldsValue({
      email: "admin@example.com",
      password: "admin12345",
    });
    setErrorMessage(null);
  };

  return (
    <div
      style={{
        minHeight: "100vh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        background: "linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%)",
        padding: "20px",
      }}
    >
      <Card
        style={{
          width: "100%",
          maxWidth: 420,
          borderRadius: 12,
          boxShadow: "0 10px 30px rgba(0,0,0,0.3)",
          border: "none",
        }}
        bodyStyle={{ padding: "36px 30px" }}
      >
        <div style={{ textAlign: "center", marginBottom: 28 }}>
          <div style={{ fontSize: 40, marginBottom: 8 }}>🏪</div>
          <Title level={3} style={{ margin: 0, color: "#1e1b4b" }}>
            StoreCounter POS
          </Title>
          <Text type="secondary" style={{ fontSize: 13 }}>
            Administrator Authentication Portal
          </Text>
        </div>

        {errorMessage && (
          <Alert
            message={errorMessage}
            type="error"
            showIcon
            closable
            onClose={() => setErrorMessage(null)}
            style={{ marginBottom: 20, borderRadius: 6 }}
          />
        )}

        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          initialValues={{
            email: "admin@example.com",
            password: "admin12345",
          }}
        >
          <Form.Item
            name="email"
            label={<Text strong>Admin Email</Text>}
            rules={[
              { required: true, message: "Please enter your admin email" },
              { type: "email", message: "Please enter a valid email address" },
            ]}
          >
            <Input
              prefix={<UserOutlined style={{ color: "#9ca3af" }} />}
              placeholder="admin@example.com"
              size="large"
              style={{ borderRadius: 6 }}
            />
          </Form.Item>

          <Form.Item
            name="password"
            label={<Text strong>Admin Password</Text>}
            rules={[{ required: true, message: "Please enter your password" }]}
          >
            <Input.Password
              prefix={<LockOutlined style={{ color: "#9ca3af" }} />}
              placeholder="••••••••"
              size="large"
              style={{ borderRadius: 6 }}
            />
          </Form.Item>

          <Form.Item style={{ marginTop: 24, marginBottom: 12 }}>
            <Button
              type="primary"
              htmlType="submit"
              size="large"
              block
              loading={loading}
              icon={<LoginOutlined />}
              style={{
                borderRadius: 6,
                background: "linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%)",
                border: "none",
                fontWeight: 600,
                height: 44,
              }}
            >
              Log In as Administrator
            </Button>
          </Form.Item>
        </Form>

        {/* <Divider style={{ margin: "16px 0", fontSize: 12, color: "#9ca3af" }}>
          Default Seeder Credentials
        </Divider>

        <div
          style={{
            backgroundColor: "#f8fafc",
            border: "1px dashed #cbd5e1",
            borderRadius: 8,
            padding: "12px 14px",
            fontSize: 12,
            color: "#475569",
          }}
        >
          <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 4 }}>
            <span><strong>Email:</strong> admin@example.com</span>
            <span><strong>Pass:</strong> admin12345</span>
          </div>
          <Button
            size="small"
            type="dashed"
            block
            icon={<SafetyCertificateOutlined />}
            onClick={fillDefaultAdmin}
            style={{ marginTop: 6, borderRadius: 4 }}
          >
            Fill Default Admin Credentials
          </Button>
        </div> */}
      </Card>
    </div>
  );
}
