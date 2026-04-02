<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice['invoice_id'] }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .invoice-box {
            width: 100%;
            border: 1px solid #ddd;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background: #f5f5f5;
        }
        .total {
            text-align: right;
            margin-top: 15px;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Invoice</h2>
    <p><strong>{{ $invoice['invoice_id'] }}</strong></p>
</div>

<div class="invoice-box">
    <p><strong>Status:</strong> {{ $invoice['status'] }}</p>
    <p><strong>Created At:</strong> {{ $invoice['created_at'] }}</p>
    <p><strong>Due Date:</strong> {{ $invoice['due_date'] ?? 'N/A' }}</p>
    <p><strong>Payment Type:</strong> {{ $invoice['payment_type'] }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Task</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($invoice['tasks'] as $index => $task)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $task['task_name'] }}</td>
                <td>{{ $task['status'] }}</td>
                <td>{{ $task['created_at'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" align="center">No tasks</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="total">
        Total Amount: {{ $invoice['amount'] }}
    </div>
</div>

</body>
</html>
