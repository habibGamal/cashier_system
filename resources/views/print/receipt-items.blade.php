<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Items</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            color: black;
        }

        body {
            font-family: "DejaVu Sans", "DejaVu Serif", "DejaVu Sans Mono", sans-serif;
            direction: rtl;
            font-size: 22px;
            font-weight: bold;
            line-height: 1.4;
            color: black;
            background: white;
            padding-left: 20px;
            padding-right: 20px;
            width: 572px;
            direction: rtl;
        }

        .receipt {
            width: 100%;
            /* space-y: 16px; */
        }

        .receipt>* {
            /* margin-bottom: 16px; */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid black;
            /* margin: 16px 0; */
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
            word-break: break-word;
            font-size: 20px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .product-cell {
            text-align: right;
            width: 150px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .number-cell {
            max-width: 80px;
            font-size: 18px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body>
    <div class="receipt">
        <table>
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>الاجمالي</th>
                    <th>الخصم</th>
                    <th>الصافي</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $itemSubtotal = $item->quantity * $item->price;
                        $itemDiscount = $item->item_discount ?? 0;
                        $itemTotal = $itemSubtotal - $itemDiscount;
                    @endphp
                    <tr>
                        <td class="product-cell">{{ $item->product->name }}</td>
                        <td class="number-cell">{{ $item->quantity }}</td>
                        <td class="number-cell">{{ number_format($item->price, 2) }}</td>
                        <td class="number-cell">{{ number_format($itemSubtotal, 2) }}</td>
                        <td class="number-cell" style="text-align: left; padding-right: 20px;">
                            @if ($item->item_discount_type === 'percent' && $item->item_discount_percent)
                                ({{ number_format($item->item_discount_percent, 0) }}%)
                            @endif
                            {{ number_format($itemDiscount, 2) }}
                        </td>

                        <td class="number-cell" style="background-color: #f9f9f9;">
                            {{ number_format($itemTotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>