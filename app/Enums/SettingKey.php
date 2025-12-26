<?php

namespace App\Enums;

enum SettingKey: string
{
    case WEBSITE_URL = 'website_url';
    case CASHIER_PRINTER_IP = 'cashier_printer_ip';
    case RECEIPT_FOOTER = 'receipt_footer';
    case RESTAURANT_NAME = 'restaurant_name';
    case RESTAURANT_PRINT_LOGO = 'restaurant_print_logo';
    case RESTAURANT_OFFICIAL_LOGO = 'restaurant_official_logo';
    case NODE_TYPE = 'node_type';
    case MASTER_NODE_LINK = 'master_node_link';
    case SCALE_BARCODE_PREFIX = 'scale_barcode_prefix';
    case CURRENCY_SYMBOL = 'currency_symbol';
    case CURRENCY_CODE = 'currency_code';
    case CURRENCY_NAME = 'currency_name';
    case CURRENCY_DECIMALS = 'currency_decimals';

    /**
     * Get default value for this setting
     */
    public function defaultValue(): string
    {
        return match ($this) {
            self::WEBSITE_URL => 'http://127.0.0.1:38794',
            self::CASHIER_PRINTER_IP => '192.168.1.100',
            self::RECEIPT_FOOTER => 'شكراً لزيارتكم، نتطلع لخدمتكم مرة أخرى',
            self::RESTAURANT_NAME => '-------',
            self::RESTAURANT_PRINT_LOGO => '',
            self::RESTAURANT_OFFICIAL_LOGO => '/images/logo.jpg',
            self::NODE_TYPE => 'independent',
            self::MASTER_NODE_LINK => 'http://127.0.0.1:38794',
            self::SCALE_BARCODE_PREFIX => '23',
            self::CURRENCY_SYMBOL => 'ج.م',
            self::CURRENCY_CODE => 'EGP',
            self::CURRENCY_NAME => 'جنيه',
            self::CURRENCY_DECIMALS => '2',
        };
    }

    /**
     * Get validation rules for this setting
     */
    public function validationRules(): array
    {
        return match ($this) {
            self::WEBSITE_URL => ['required', 'url', 'max:255'],
            self::CASHIER_PRINTER_IP => ['required', 'ip', 'max:15'],
            self::RECEIPT_FOOTER => ['nullable', 'string', 'max:500'],
            self::RESTAURANT_NAME => ['required', 'string', 'max:255'],
            self::RESTAURANT_PRINT_LOGO => ['nullable', 'string', 'max:255'],
            self::RESTAURANT_OFFICIAL_LOGO => ['nullable', 'string', 'max:255'],
            self::NODE_TYPE => ['required', 'in:master,slave,independent'],
            self::MASTER_NODE_LINK => ['nullable', 'url', 'max:255'],
            self::SCALE_BARCODE_PREFIX => ['required', 'string', 'regex:/^\d{1,4}$/', 'max:4'],
            self::CURRENCY_SYMBOL => ['required', 'string', 'max:10'],
            self::CURRENCY_CODE => ['required', 'string', 'max:3'],
            self::CURRENCY_NAME => ['required', 'string', 'max:50'],
            self::CURRENCY_DECIMALS => ['required', 'integer', 'min:0', 'max:4'],
        };
    }

    /**
     * Get Arabic label for this setting
     */
    public function label(): string
    {
        return match ($this) {
            self::WEBSITE_URL => 'رابط الموقع',
            self::CASHIER_PRINTER_IP => 'عنوان IP لطابعة الكاشير',
            self::RECEIPT_FOOTER => 'تذييل الفاتورة',
            self::RESTAURANT_NAME => 'اسم المطعم',
            self::RESTAURANT_PRINT_LOGO => 'شعار المطعم للطباعة',
            self::RESTAURANT_OFFICIAL_LOGO => 'الشعار الرسمي للمطعم',
            self::NODE_TYPE => 'نوع النقطة الحالية',
            self::MASTER_NODE_LINK => 'رابط النقطة الرئيسية',
            self::SCALE_BARCODE_PREFIX => 'بادئة باركود الميزان',
            self::CURRENCY_SYMBOL => 'رمز العملة',
            self::CURRENCY_CODE => 'كود العملة',
            self::CURRENCY_NAME => 'اسم العملة',
            self::CURRENCY_DECIMALS => 'عدد الخانات العشرية',
        };
    }

