@php
    use App\Enums\SettingKey;
    use Illuminate\Support\Facades\Storage;
    use App\Enums\OrderType;

    // Order type mapping
    $getOrderTypeString = function ($type) {
        $typeMap = [
            'dine_in' => 'صالة',
            'takeaway' => 'تيك أواي',
            'delivery' => 'دليفري',
            'companies' => 'شركات',
            'talabat' => 'طلبات',
            'web_delivery' => 'اونلاين دليفري',
            'web_takeaway' => 'اونلاين تيك أواي',
            'direct_sale' => 'بيع مباشر'
        ];
        return $typeMap[$type->value] ?? $type->value;
    };

    $logoPath =
        setting(SettingKey::RESTAURANT_PRINT_LOGO) !== ''
        ? public_path(Storage::url(setting(SettingKey::RESTAURANT_PRINT_LOGO)))
        : null;

    // Format dates
    $orderDate = $order->created_at->setTimezone('Africa/Cairo')->format('d/m/Y H:i:s');
    $printDate = now()->setTimezone('Africa/Cairo')->format('d/m/Y H:i:s');

    // Convert image to data URI
    $imgToDataUri = function ($imagePath) {
        if (!file_exists($imagePath)) {
            return '';
        }
        $imageData = file_get_contents($imagePath);
        $base64 = base64_encode($imageData);
        $mimeType = mime_content_type($imagePath);
        return "data:{$mimeType};base64,{$base64}";
    };

    // Format number: hide .00 decimals
    $formatNumber = function($number) {
        return fmod($number, 1) == 0 ? number_format($number, 0) : number_format($number, 2);
    };
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Header</title>
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
            /* font-weight: bold; */
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
        }

        .receipt>* {}

        .logo {
            display: block;
            margin: 0 auto;
            width: 200px;
            max-width: 50mm;
        }

        .order-number {
            font-size: 48px;
            text-align: center;
            font-weight: bold;
        }

        .center {
            text-align: center;
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
            font-size: 18px;
        }

        td {
            font-size: 16px;
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
        @if ($logoPath)
            <img class="logo" src="{{ $imgToDataUri($logoPath) }}" alt="Logo" />
        @elseif($name = setting(SettingKey::RESTAURANT_NAME))
            <h1 style="text-align: center;">{{ $name }}</h1>
        @else
            <h1>-- TURBO --</h1>
        @endif

        <p class="order-number">Order #{{ $order->order_number }}</p>

        <p>الكاشير : {{ $order->user?->email }}</p>
        <p>تاريخ الطلب : {{ $orderDate }}</p>
        <p>تاريخ الطباعة : {{ $printDate }}</p>
        <p>نوع الطلب : {{ $getOrderTypeString($order->type) }} </p>

        <p>اسم العميل : {{ $order->customer?->name ?? '-' }}</p>
        <p>رقم الهاتف : {{ $order->customer?->phone ?? '-' }}</p>

        {{-- Delivery: show phone, name, address, driver --}}
        @if (in_array($order->type->value, ['delivery', 'web_delivery']))
            <p>العنوان : {{ $order->customer?->address ?? '-' }}</p>
            <p>السائق : {{ $order->driver?->name ?? '-' }}</p>
        @endif

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
                        <td class="number-cell">{{ $formatNumber($item->price) }}</td>
                        <td class="number-cell">{{ $formatNumber($itemSubtotal) }}</td>
                        <td class="number-cell" style="text-align: left; padding-right: 20px;">
                            @if ($item->item_discount_type === 'percent' && $item->item_discount_percent)
                                ({{ number_format($item->item_discount_percent, 0) }}%)
                            @endif
                            {{ $formatNumber($itemDiscount) }}
                        </td>

                        <td class="number-cell" style="background-color: #f9f9f9;">
                            {{ $formatNumber($itemTotal) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
