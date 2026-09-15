import React, { useState } from "react";
import {
  Card,
  Input,
  Button,
  Table,
  Tag,
  Typography,
  Space,
  Empty,
  Alert,
  Collapse,
  Descriptions,
  Divider,
  Row,
  Col,
  Statistic,
} from "antd";
import {
  SearchOutlined,
  MailOutlined,
  HistoryOutlined,
  ShoppingOutlined,
} from "@ant-design/icons";
import axiosClient from "../../axiosClient";

const { Title, Text } = Typography;
const { Panel } = Collapse;

export default function OrderHistoryView() {
  const [email, setEmail] = useState("");
  const [searching, setSearching] = useState(false);
  const [result, setResult] = useState(null);
  const [searched, setSearched] = useState(false);

  const handleSearch = async () => {
    const q = email.trim();
    if (!q) return;

    setSearching(true);
    setSearched(false);
    setResult(null);

    try {
      const res = await axiosClient.get(
        `/customers/${encodeURIComponent(q)}/orders`
      );
      setResult(res.data);
    } catch (err) {
      console.error(err);
      setResult({ data: [], customer: null, meta: { total_orders: 0 } });
    } finally {
      setSearching(false);
      setSearched(true);
    }
  };

  const handleKeyDown = (e) => {
    if (e.key === "Enter") handleSearch();
  };

  const sampleEmails = [
    "alice@example.com",
    "bob.smith@example.com",
    "carol.davis@example.com",
  ];

  return (
    <div>
      <Title level={3} style={{ marginBottom: 4 }}>
        <HistoryOutlined style={{ marginRight: 8, color: "#1677ff" }} />
        Customer Order History
      </Title>
      <Text type="secondary" style={{ display: "block", marginBottom: 24 }}>
        Enter a customer's email address to look up their complete order history.
      </Text>

      {/* Search Card */}
      <Card
        style={{ borderRadius: 8, marginBottom: 20, boxShadow: "0 2px 8px rgba(0,0,0,0.06)" }}
      >
        <Row gutter={12} align="middle">
          <Col flex="auto">
            <Input
              size="large"
              prefix={<MailOutlined style={{ color: "#aaa" }} />}
              placeholder="Enter customer email address…"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              onKeyDown={handleKeyDown}
              style={{ borderRadius: 6 }}
            />
          </Col>
          <Col>
            <Button
              type="primary"
              size="large"
              icon={<SearchOutlined />}
              loading={searching}
              onClick={handleSearch}
              style={{ borderRadius: 6 }}
            >
              Lookup Orders
            </Button>
          </Col>
        </Row>

        <div style={{ marginTop: 10, fontSize: 12, color: "#888" }}>
          <span>Quick fill: </span>
          {sampleEmails.map((e) => (
            <span key={e}>
              <a onClick={() => setEmail(e)}>{e}</a>
              {e !== sampleEmails[sampleEmails.length - 1] && " | "}
            </span>
          ))}
        </div>
      </Card>

      {/* Results */}
      {searched && result && (
        <>
          {result.customer ? (
            <>
              {/* Customer Summary Card */}
              <Card
                style={{ borderRadius: 8, marginBottom: 16, backgroundColor: "#f0f7ff" }}
                bodyStyle={{ padding: "16px 20px" }}
              >
                <Row gutter={32} align="middle">
                  <Col>
                    <Statistic
                      title="Customer Name"
                      value={result.customer.name}
                      valueStyle={{ fontSize: 18 }}
                    />
                  </Col>
                  <Col>
                    <Statistic
                      title="Email"
                      value={result.customer.email}
                      valueStyle={{ fontSize: 16 }}
                    />
                  </Col>
                  <Col>
                    <Statistic
                      title="Total Orders"
                      value={result.meta.total_orders}
                      suffix="order(s)"
                      valueStyle={{ color: "#1677ff" }}
                    />
                  </Col>
                </Row>
              </Card>

              {/* Orders List */}
              {result.data.length === 0 ? (
                <Empty description="No orders found for this customer." />
              ) : (
                <Collapse
                  expandIconPosition="end"
                  style={{ borderRadius: 8 }}
                  ghost
                >
                  {result.data.map((order) => (
                    <Panel
                      key={order.uuid}
                      header={
                        <Row justify="space-between" align="middle" style={{ width: "100%" }}>
                          <Space size="large">
                            <ShoppingOutlined style={{ color: "#1677ff" }} />
                            <span>
                              <strong>{order.order_number}</strong>
                              <Text type="secondary" style={{ marginLeft: 12, fontSize: 12 }}>
                                {order.formatted_created_at}
                              </Text>
                            </span>
                          </Space>
                          <Space>
                            <Tag color="green">{order.status.toUpperCase()}</Tag>
                            <Text strong style={{ color: "#52c41a", fontSize: 16 }}>
                              ${Number(order.grand_total).toFixed(2)}
                            </Text>
                          </Space>
                        </Row>
                      }
                      style={{
                        marginBottom: 8,
                        backgroundColor: "#fff",
                        borderRadius: 8,
                        border: "1px solid #eee",
                      }}
                    >
                      {/* Financial Summary */}
                      <Row gutter={16} style={{ marginBottom: 16 }}>
                        <Col xs={8}>
                          <div style={{ textAlign: "center", padding: 12, backgroundColor: "#f9f9f9", borderRadius: 6 }}>
                            <div style={{ fontSize: 12, color: "#888", marginBottom: 4 }}>Subtotal</div>
                            <div style={{ fontSize: 18, fontWeight: 700 }}>
                              ${Number(order.subtotal).toFixed(2)}
                            </div>
                          </div>
                        </Col>
                        <Col xs={8}>
                          <div style={{ textAlign: "center", padding: 12, backgroundColor: "#f0fafa", borderRadius: 6 }}>
                            <div style={{ fontSize: 12, color: "#888", marginBottom: 4 }}>Tax Total</div>
                            <div style={{ fontSize: 18, fontWeight: 700, color: "#08979c" }}>
                              ${Number(order.tax_total).toFixed(2)}
                            </div>
                          </div>
                        </Col>
                        <Col xs={8}>
                          <div style={{ textAlign: "center", padding: 12, backgroundColor: "#f6ffed", borderRadius: 6 }}>
                            <div style={{ fontSize: 12, color: "#888", marginBottom: 4 }}>Grand Total</div>
                            <div style={{ fontSize: 18, fontWeight: 700, color: "#52c41a" }}>
                              ${Number(order.grand_total).toFixed(2)}
                            </div>
                          </div>
                        </Col>
                      </Row>

                      {/* Line Items Table */}
                      <Table
                        dataSource={order.items || []}
                        rowKey="uuid"
                        pagination={false}
                        size="small"
                        columns={[
                          {
                            title: "Product",
                            key: "product",
                            render: (_, record) => (
                              <div>
                                <div style={{ fontWeight: 600 }}>{record.product_name}</div>
                                <Text type="secondary" style={{ fontSize: 11 }}>
                                  {record.product_code}
                                </Text>
                              </div>
                            ),
                          },
                          {
                            title: "Qty",
                            dataIndex: "quantity",
                            align: "center",
                            width: 60,
                          },
                          {
                            title: "Unit Price",
                            dataIndex: "unit_price",
                            align: "right",
                            width: 100,
                            render: (v) => `$${Number(v).toFixed(2)}`,
                          },
                          {
                            title: "Tax",
                            dataIndex: "tax_percentage",
                            align: "center",
                            width: 70,
                            render: (v) => <Tag color="cyan">{v}%</Tag>,
                          },
                          {
                            title: "Subtotal",
                            dataIndex: "subtotal",
                            align: "right",
                            width: 100,
                            render: (v) => `$${Number(v).toFixed(2)}`,
                          },
                          {
                            title: "Tax Amt",
                            dataIndex: "tax_amount",
                            align: "right",
                            width: 90,
                            render: (v) => `$${Number(v).toFixed(2)}`,
                          },
                          {
                            title: "Total",
                            dataIndex: "total",
                            align: "right",
                            width: 100,
                            render: (v) => (
                              <strong style={{ color: "#1677ff" }}>
                                ${Number(v).toFixed(2)}
                              </strong>
                            ),
                          },
                        ]}
                      />
                    </Panel>
                  ))}
                </Collapse>
              )}
            </>
          ) : (
            <Alert
              type="info"
              showIcon
              message="Customer Not Found"
              description={`No customer account was found with email "${email}". They may not have placed an order yet.`}
            />
          )}
        </>
      )}
    </div>
  );
}
