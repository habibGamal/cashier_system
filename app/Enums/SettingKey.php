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
    case RECEIPT_FOOTER_BARCODE = 'receipt_footer_barcode';
    case NODE_TYPE = 'node_type';
    case MASTER_NODE_LINK = 'master_node_link';
    case SCALE_BARCODE_PREFIX = 'scale_barcode_prefix';

    // Cashier Permissions
    case ALLOW_CASHIER_DISCOUNTS = 'allow_cashier_discounts';
    case ALLOW_CASHIER_CANCEL_ORDERS = 'allow_cashier_cancel_orders';
    case ALLOW_CASHIER_ITEM_CHANGES = 'allow_cashier_item_changes';

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
            self::RECEIPT_FOOTER_BARCODE => '',
            self::NODE_TYPE => 'independent',
            self::MASTER_NODE_LINK => 'http://127.0.0.1:38794',
            self::SCALE_BARCODE_PREFIX => '23',
            self::ALLOW_CASHIER_DISCOUNTS => '0',
            self::ALLOW_CASHIER_CANCEL_ORDERS => '0',
            self::ALLOW_CASHIER_ITEM_CHANGES => '0',
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
            self::RECEIPT_FOOTER_BARCODE => ['nullable', 'string', 'max:255'],
            self::NODE_TYPE => ['required', 'in:master,slave,independent'],
            self::MASTER_NODE_LINK => ['nullable', 'url', 'max:255'],
            self::SCALE_BARCODE_PREFIX => ['required', 'string', 'regex:/^\d{1,4}$/', 'max:4'],
            self::ALLOW_CASHIER_DISCOUNTS => ['required', 'boolean'],
            self::ALLOW_CASHIER_CANCEL_ORDERS => ['required', 'boolean'],
            self::ALLOW_CASHIER_ITEM_CHANGES => ['required', 'boolean'],
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
            self::RESTAURANT_NAME => 'اسم الشركة',
            self::RESTAURANT_PRINT_LOGO => 'شعار الشركة للطباعة',
            self::RESTAURANT_OFFICIAL_LOGO => 'الشعار الرسمي للشركة',
            self::RECEIPT_FOOTER_BARCODE => 'باركود تذييل الفاتورة',
            self::NODE_TYPE => 'نوع النقطة الحالية',
            self::MASTER_NODE_LINK => 'رابط النقطة الرئيسية',
            self::SCALE_BARCODE_PREFIX => 'بادئة باركود الميزان',
            self::ALLOW_CASHIER_DISCOUNTS => 'السماح للكاشير بتطبيق الخصومات',
            self::ALLOW_CASHIER_CANCEL_ORDERS => 'السماح للكاشير بإلغاء الطلبات',
            self::ALLOW_CASHIER_ITEM_CHANGES => 'السماح للكاشير بتعديل الأصناف',
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
            self::RESTAURANT_NAME => 'اسم الشركة الذي سيظهر في الفواتير والتقارير',
            self::RESTAURANT_PRINT_LOGO => 'شعار الشركة للطباعة (يفضل ملف خفيف وبالأبيض والأسود PNG للطابعات)',
            self::RESTAURANT_OFFICIAL_LOGO => 'الشعار الرسمي للشركة (سيتم حفظه في public/images/logo.jpg)',
            self::RECEIPT_FOOTER_BARCODE => 'باركود يظهر في نهاية الفاتورة المطبوعة (PNG)',
            self::NODE_TYPE => 'تحديد نوع النقطة الحالية في شبكة الفروع',
            self::MASTER_NODE_LINK => 'رابط النقطة الرئيسية (مطلوب فقط إذا كان النوع عبارة عن فرع)',
            self::SCALE_BARCODE_PREFIX => 'البادئة المستخدمة لتحديد باركود المنتجات الموزونة (مثال: 23)',
            self::ALLOW_CASHIER_DISCOUNTS => 'تفعيل هذا الخيار يسمح للكاشير بتطبيق الخصومات على الطلبات والأصناف',
            self::ALLOW_CASHIER_CANCEL_ORDERS => 'تفعيل هذا الخيار يسمح للكاشير بإلغاء أو مسح الطلبات',
            self::ALLOW_CASHIER_ITEM_CHANGES => 'تفعيل هذا الخيار يسمح للكاشير بحذف الأصناف أو تعديل الكميات',
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
            self::RESTAURANT_NAME => 'أدخل اسم الشركة...',
            self::RESTAURANT_OFFICIAL_LOGO => '/images/logo.jpg',
            self::NODE_TYPE => 'اختر نوع النقطة',
            self::MASTER_NODE_LINK => 'http://127.0.0.1:38794',
            self::SCALE_BARCODE_PREFIX => '23',
            default => '',
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
            self::RECEIPT_FOOTER_BARCODE => true, // Always valid for file path
            self::NODE_TYPE => in_array($value, ['master', 'slave', 'independent']),
            self::MASTER_NODE_LINK => !$value || filter_var($value, FILTER_VALIDATE_URL) !== false,
            self::SCALE_BARCODE_PREFIX => is_string($value) && preg_match('/^\d{1,4}$/', $value),
            self::ALLOW_CASHIER_DISCOUNTS,
            self::ALLOW_CASHIER_CANCEL_ORDERS,
            self::ALLOW_CASHIER_ITEM_CHANGES => in_array($value, ['0', '1', 0, 1, true, false], true),
        };
    }
}
