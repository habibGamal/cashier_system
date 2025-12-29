@php
    use App\Enums\SettingKey;
    use Illuminate\Support\Facades\Storage;

    $receiptFooter = setting(SettingKey::RECEIPT_FOOTER);
    $qrLogoPath =
        setting(SettingKey::RECEIPT_FOOTER_BARCODE) !== ''
        ? public_path(Storage::url(setting(SettingKey::RECEIPT_FOOTER_BARCODE)))
        : null;

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
    <title>Receipt Footer</title>
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
        <table>
            <tbody>
                <tr>
                    <td>اجمالي الطلب</td>
                    <td class="number-cell">{{ number_format($order->sub_total, 2) }}</td>
                </tr>
                <tr>
                    <td>
                        الخصم
                        @if ($order->discount > 0 && $order->sub_total > 0)
                            @php
                                $discountPercent = ($order->discount / $order->sub_total) * 100;
                            @endphp
                            ({{ number_format($discountPercent, 2) }}%)
                        @endif
                    </td>
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
