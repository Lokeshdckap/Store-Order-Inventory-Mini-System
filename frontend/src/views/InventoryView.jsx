import React, { useState, useEffect } from "react";
import {
  Card,
  Table,
  Tag,
  Button,
  Input,
  InputNumber,
  Alert,
  Typography,
  Space,
  Row,
  Col,
  Statistic,
  Progress,
  Tabs,
  Tooltip,
  Modal,
  Form,
  Popconfirm,
  message,
} from "antd";
import {
  WarningOutlined,
  ReloadOutlined,
  ToolOutlined,
  DatabaseOutlined,
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  SearchOutlined,
  SyncOutlined,
} from "@ant-design/icons";
import axiosClient from "../../axiosClient";

const { Title, Text } = Typography;

export default function InventoryView() {
  const [allProducts, setAllProducts] = useState([]);
  const [lowStockProducts, setLowStockProducts] = useState([]);
  const [lowStockMeta, setLowStockMeta] = useState({ threshold: 5, total_low_stock_items: 0 });
  const [threshold, setThreshold] = useState(5);
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");

  // Modals state
  const [addModalVisible, setAddModalVisible] = useState(false);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [restockModalVisible, setRestockModalVisible] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  // Forms
  const [addForm] = Form.useForm();
  const [editForm] = Form.useForm();
  const [restockForm] = Form.useForm();

  const fetchAllProducts = async () => {
    setLoading(true);
    try {
      const [allRes, lowRes] = await Promise.all([
        axiosClient.get("/products"),
        axiosClient.get("/products/low-stock", { params: { threshold } }),
      ]);
      setAllProducts(allRes.data.data || []);
      setLowStockProducts(lowRes.data.data || []);
      setLowStockMeta(lowRes.data.meta || { threshold, total_low_stock_items: 0 });
    } catch (err) {
      console.error(err);
      message.error("Failed to load inventory data.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAllProducts();
  }, []);

  const handleThresholdChange = async () => {
    setLoading(true);
    try {
      const res = await axiosClient.get("/products/low-stock", {
        params: { threshold },
      });
      setLowStockProducts(res.data.data || []);
      setLowStockMeta(res.data.meta || { threshold, total_low_stock_items: 0 });
    } catch (err) {
      message.error("Failed to update low-stock filter.");
    } finally {
      setLoading(false);
    }
  };

  // --- Add Product Handler ---
  const handleCreateProduct = async (values) => {
    setActionLoading(true);
    try {
      const payload = {
        name: values.name.trim(),
        code: values.code.trim().toUpperCase(),
        price: Number(values.price),
        tax_percentage: Number(values.tax_percentage || 0),
        stock: Number(values.stock || 0),
      };

      const res = await axiosClient.post("/products", payload);
      message.success(`Product "${res.data.data?.name}" created successfully!`);
      setAddModalVisible(false);
      addForm.resetFields();
      fetchAllProducts();
    } catch (err) {
      console.error(err);
      const errMsg =
        err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(" ")
          : err.response?.data?.message || "Failed to create product.";
      message.error(errMsg);
    } finally {
      setActionLoading(false);
    }
  };

  // --- Edit Product Handler ---
  const openEditModal = (product) => {
    setSelectedProduct(product);
    editForm.setFieldsValue({
      name: product.name,
      code: product.code,
      price: product.price,
      tax_percentage: product.tax_percentage,
      stock: product.stock,
    });
    setEditModalVisible(true);
  };

  const handleUpdateProduct = async (values) => {
    if (!selectedProduct) return;
    setActionLoading(true);
    try {
      const payload = {
        name: values.name.trim(),
        code: values.code.trim().toUpperCase(),
        price: Number(values.price),
        tax_percentage: Number(values.tax_percentage || 0),
        stock: Number(values.stock),
      };

      const res = await axiosClient.put(`/products/${selectedProduct.uuid}`, payload);
      message.success(`Product "${res.data.data?.name}" updated successfully!`);
      setEditModalVisible(false);
      setSelectedProduct(null);
      fetchAllProducts();
    } catch (err) {
      console.error(err);
      const errMsg =
        err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(" ")
          : err.response?.data?.message || "Failed to update product.";
      message.error(errMsg);
    } finally {
      setActionLoading(false);
    }
  };

  // --- Quick Restock Handler ---
  const openRestockModal = (product) => {
    setSelectedProduct(product);
    restockForm.setFieldsValue({
      currentStock: product.stock,
      newStock: product.stock,
      adjustment: 0,
    });
    setRestockModalVisible(true);
  };

  const handleApplyRestock = async (values) => {
    if (!selectedProduct) return;
    setActionLoading(true);
    try {
      const newStockVal = Number(values.newStock);
      const res = await axiosClient.patch(`/products/${selectedProduct.uuid}/stock`, {
        stock: newStockVal,
      });

      message.success(`Stock for "${selectedProduct.name}" updated to ${newStockVal} units!`);
      setRestockModalVisible(false);
      setSelectedProduct(null);
      fetchAllProducts();
    } catch (err) {
      console.error(err);
      const errMsg = err.response?.data?.message || "Failed to update stock.";
      message.error(errMsg);
    } finally {
      setActionLoading(false);
    }
  };

  // --- Delete Product Handler ---
  const handleDeleteProduct = async (product) => {
    setLoading(true);
    try {
      const res = await axiosClient.delete(`/products/${product.uuid}`);
      message.success(res.data?.message || `Product "${product.name}" deleted.`);
      fetchAllProducts();
    } catch (err) {
      console.error(err);
      const errMsg =
        err.response?.data?.errors?.product?.[0] ||
        err.response?.data?.message ||
        "Failed to delete product.";
      message.error(errMsg, 6);
    } finally {
      setLoading(false);
    }
  };

  const stockStatusColor = (stock, thresh) => {
    if (stock === 0) return "#ff4d4f";
    if (stock <= thresh) return "#faad14";
    return "#52c41a";
  };

  // Filter products by search query
  const filteredAllProducts = allProducts.filter((p) => {
    if (!searchQuery.trim()) return true;
    const q = searchQuery.toLowerCase().trim();
    return (
      p.name?.toLowerCase().includes(q) ||
      p.code?.toLowerCase().includes(q)
    );
  });

  const filteredLowStockProducts = lowStockProducts.filter((p) => {
    if (!searchQuery.trim()) return true;
    const q = searchQuery.toLowerCase().trim();
    return (
      p.name?.toLowerCase().includes(q) ||
      p.code?.toLowerCase().includes(q)
    );
  });

  const productColumns = [
    {
      title: "Product Name",
      key: "name",
      render: (_, record) => (
        <div>
          <Text strong>{record.name}</Text>
          <br />
          <Text type="secondary" style={{ fontSize: 12 }}>
            {record.code}
          </Text>
        </div>
      ),
    },
    {
      title: "Price",
      dataIndex: "price",
      width: 100,
      align: "right",
      render: (v) => (
        <span style={{ color: "#1677ff", fontWeight: 600 }}>
          ${Number(v).toFixed(2)}
        </span>
      ),
    },
    {
      title: "Tax %",
      dataIndex: "tax_percentage",
      width: 80,
      align: "center",
      render: (v) => <Tag color="cyan">{v}%</Tag>,
    },
    {
      title: "Stock Level",
      key: "stock_bar",
      width: 160,
      render: (_, record) => {
        const max = 50;
        const pct = Math.min(Math.round((record.stock / max) * 100), 100);
        const color = stockStatusColor(record.stock, lowStockMeta.threshold || threshold);
        return (
          <Tooltip title={`${record.stock} units`}>
            <Progress
              percent={pct}
              strokeColor={color}
              trailColor="#f0f0f0"
              size="small"
              format={() => record.stock}
            />
          </Tooltip>
        );
      },
    },
    {
      title: "Status",
      key: "status",
      width: 120,
      render: (_, record) => {
        if (record.stock === 0)
          return <Tag color="error" icon={<WarningOutlined />}>Out of Stock</Tag>;
        if (record.is_low_stock || record.stock <= (lowStockMeta.threshold || threshold))
          return <Tag color="warning" icon={<WarningOutlined />}>Low Stock</Tag>;
        return <Tag color="success">Healthy</Tag>;
      },
    },
    {
      title: "Actions",
      key: "actions",
      width: 220,
      align: "center",
      render: (_, record) => (
        <Space size="small">
          <Tooltip title="Quick Restock / Adjust Stock">
            <Button
              size="small"
              type="primary"
              icon={<SyncOutlined />}
              onClick={() => openRestockModal(record)}
              style={{ backgroundColor: "#13c2c2", borderColor: "#13c2c2" }}
            >
              Restock
            </Button>
          </Tooltip>

          <Tooltip title="Edit Product Details">
            <Button
              size="small"
              icon={<EditOutlined />}
              onClick={() => openEditModal(record)}
            />
          </Tooltip>

          <Tooltip title="Delete Product">
            <Popconfirm
              title={`Delete "${record.name}"?`}
              description="Cannot be deleted if referenced in customer orders."
              okText="Yes, Delete"
              cancelText="Cancel"
              okButtonProps={{ danger: true }}
              onConfirm={() => handleDeleteProduct(record)}
            >
              <Button size="small" danger icon={<DeleteOutlined />} />
            </Popconfirm>
          </Tooltip>
        </Space>
      ),
    },
  ];

  const outOfStockCount = allProducts.filter((p) => p.stock === 0).length;
  const healthyCount = allProducts.filter(
    (p) => p.stock > 0 && !p.is_low_stock
  ).length;

  return (
    <div>
      <Row justify="space-between" align="middle" style={{ marginBottom: 16 }}>
        <Col>
          <Title level={3} style={{ marginBottom: 4 }}>
            <DatabaseOutlined style={{ marginRight: 8, color: "#1677ff" }} />
            Inventory & Product Management
          </Title>
          <Text type="secondary">
            Manage product catalog, stock levels, restocking, and low-stock alert thresholds.
          </Text>
        </Col>
        <Col>
          <Space>
            <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={() => setAddModalVisible(true)}
              style={{ borderRadius: 6 }}
            >
              Add New Product
            </Button>
            <Button
              icon={<ReloadOutlined />}
              onClick={fetchAllProducts}
              loading={loading}
              style={{ borderRadius: 6 }}
            >
              Refresh
            </Button>
          </Space>
        </Col>
      </Row>

      {/* Summary Stats */}
      <Row gutter={[16, 16]} style={{ marginBottom: 20 }}>
        <Col xs={12} sm={6}>
          <Card bordered={false} style={{ backgroundColor: "#e6f4ff", borderRadius: 8 }}>
            <Statistic
              title="Total Catalog SKUs"
              value={allProducts.length}
              suffix="items"
              valueStyle={{ color: "#1677ff" }}
            />
          </Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card bordered={false} style={{ backgroundColor: "#fff2e8", borderRadius: 8 }}>
            <Statistic
              title={`Low Stock (≤ ${lowStockMeta.threshold || threshold})`}
              value={lowStockMeta.total_low_stock_items || 0}
              suffix="items"
              valueStyle={{ color: "#fa8c16" }}
              prefix={<WarningOutlined />}
            />
          </Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card bordered={false} style={{ backgroundColor: "#fff1f0", borderRadius: 8 }}>
            <Statistic
              title="Out of Stock"
              value={outOfStockCount}
              suffix="items"
              valueStyle={{ color: "#ff4d4f" }}
              prefix={<WarningOutlined />}
            />
          </Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card bordered={false} style={{ backgroundColor: "#f6ffed", borderRadius: 8 }}>
            <Statistic
              title="Healthy Stock"
              value={healthyCount}
              suffix="items"
              valueStyle={{ color: "#52c41a" }}
            />
          </Card>
        </Col>
      </Row>

      {/* Low-stock warning banner */}
      {lowStockMeta.total_low_stock_items > 0 && (
        <Alert
          type="warning"
          showIcon
          icon={<WarningOutlined />}
          message={`${lowStockMeta.total_low_stock_items} product(s) are at or below the low-stock threshold of ${lowStockMeta.threshold || threshold} units. Please restock soon!`}
          style={{ marginBottom: 16, borderRadius: 6 }}
        />
      )}

      {/* Search & Threshold Filter Card */}
      <Card
        style={{ borderRadius: 8, marginBottom: 16 }}
        bodyStyle={{ padding: "14px 20px" }}
      >
        <Row align="middle" gutter={[16, 12]} justify="space-between">
          <Col xs={24} md={10}>
            <Input
              placeholder="Search products by Name or SKU code..."
              prefix={<SearchOutlined style={{ color: "#999" }} />}
              allowClear
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              style={{ borderRadius: 6 }}
            />
          </Col>
          <Col xs={24} md={14} style={{ textAlign: "right" }}>
            <Space>
              <ToolOutlined style={{ color: "#666" }} />
              <Text strong>Low-Stock Alert Threshold:</Text>
              <InputNumber
                min={0}
                max={100}
                value={threshold}
                onChange={(v) => setThreshold(v)}
                style={{ width: 75 }}
                size="small"
              />
              <Text type="secondary" style={{ fontSize: 12 }}>
                units
              </Text>
              <Button
                size="small"
                type="primary"
                onClick={handleThresholdChange}
                loading={loading}
              >
                Apply
              </Button>
            </Space>
          </Col>
        </Row>
      </Card>

      {/* Product Table Tabs */}
      <Card style={{ borderRadius: 8, boxShadow: "0 2px 8px rgba(0,0,0,0.06)" }}>
        <Tabs
          activeKey={activeTab}
          onChange={setActiveTab}
          items={[
            {
              key: "all",
              label: (
                <Space>
                  <DatabaseOutlined />
                  All Products
                  <Tag>{filteredAllProducts.length}</Tag>
                </Space>
              ),
              children: (
                <Table
                  rowKey="uuid"
                  columns={productColumns}
                  dataSource={filteredAllProducts}
                  loading={loading}
                  pagination={{ pageSize: 10, size: "small" }}
                  size="middle"
                  rowClassName={(record) => {
                    if (record.stock === 0) return "row-out-of-stock";
                    if (record.is_low_stock) return "row-low-stock";
                    return "";
                  }}
                />
              ),
            },
            {
              key: "lowstock",
              label: (
                <Space>
                  <WarningOutlined style={{ color: "#faad14" }} />
                  Low Stock Alerts
                  <Tag color="warning">{filteredLowStockProducts.length}</Tag>
                </Space>
              ),
              children:
                filteredLowStockProducts.length === 0 ? (
                  <div style={{ textAlign: "center", padding: 40, color: "#52c41a" }}>
                    <div style={{ fontSize: 36, marginBottom: 12 }}>✅</div>
                    <Title level={5} style={{ color: "#52c41a" }}>
                      All products are well-stocked!
                    </Title>
                    <Text type="secondary">
                      No products are at or below the threshold of {lowStockMeta.threshold || threshold} units.
                    </Text>
                  </div>
                ) : (
                  <Table
                    rowKey="uuid"
                    columns={productColumns}
                    dataSource={filteredLowStockProducts}
                    loading={loading}
                    pagination={{ pageSize: 10, size: "small" }}
                    size="middle"
                  />
                ),
            },
          ]}
        />
      </Card>

      {/* --- MODAL 1: ADD NEW PRODUCT --- */}
      <Modal
        title="Add New Product to Catalog"
        open={addModalVisible}
        onCancel={() => {
          setAddModalVisible(false);
          addForm.resetFields();
        }}
        onOk={() => addForm.submit()}
        confirmLoading={actionLoading}
        okText="Create Product"
        destroyOnClose
      >
        <Form
          form={addForm}
          layout="vertical"
          onFinish={handleCreateProduct}
          initialValues={{ tax_percentage: 18, stock: 10 }}
        >
          <Form.Item
            name="name"
            label="Product Name"
            rules={[{ required: true, message: "Please enter product name" }]}
          >
            <Input placeholder="e.g. Wireless Ergonomic Mouse" />
          </Form.Item>

          <Form.Item
            name="code"
            label="SKU Code"
            rules={[{ required: true, message: "Please enter unique SKU code" }]}
            extra="SKU code will be auto-converted to uppercase (e.g. SKU-MS01)"
          >
            <Input placeholder="e.g. SKU-MS01" />
          </Form.Item>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="price"
                label="Price ($)"
                rules={[{ required: true, message: "Please enter unit price" }]}
              >
                <InputNumber
                  min={0.01}
                  step={0.5}
                  style={{ width: "100%" }}
                  placeholder="29.99"
                />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="tax_percentage"
                label="Tax Rate (%)"
                rules={[{ required: true, message: "Please enter tax rate" }]}
              >
                <InputNumber
                  min={0}
                  max={100}
                  style={{ width: "100%" }}
                  placeholder="18"
                />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item
            name="stock"
            label="Initial Stock Quantity"
            rules={[{ required: true, message: "Please enter initial stock" }]}
          >
            <InputNumber min={0} style={{ width: "100%" }} placeholder="50" />
          </Form.Item>
        </Form>
      </Modal>

      {/* --- MODAL 2: EDIT PRODUCT --- */}
      <Modal
        title={`Edit Product: ${selectedProduct?.name}`}
        open={editModalVisible}
        onCancel={() => {
          setEditModalVisible(false);
          setSelectedProduct(null);
        }}
        onOk={() => editForm.submit()}
        confirmLoading={actionLoading}
        okText="Save Changes"
        destroyOnClose
      >
        <Form form={editForm} layout="vertical" onFinish={handleUpdateProduct}>
          <Form.Item
            name="name"
            label="Product Name"
            rules={[{ required: true, message: "Product name cannot be empty" }]}
          >
            <Input />
          </Form.Item>

          <Form.Item
            name="code"
            label="SKU Code"
            rules={[{ required: true, message: "SKU code cannot be empty" }]}
          >
            <Input />
          </Form.Item>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                name="price"
                label="Price ($)"
                rules={[{ required: true, message: "Price cannot be empty" }]}
              >
                <InputNumber min={0.01} step={0.5} style={{ width: "100%" }} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                name="tax_percentage"
                label="Tax Rate (%)"
                rules={[{ required: true, message: "Tax percentage cannot be empty" }]}
              >
                <InputNumber min={0} max={100} style={{ width: "100%" }} />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item
            name="stock"
            label="Stock Quantity"
            rules={[{ required: true, message: "Stock cannot be empty" }]}
          >
            <InputNumber min={0} style={{ width: "100%" }} />
          </Form.Item>
        </Form>
      </Modal>

      {/* --- MODAL 3: QUICK RESTOCK --- */}
      <Modal
        title={`Restock Product: ${selectedProduct?.name}`}
        open={restockModalVisible}
        onCancel={() => {
          setRestockModalVisible(false);
          setSelectedProduct(null);
        }}
        onOk={() => restockForm.submit()}
        confirmLoading={actionLoading}
        okText="Apply Stock Update"
        destroyOnClose
        width={480}
      >
        {selectedProduct && (
          <div>
            <div style={{ marginBottom: 16, padding: 12, backgroundColor: "#f9f9f9", borderRadius: 6 }}>
              <div><strong>SKU:</strong> {selectedProduct.code}</div>
              <div><strong>Current Stock on Hand:</strong> <Tag color="blue">{selectedProduct.stock} units</Tag></div>
            </div>

            <Form
              form={restockForm}
              layout="vertical"
              onFinish={handleApplyRestock}
            >
              <Form.Item
                name="newStock"
                label="New Total Stock on Hand"
                rules={[{ required: true, message: "Please specify stock quantity" }]}
              >
                <InputNumber min={0} style={{ width: "100%" }} size="large" />
              </Form.Item>

              <div style={{ marginBottom: 16 }}>
                <Text type="secondary" style={{ display: "block", marginBottom: 6 }}>
                  Quick Add Units:
                </Text>
                <Space wrap>
                  {[+5, +10, +25, +50].map((addAmount) => (
                    <Button
                      key={addAmount}
                      size="small"
                      onClick={() => {
                        const current = restockForm.getFieldValue("newStock") || 0;
                        restockForm.setFieldsValue({ newStock: current + addAmount });
                      }}
                    >
                      {addAmount} Units
                    </Button>
                  ))}
                  <Button
                    size="small"
                    danger
                    onClick={() => restockForm.setFieldsValue({ newStock: 0 })}
                  >
                    Set to 0 (Out of Stock)
                  </Button>
                </Space>
              </div>
            </Form>
          </div>
        )}
      </Modal>

      <style>{`
        .row-out-of-stock td { background-color: #fff2f0 !important; }
        .row-low-stock td { background-color: #fffbe6 !important; }
      `}</style>
    </div>
  );
}
