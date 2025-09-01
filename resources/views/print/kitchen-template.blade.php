@php
    // Order type mapping
    $getOrderTypeString = function ($type) {
        $typeMap = [
            'dine_in' => 'في المطعم',
            'takeaway' => 'خارجي',
            'delivery' => 'توصيل',
            'companies' => 'شركات',
            'talabat' => 'طلبات',
            'web_delivery' => 'اونلاين دليفري',
            'web_takeaway' => 'اونلاين تيك أواي',
        ];
        return $typeMap[$type->value] ?? $type->value;
    };

    // Format dates
    $printDate = now()->setTimezone('Africa/Cairo')->format('d/m/Y H:i:s');
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Order</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'Amiri', serif;
            font-size: 24px;
            font-weight: bold;
            line-height: 1.4;
            color: black;
            background: white;
            padding: 20px;
            width: 572px;
            direction: rtl;
        }

        .kitchen-order {
            width: 100%;
            space-y: 16px;
        }

        .kitchen-order>* {
            margin-bottom: 16px;
        }

        .order-number {
            font-size: 36px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .order-info {
            font-size: 24px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid black;
            margin: 16px 0;
        }

        th,
        td {
            border: 1px solid black;
            padding: 12px 8px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 24px;
        }

        .product-cell {
            text-align: right;
            font-size: 24px;
            font-weight: bold;
        }

        .quantity-cell {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }

        .notes-row {
            font-size: 20px;
            text-align: right;
            background-color: #fff3cd;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="kitchen-order">
        <p class="order-number">Order #{{ $order->order_number }}</p>

        <div class="order-info">
            <p>نوع الطلب : {{ $getOrderTypeString($order->type) }}</p>
            <p>التاريخ : {{ $printDate }}</p>
            @if ($order->type === 'dine_in' && $order->dine_table_number)
                <p>طاولة رقم {{ $order->dine_table_number }}</p>
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orderItems as $item)
                    <tr>
                        <td class="product-cell">{{ $item['name'] }}</td>
                        <td class="quantity-cell">{{ $item['quantity'] }}</td>
                    </tr>
                    @if (!empty($item['notes']))
                        <tr>
                            <td colspan="2" class="notes-row">
                                ملاحظات : {{ $item['notes'] }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