    /**
     * Get helper text for this setting
     */
    public function helperText(): string
    {
        return match ($this) {
            self::WEBSITE_URL => 'الرابط الأساسي للموقع الإلكتروني',
            self::CASHIER_PRINTER_IP => 'عنوان IP الخاص بطابعة الكاشير لطباعة الفواتير',
            self::RECEIPT_FOOTER => 'النص الذي يظهر في نهاية كل فاتورة مطبوعة',
            self::RESTAURANT_NAME => 'اسم المطعم الذي سيظهر في الفواتير والتقارير',
            self::RESTAURANT_PRINT_LOGO => 'شعار المطعم للطباعة (يفضل ملف خفيف وبالأبيض والأسود PNG للطابعات)',
            self::RESTAURANT_OFFICIAL_LOGO => 'الشعار الرسمي للمطعم (سيتم حفظه في public/images/logo.jpg)',
            self::NODE_TYPE => 'تحديد نوع النقطة الحالية في شبكة الفروع',
            self::MASTER_NODE_LINK => 'رابط النقطة الرئيسية (مطلوب فقط إذا كان النوع عبارة عن فرع)',
            self::SCALE_BARCODE_PREFIX => 'البادئة المستخدمة لتحديد باركود المنتجات الموزونة (مثال: 23)',
            self::CURRENCY_SYMBOL => 'الرمز الذي يظهر بجانب المبالغ (مثال: ج.م، $، €)',
            self::CURRENCY_CODE => 'الكود الدولي للعملة المكون من 3 أحرف (مثال: EGP، USD، EUR)',
            self::CURRENCY_NAME => 'اسم العملة بالعربية (مثال: جنيه، دولار، يورو)',
            self::CURRENCY_DECIMALS => 'عدد الأرقام بعد الفاصلة العشرية (عادة 2)',
        };
    }

    /**
     * Get placeholder text for this setting
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::WEBSITE_URL => 'http://127.0.0.1:38794',
            self::CASHIER_PRINTER_IP => '192.168.1.100',
            self::RECEIPT_FOOTER => 'أدخل النص الذي تريد أن يظهر في نهاية الفاتورة...',
            self::RESTAURANT_NAME => 'أدخل اسم المطعم...',
            self::RESTAURANT_OFFICIAL_LOGO => '/images/logo.jpg',
            self::NODE_TYPE => 'اختر نوع النقطة',
            self::MASTER_NODE_LINK => 'http://127.0.0.1:38794',
            self::SCALE_BARCODE_PREFIX => '23',
            self::CURRENCY_SYMBOL => 'ج.م',
            self::CURRENCY_CODE => 'EGP',
            self::CURRENCY_NAME => 'جنيه',
            self::CURRENCY_DECIMALS => '2',
        };
    }

    /**
     * Validate the value for this setting
     */
    public function validate(mixed $value): bool
    {
        return match ($this) {
            self::WEBSITE_URL => filter_var($value, FILTER_VALIDATE_URL) !== false,
            self::CASHIER_PRINTER_IP => filter_var($value, FILTER_VALIDATE_IP) !== false,
            self::RECEIPT_FOOTER => true, // Always valid for text
            self::RESTAURANT_NAME => is_string($value) && strlen($value) > 0,
            self::RESTAURANT_PRINT_LOGO => true, // Always valid for file path
            self::RESTAURANT_OFFICIAL_LOGO => true, // Always valid for file path
            self::NODE_TYPE => in_array($value, ['master', 'slave', 'independent']),
            self::MASTER_NODE_LINK => ! $value || filter_var($value, FILTER_VALIDATE_URL) !== false,
            self::SCALE_BARCODE_PREFIX => is_string($value) && preg_match('/^\d{1,4}$/', $value),
            self::CURRENCY_SYMBOL => is_string($value) && strlen($value) > 0 && strlen($value) <= 10,
            self::CURRENCY_CODE => is_string($value) && strlen($value) === 3,
            self::CURRENCY_NAME => is_string($value) && strlen($value) > 0 && strlen($value) <= 50,
            self::CURRENCY_DECIMALS => is_numeric($value) && (int) $value >= 0 && (int) $value <= 4,
        };
    }
}
