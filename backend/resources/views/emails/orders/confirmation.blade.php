<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333333;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }
        .email-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%);
            color: #ffffff;
            padding: 30px 24px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0 0 8px 0;
            font-size: 24px;
            font-weight: 700;
        }
        .email-header p {
            margin: 0;
            font-size: 14px;
            color: #c7d2fe;
        }
        .email-body {
            padding: 24px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #64748b;
            font-weight: 500;
        }
        .info-value {
            font-weight: 600;
            color: #1e293b;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 14px;
        }
        table.items-table th {
            background-color: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 10px 8px;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        table.items-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 100%;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .totals-table td {
            padding: 6px 8px;
        }
        .grand-total {
            font-size: 18px;
            font-weight: 700;
            color: #16a34a;
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
        }
        .email-footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>Thank You for Your Order!</h1>
            <p>Order #{{ $order->order_number }} has been confirmed.</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p style="font-size: 15px; margin-top: 0;">
                Hello <strong>{{ $order->customer->name ?? 'Valued Customer' }}</strong>,
            </p>
            <p style="font-size: 14px; color: #475569;">
                Your order has been placed successfully and our store counter team is preparing your items.
            </p>

            <!-- Order Details Box -->
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Order Number:</span>
                    <span class="info-value">{{ $order->order_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer Name:</span>
                    <span class="info-value">{{ $order->customer->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer Email:</span>
                    <span class="info-value">{{ $order->customer->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Status:</span>
                    <span class="info-value" style="color: #16a34a;">{{ strtoupper($order->status) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-center">Tax</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->name ?? 'Product' }}</strong><br>
                                <small style="color: #64748b;">{{ $item->product->code ?? 'N/A' }}</small>
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-center">{{ (float) $item->tax_percentage }}%</td>
                            <td class="text-right">${{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <table class="totals-table">
                <tr>
                    <td style="width: 60%;"></td>
                    <td style="color: #64748b; font-weight: 500;">Subtotal:</td>
                    <td class="text-right" style="font-weight: 600;">${{ number_format($order->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td style="color: #64748b; font-weight: 500;">Tax Total:</td>
                    <td class="text-right" style="font-weight: 600;">${{ number_format($order->tax_total, 2) }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="grand-total">Grand Total:</td>
                    <td class="text-right grand-total">${{ number_format($order->grand_total, 2) }}</td>
                </tr>
            </table>

            <p style="font-size: 14px; color: #475569; margin-bottom: 0;">
                If you have any questions or need to make adjustments to your order, please reply directly to this email.
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p style="margin: 0 0 4px 0;">StoreCounter Retail POS System</p>
            <p style="margin: 0;">Automated Order Confirmation Service</p>
        </div>
    </div>
</body>
</html>
