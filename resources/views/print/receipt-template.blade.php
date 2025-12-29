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

    $receiptFooter = setting(SettingKey::RECEIPT_FOOTER);
    $logoPath =
        setting(SettingKey::RESTAURANT_PRINT_LOGO) !== ''
        ? Storage::path(setting(SettingKey::RESTAURANT_PRINT_LOGO))
        : null;
    $qrLogoPath =
        setting(SettingKey::RECEIPT_FOOTER_BARCODE) !== ''
        ? Storage::path(setting(SettingKey::RECEIPT_FOOTER_BARCODE))
        : null;
    logger()->info($qrLogoPath);
    logger()->info($logoPath);
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
    $footerLogo = $imgToDataUri(public_path('images/turbo.png'));
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <style>
        /* @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;700&display=swap'); */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            color: black;
        }

        body {
            font-family: "FreeSans",sans-serif;
            /* font-family: "DejaVu Sans", "DejaVu Serif", "DejaVu Sans Mono", sans-serif; */
            direction: rtl;
            font-size: 22px;
            font-weight: bold;
            line-height: 1.4;
            color: black;
            background: white;
            padding: 20px;
            width: 572px;
            direction: rtl;
        }

        .receipt {
            width: 100%;
            space-y: 16px;
        }

        .receipt>* {
            margin-bottom: 16px;
        }

        .logo {
            display: block;
            margin: 0 auto;
            width: 250px;
            max-width: 60mm;
        }

        .order-number {
            font-size: 36px;
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
            margin: 16px 0;
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
            max-width: 200px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .number-cell {
            max-width: 80px;
            font-size: 18px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .reference {
            text-align: center;
            margin-top: 20px;
        }

        .footer-text {
            white-space: pre-line;
            text-align: center;
        }

        .turbo-logo {
            display: block;
            margin: 20px auto;
            width: 200px;
            max-width: 50mm;
        }

        .footer-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin: 20px auto;
            text-align: center;
        }

        .footer-logos img {
            width: 150px;
            max-width: 40mm;
        }

        .company-info {
            text-align: center;
            font-size: 18px;
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

        {{-- Takeaway: show customer name and phone --}}
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
                @foreach ($order->items as $item)
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

        <table>
            <tbody>
                <tr>
                    <td>اجمالي الطلب</td>
                    <td class="number-cell">{{ number_format($order->sub_total, 2) }}</td>
                </tr>
                <tr>
                    <td>الخصم</td>
                    <td class="number-cell">{{ number_format($order->discount, 2) }}</td>
                </tr>
                <tr>
                    <td>الخدمة</td>
                    <td class="number-cell">{{ number_format($order->service, 2) }}</td>
                </tr>
                <tr>
                    <td>الضريبة</td>
                    <td class="number-cell">{{ number_format($order->tax, 2) }}</td>
                </tr>
                <tr>
                    <td>الاجمالي النهائي</td>
                    <td class="number-cell">{{ ceil($order->total) }}</td>
                </tr>
            </tbody>
        </table>

        <p class="reference">الرقم المرجعي - {{ $order->id }}</p>
        @if ($order->order_notes)
            <p>{{ $order->order_notes }}</p>
        @endif
        <p class="footer-text">{{ $receiptFooter }}</p>

        <div class="footer-logos">
            @if ($qrLogoPath)
                <img src="{{ $imgToDataUri($qrLogoPath) }}" alt="Restaurant QR Logo" />
            @else
                <img src="{{ $footerLogo }}" alt="Turbo Logo" />
            @endif
        </div>

        <p class="company-info">Turbo Software Space</p>
        <p class="center">{{ $printDate }}</p>
    </div>
</body>

</html>
