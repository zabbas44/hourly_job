<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Statement - {{ $worker->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #222;
            margin: 24px;
        }

        .header,
        .summary {
            margin-bottom: 20px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-size: 14px;
        }

        .note {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 12px;
        }

        .actions {
            margin-bottom: 16px;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Print / Imprimir / پرنٹ</button>
    </div>

    <div class="header">
        <h1>{{ $worker->name }}</h1>
        <p>{{ $worker->email }} | {{ $worker->phone }}</p>
        <p>{{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="summary">
        <div class="card">
            <strong>Total earned</strong>
            <div>{{ '€'.number_format($summary['total_earned'], 2) }}</div>
        </div>
        <div class="card">
            <strong>Total paid</strong>
            <div>{{ '€'.number_format($summary['total_paid'], 2) }}</div>
        </div>
        <div class="card">
            <strong>Outstanding</strong>
            <div>{{ '€'.number_format($summary['outstanding'], 2) }}</div>
        </div>
        <div class="card">
            <strong>Credit</strong>
            <div>{{ '€'.number_format($summary['credit'], 2) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Type</th>
                <th>Method</th>
                <th>Detail</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary['history'] as $row)
                <tr>
                    <td>{{ $row['date']->format('d M Y') }}</td>
                    <td>{{ $row['time'] ?? '—' }}</td>
                    <td>{{ $row['type'] === 'charge' ? 'Charge' : 'Payment' }}</td>
                    <td>{{ $row['method'] ? str_replace('_', ' ', ucfirst($row['method'])) : '—' }}</td>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['debit'] > 0 ? '€'.number_format($row['debit'], 2) : '—' }}</td>
                    <td>{{ $row['credit'] > 0 ? '€'.number_format($row['credit'], 2) : '—' }}</td>
                    <td>{{ '€'.number_format($row['balance'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="note">
        <p><strong>English:</strong> {{ $summary['balance_note']['en'] }}</p>
        <p><strong>Español:</strong> {{ $summary['balance_note']['es'] }}</p>
        <p><strong>اردو:</strong> {{ $summary['balance_note']['ur'] }}</p>
    </div>
</body>
</html>
