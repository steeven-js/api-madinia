<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-info {
            margin-bottom: 30px;
        }
        .customer-info {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        .total {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FACTURE</h1>
        <p>N° {{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="invoice-info">
        <p><strong>Date :</strong> {{ $order->created_at->format('d/m/Y') }}</p>
        <p><strong>Référence :</strong> #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="customer-info">
        <p><strong>Client :</strong></p>
        <p>{{ $order->customer_name }}</p>
        <p>{{ $order->customer_email }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Prix</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $order->event->title }}</td>
                <td>{{ number_format($order->total_price, 2) }}€</td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        <p>Total TTC : {{ number_format($order->total_price, 2) }}€</p>
    </div>
</body>
</html>
