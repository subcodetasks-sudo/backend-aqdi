<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; direction: rtl; }
        h1 { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: right; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h1>{{ $payload['channels']['range_label_ar'] ?? 'تقرير القنوات' }}</h1>
    <table>
        <thead>
            <tr>
                <th>القناة</th>
                <th>الصرف</th>
                <th>الإيراد</th>
                <th>ROAS</th>
                <th>عملاء محتملون</th>
                <th>تحويلات</th>
                <th>CAC</th>
                <th>الربح</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payload['channels']['rows'] as $row)
                <tr>
                    <td>{{ $row['label_ar'] }}</td>
                    <td>{{ $row['spend'] }}</td>
                    <td>{{ $row['revenue'] }}</td>
                    <td>{{ $row['roas'] ?? '—' }}</td>
                    <td>{{ $row['leads'] }}</td>
                    <td>{{ $row['conversions'] }}</td>
                    <td>{{ $row['cac'] ?? '—' }}</td>
                    <td>{{ $row['profit'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td>الإجمالي</td>
                <td>{{ $payload['channels']['total']['spend'] }}</td>
                <td>{{ $payload['channels']['total']['revenue'] }}</td>
                <td>{{ $payload['channels']['total']['roas'] ?? '—' }}</td>
                <td>{{ $payload['channels']['total']['leads'] }}</td>
                <td>{{ $payload['channels']['total']['conversions'] }}</td>
                <td>{{ $payload['channels']['total']['cac'] ?? '—' }}</td>
                <td>{{ $payload['channels']['total']['profit'] }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
