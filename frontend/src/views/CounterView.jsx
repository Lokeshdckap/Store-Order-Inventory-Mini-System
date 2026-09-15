import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import {
  Row,
  Col,
  Card,
  Input,
  InputNumber,
  Button,
  Table,
  Tag,
  Badge,
  Modal,
  Alert,
  message,
  Typography,
  Divider,
  Space,
  Empty
} from "antd";
import {
  ShoppingCartOutlined,
  SearchOutlined,
  DeleteOutlined,
  CheckCircleOutlined,
  ReloadOutlined,
  PrinterOutlined,
  UserOutlined,
  MailOutlined,
  InboxOutlined,
  DatabaseOutlined,
} from "@ant-design/icons";
import axiosClient from "../../axiosClient";

const { Title, Text } = Typography;

export default function CounterView() {
  const [products, setProducts] = useState([]);
  const [loadingProducts, setLoadingProducts] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  
  // Cart state: array of { product, quantity }
  const [cart, setCart] = useState([]);
  const [customerName, setCustomerName] = useState("");
  const [customerEmail, setCustomerEmail] = useState("");
  const [submittingOrder, setSubmittingOrder] = useState(false);
  const [orderError, setOrderError] = useState(null);

  // Success receipt modal
  const [completedOrder, setCompletedOrder] = useState(null);
  const [receiptVisible, setReceiptVisible] = useState(false);

  // Fetch catalog
  const fetchProducts = async () => {
    setLoadingProducts(true);
    try {
      const res = await axiosClient.get("/products", {
        params: searchQuery ? { q: searchQuery } : {},
      });
      setProducts(res.data.data || []);
    } catch (err) {
      console.error(err);
      message.error("Failed to load products catalog.");
    } finally {
      setLoadingProducts(false);
    }
  };

  useEffect(() => {
    fetchProducts();
  }, [searchQuery]);

  // Add product to cart
  const addToCart = (product) => {
    if (product.stock <= 0) {
      message.warning(`"${product.name}" is currently out of stock!`);
      return;
    }

    setCart((prevCart) => {
      const existingIndex = prevCart.findIndex(
        (item) => item.product.uuid === product.uuid
      );
      if (existingIndex > -1) {
        const currentQty = prevCart[existingIndex].quantity;
        if (currentQty >= product.stock) {
          message.warning(
            `Cannot add more. Only ${product.stock} unit(s) available for "${product.name}".`
          );
          return prevCart;
        }
        const updated = [...prevCart];
        updated[existingIndex] = {
          ...updated[existingIndex],
          quantity: currentQty + 1,
        };
        return updated;
      } else {
        return [...prevCart, { product, quantity: 1 }];
      }
    });
    setOrderError(null);
  };

  // Update item quantity in cart
  const updateQuantity = (productUuid, newQty) => {
    const targetProduct = products.find((p) => p.uuid === productUuid);
    const maxStock = targetProduct ? targetProduct.stock : 999;

    if (newQty <= 0) {
      removeFromCart(productUuid);
      return;
    }

    if (newQty > maxStock) {
      message.warning(`Maximum available stock is ${maxStock}.`);
      newQty = maxStock;
    }

    setCart((prev) =>
      prev.map((item) =>
        item.product.uuid === productUuid ? { ...item, quantity: newQty } : item
      )
    );
  };

  // Remove item from cart
  const removeFromCart = (productUuid) => {
    setCart((prev) => prev.filter((item) => item.product.uuid !== productUuid));
  };

  // Clear cart
  const clearCart = () => {
    setCart([]);
    setCustomerName("");
    setCustomerEmail("");
    setOrderError(null);
  };

  // Computed totals
  const computedTotals = cart.reduce(
    (acc, item) => {
      const unitPrice = item.product.price;
      const taxRate = item.product.tax_percentage;
      const lineSubtotal = Math.round(unitPrice * item.quantity * 100) / 100;
      const lineTax = Math.round(lineSubtotal * (taxRate / 100) * 100) / 100;
      const lineTotal = Math.round((lineSubtotal + lineTax) * 100) / 100;

      acc.subtotal = Math.round((acc.subtotal + lineSubtotal) * 100) / 100;
      acc.tax = Math.round((acc.tax + lineTax) * 100) / 100;
      acc.grandTotal = Math.round((acc.grandTotal + lineTotal) * 100) / 100;
      return acc;
    },
    { subtotal: 0, tax: 0, grandTotal: 0 }
  );

  // Submit Order
  const handlePlaceOrder = async () => {
    if (!customerEmail.trim() || !customerName.trim()) {
      message.error("Please provide both Customer Name and Email.");
      return;
    }

    if (cart.length === 0) {
      message.error("Cart is empty! Select products to order.");
      return;
    }

    setSubmittingOrder(true);
    setOrderError(null);

    const payload = {
      customer_name: customerName.trim(),
      customer_email: customerEmail.trim().toLowerCase(),
      items: cart.map((item) => ({
        product_uuid: item.product.uuid,
        quantity: item.quantity,
      })),
    };

    try {
      const res = await axiosClient.post("/orders", payload);
      const createdOrder = res.data.data;
      setCompletedOrder(createdOrder);
      setReceiptVisible(true);
      message.success(`Order #${createdOrder.order_number} created successfully!`);
      clearCart();
      fetchProducts(); // Refresh stock in catalog
    } catch (err) {
      console.error(err);
      const errors = err.response?.data?.errors;
      let errMsg = "Failed to place order.";

      if (errors && typeof errors === "object") {
        const errorList = Object.values(errors).flat().filter(Boolean);
        if (errorList.length > 0) {
          errMsg = errorList.join(" ");
        }
      } else if (err.response?.data?.message) {
        errMsg = err.response.data.message;
      }

      setOrderError(errMsg);
      message.error(errMsg);
      fetchProducts(); // Refresh catalog to show actual live stock
    } finally {
      setSubmittingOrder(false);
    }
  };

  // Quick fill sample customer
  const fillSampleCustomer = (name, email) => {
    setCustomerName(name);
    setCustomerEmail(email);
  };

  const productColumns = [
    {
      title: "Product",
      key: "product",
      render: (_, record) => (
        <div>
          <div style={{ fontWeight: 600, fontSize: 14 }}>{record.name}</div>
          <Text type="secondary" style={{ fontSize: 12 }}>
            Code: {record.code}
          </Text>
        </div>
      ),
    },
    {
      title: "Price",
      key: "price",
      width: 100,
      render: (_, record) => (
        <span style={{ fontWeight: 600, color: "#1677ff" }}>
          ${record.price.toFixed(2)}
        </span>
      ),
    },
    {
      title: "Tax",
      key: "tax",
      width: 80,
      render: (_, record) => (
        <Tag color="cyan">{record.tax_percentage}%</Tag>
      ),
    },
    {
      title: "Stock",
      key: "stock",
      width: 110,
      render: (_, record) => {
        if (record.stock <= 0) {
          return <Tag color="error">Out of Stock</Tag>;
        }
        if (record.is_low_stock) {
          return (
            <Tag color="warning" icon={<Badge status="warning" />}>
              Low ({record.stock})
            </Tag>
          );
        }
        return <Tag color="success">In Stock ({record.stock})</Tag>;
      },
    },
    {
      title: "Action",
      key: "action",
      width: 90,
      render: (_, record) => {
        const itemInCart = cart.find((i) => i.product.uuid === record.uuid);
        const inCartQty = itemInCart ? itemInCart.quantity : 0;
        const disabled = record.stock <= 0 || inCartQty >= record.stock;

        return (
          <Button
            type="primary"
            size="small"
            disabled={disabled}
            onClick={() => addToCart(record)}
            style={{ borderRadius: 4 }}
          >
            + Add
          </Button>
        );
      },
    },
  ];

  return (
    <div style={{ padding: "8px 0" }}>
      <Row gutter={[20, 20]}>
        {/* Left Column: Product Catalog */}
        <Col xs={24} lg={13} xl={14}>
          <Card
            title={
              <Space>
                <InboxOutlined style={{ color: "#1677ff" }} />
                <span>Product Catalog</span>
              </Space>
            }
            extra={
              <Space>
                <Link to="/inventory">
                  <Button size="small" icon={<DatabaseOutlined />}>
                    Manage Inventory
                  </Button>
                </Link>
                <Button
                  icon={<ReloadOutlined />}
                  size="small"
                  onClick={fetchProducts}
                  loading={loadingProducts}
                >
                  Refresh
                </Button>
              </Space>
            }
            style={{ borderRadius: 8, boxShadow: "0 2px 8px rgba(0,0,0,0.06)" }}
          >
            <div style={{ marginBottom: 16 }}>
              <Input
                placeholder="Search products by name or SKU code..."
                prefix={<SearchOutlined style={{ color: "#999" }} />}
                allowClear
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                style={{ borderRadius: 6 }}
              />
            </div>

            <Table
              rowKey="uuid"
              columns={productColumns}
              dataSource={products}
              loading={loadingProducts}
              pagination={{ pageSize: 7, size: "small" }}
              size="middle"
            />
          </Card>
        </Col>

        {/* Right Column: Active Order & Counter Checkout */}
        <Col xs={24} lg={11} xl={10}>
          <Card
            title={
              <Space>
                <ShoppingCartOutlined style={{ color: "#52c41a" }} />
                <span>New Counter Order</span>
                {cart.length > 0 && (
                  <Badge count={cart.length} style={{ backgroundColor: "#52c41a" }} />
                )}
              </Space>
            }
            extra={
              cart.length > 0 && (
                <Button type="link" danger size="small" onClick={clearCart}>
                  Clear Cart
                </Button>
              )
            }
            style={{ borderRadius: 8, boxShadow: "0 2px 8px rgba(0,0,0,0.06)" }}
          >
            {/* Customer Details Form */}
            <div style={{ marginBottom: 16, backgroundColor: "#fafafa", padding: 14, borderRadius: 6 }}>
              <div style={{ fontWeight: 600, marginBottom: 10, fontSize: 13, color: "#444" }}>
                CUSTOMER INFORMATION
              </div>
              <Row gutter={8}>
                <Col span={12}>
                  <Input
                    prefix={<UserOutlined style={{ color: "#aaa" }} />}
                    placeholder="Customer Name *"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    style={{ borderRadius: 4 }}
                  />
                </Col>
                <Col span={12}>
                  <Input
                    prefix={<MailOutlined style={{ color: "#aaa" }} />}
                    placeholder="Customer Email *"
                    type="email"
                    value={customerEmail}
                    onChange={(e) => setCustomerEmail(e.target.value)}
                    style={{ borderRadius: 4 }}
                  />
                </Col>
              </Row>
              <div style={{ marginTop: 8, fontSize: 11, color: "#888" }}>
                <span>Quick Fill: </span>
                <a onClick={() => fillSampleCustomer("Alice Johnson", "alice@example.com")}>
                  Alice Johnson
                </a>
                {" | "}
                <a onClick={() => fillSampleCustomer("Bob Smith", "bob.smith@example.com")}>
                  Bob Smith
                </a>
              </div>
            </div>

            {/* Error Notification */}
            {orderError && (
              <Alert
                message="Order Error"
                description={orderError}
                type="error"
                showIcon
                closable
                onClose={() => setOrderError(null)}
                style={{ marginBottom: 14 }}
              />
            )}

            {/* Cart Line Items */}
            <div style={{ minHeight: 180 }}>
              {cart.length === 0 ? (
                <Empty
                  image={Empty.PRESENTED_IMAGE_SIMPLE}
                  description="No items selected yet. Click '+ Add' on the catalog to build an order."
                  style={{ padding: "20px 0" }}
                />
              ) : (
                <div style={{ maxHeight: 260, overflowY: "auto", marginBottom: 16 }}>
                  <table style={{ width: "100%", fontSize: 13, borderCollapse: "collapse" }}>
                    <thead>
                      <tr style={{ borderBottom: "1px solid #eee", color: "#777", textAlign: "left" }}>
                        <th style={{ padding: "6px 4px" }}>Item</th>
                        <th style={{ padding: "6px 4px", textAlign: "center" }}>Qty</th>
                        <th style={{ padding: "6px 4px", textAlign: "right" }}>Price</th>
                        <th style={{ padding: "6px 4px", textAlign: "right" }}>Total</th>
                        <th style={{ padding: "6px 4px", width: 30 }}></th>
                      </tr>
                    </thead>
                    <tbody>
                      {cart.map((item) => {
                        const unitPrice = item.product.price;
                        const taxRate = item.product.tax_percentage;
                        const lineSubtotal = item.quantity * unitPrice;
                        const lineTax = lineSubtotal * (taxRate / 100);
                        const lineTotal = lineSubtotal + lineTax;

                        return (
                          <tr key={item.product.uuid} style={{ borderBottom: "1px solid #f3f3f3" }}>
                            <td style={{ padding: "8px 4px" }}>
                              <div style={{ fontWeight: 600 }}>{item.product.name}</div>
                              <span style={{ fontSize: 11, color: "#888" }}>
                                {item.product.code} (+{taxRate}% tax)
                              </span>
                            </td>
                            <td style={{ padding: "8px 4px", textAlign: "center" }}>
                              <InputNumber
                                min={1}
                                max={item.product.stock}
                                value={item.quantity}
                                size="small"
                                onChange={(val) => updateQuantity(item.product.uuid, val)}
                                style={{ width: 55 }}
                              />
                            </td>
                            <td style={{ padding: "8px 4px", textAlign: "right" }}>
                              ${unitPrice.toFixed(2)}
                            </td>
                            <td style={{ padding: "8px 4px", textAlign: "right", fontWeight: 600 }}>
                              ${lineTotal.toFixed(2)}
                            </td>
                            <td style={{ padding: "8px 4px", textAlign: "right" }}>
                              <Button
                                type="text"
                                danger
                                icon={<DeleteOutlined />}
                                size="small"
                                onClick={() => removeFromCart(item.product.uuid)}
                              />
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </div>

            <Divider style={{ margin: "12px 0" }} />

            {/* Financial Summary */}
            <div style={{ backgroundColor: "#f9f9fb", padding: 14, borderRadius: 6, marginBottom: 16 }}>
              <Row justify="space-between" style={{ marginBottom: 6 }}>
                <Text type="secondary">Subtotal (Pre-Tax):</Text>
                <Text style={{ fontWeight: 500 }}>
                  ${computedTotals.subtotal.toFixed(2)}
                </Text>
              </Row>
              <Row justify="space-between" style={{ marginBottom: 6 }}>
                <Text type="secondary">Total Estimated Tax:</Text>
                <Text style={{ fontWeight: 500, color: "#08979c" }}>
                  +${computedTotals.tax.toFixed(2)}
                </Text>
              </Row>
              <Divider style={{ margin: "8px 0" }} />
              <Row justify="space-between" align="middle">
                <span style={{ fontSize: 16, fontWeight: 700, color: "#111" }}>
                  Grand Total:
                </span>
                <span style={{ fontSize: 20, fontWeight: 800, color: "#52c41a" }}>
                  ${computedTotals.grandTotal.toFixed(2)}
                </span>
              </Row>
            </div>

            {/* Place Order Button */}
            <Button
              type="primary"
              size="large"
              block
              disabled={cart.length === 0}
              loading={submittingOrder}
              onClick={handlePlaceOrder}
              style={{
                height: 48,
                fontSize: 16,
                fontWeight: 600,
                borderRadius: 6,
                backgroundColor: "#52c41a",
                borderColor: "#52c41a",
              }}
            >
              {submittingOrder ? "Deducting Stock & Placing Order..." : "Confirm & Place Order"}
            </Button>
          </Card>
        </Col>
      </Row>

      {/* Order Confirmation Receipt Modal */}
      <Modal
        title={
          <Space>
            <CheckCircleOutlined style={{ color: "#52c41a", fontSize: 22 }} />
            <span style={{ fontSize: 18, fontWeight: 700 }}>Order Receipt</span>
          </Space>
        }
        open={receiptVisible}
        onCancel={() => setReceiptVisible(false)}
        footer={[
          <Button
            key="print"
            icon={<PrinterOutlined />}
            onClick={() => window.print()}
          >
            Print Receipt
          </Button>,
          <Button
            key="close"
            type="primary"
            onClick={() => setReceiptVisible(false)}
          >
            Start New Order
          </Button>,
        ]}
        width={560}
      >
        {completedOrder && (
          <div style={{ padding: "8px 0" }}>
            <Alert
              message="Order Confirmation Queued"
              description={`A confirmation email job has been dispatched to ${completedOrder.customer?.email} via asynchronous queue.`}
              type="success"
              showIcon
              style={{ marginBottom: 16 }}
            />

            <div style={{ border: "1px dashed #ccc", padding: 18, borderRadius: 8, backgroundColor: "#fff" }}>
              <div style={{ textAlign: "center", marginBottom: 16 }}>
                <Title level={4} style={{ margin: 0 }}>STORE COUNTER RECEIPT</Title>
                <Text type="secondary">Order #{completedOrder.order_number}</Text>
                <br />
                <Text type="secondary" style={{ fontSize: 12 }}>
                  {completedOrder.formatted_created_at}
                </Text>
              </div>

              <Divider style={{ margin: "10px 0" }} />

              <div style={{ marginBottom: 12, fontSize: 13 }}>
                <div><strong>Customer:</strong> {completedOrder.customer?.name}</div>
                <div><strong>Email:</strong> {completedOrder.customer?.email}</div>
                <div><strong>Status:</strong> <Tag color="green">{completedOrder.status.toUpperCase()}</Tag></div>
              </div>

              <Table
                dataSource={completedOrder.items || []}
                rowKey="uuid"
                pagination={false}
                size="small"
                columns={[
                  {
                    title: "Item",
                    dataIndex: "product_name",
                    key: "product_name",
                    render: (text, record) => (
                      <div>
                        <strong>{text}</strong>
                        <div style={{ fontSize: 11, color: "#888" }}>
                          {record.quantity} × ${record.unit_price.toFixed(2)} (+{record.tax_percentage}% tax)
                        </div>
                      </div>
                    ),
                  },
                  {
                    title: "Subtotal",
                    dataIndex: "subtotal",
                    key: "subtotal",
                    align: "right",
                    render: (val) => `$${Number(val).toFixed(2)}`,
                  },
                  {
                    title: "Tax",
                    dataIndex: "tax_amount",
                    key: "tax_amount",
                    align: "right",
                    render: (val) => `$${Number(val).toFixed(2)}`,
                  },
                  {
                    title: "Total",
                    dataIndex: "total",
                    key: "total",
                    align: "right",
                    render: (val) => (
                      <strong>${Number(val).toFixed(2)}</strong>
                    ),
                  },
                ]}
              />

              <Divider style={{ margin: "12px 0" }} />

              <div style={{ textAlign: "right", fontSize: 13, lineHeight: 1.8 }}>
                <div>Subtotal: <strong>${Number(completedOrder.subtotal).toFixed(2)}</strong></div>
                <div>Tax Total: <strong>${Number(completedOrder.tax_total).toFixed(2)}</strong></div>
                <div style={{ fontSize: 16, color: "#1677ff", marginTop: 4 }}>
                  Grand Total: <strong>${Number(completedOrder.grand_total).toFixed(2)}</strong></div>
              </div>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
