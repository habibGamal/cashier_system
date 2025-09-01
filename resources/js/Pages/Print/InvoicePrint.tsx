import React from 'react';
import { Head } from '@inertiajs/react';

interface InvoiceItem {
    product_name: string;
    quantity?: number;
    price?: number;
    total: number;
    stock_quantity?: number;
    real_quantity?: number;
    difference?: number;
}

interface AdditionalInfo {
    label: string;
    value: string;
}

interface InvoiceData {
    type: string;
    title: string;
    id: number;
    supplier?: string;
    user: string;
    total: number;
    notes?: string;
    created_at: string;
    items: InvoiceItem[];
    additional_info: AdditionalInfo[];
}

interface Props {
    invoiceData: InvoiceData;
}

export default function InvoicePrint({ invoiceData }: Props) {
    const handlePrint = () => {
        window.print();
    };

    const renderTableHeaders = () => {
        switch (invoiceData.type) {
            case 'stocktaking':
                return (
                    <tr className="bg-gray-100">
                        <th className="border border-gray-400 px-4 py-2 text-right">المنتج</th>
                        {/* <th className="border border-gray-400 px-4 py-2 text-center">الكمية المخزنة</th> */}
                        <th className="border border-gray-400 px-4 py-2 text-center">الكمية الفعلية</th>
                        <th className="border border-gray-400 px-4 py-2 text-center">الفرق</th>
                        <th className="border border-gray-400 px-4 py-2 text-center">السعر</th>
                        <th className="border border-gray-400 px-4 py-2 text-center">الإجمالي</th>
                    </tr>
                );
            default:
                return (
                    <tr className="bg-gray-100">
                        <th className="border border-gray-400 px-4 py-2 text-right">المنتج</th>
                        <th className="border border-gray-400 px-4 py-2 text-center">الكمية</th>
                        <th className="border border-gray-400 px-4 py-2 text-center">السعر</th>
                        <th className="border border-gray-400 px-4 py-2 text-center">الإجمالي</th>
                    </tr>
                );
        }
    };

    const renderTableRow = (item: InvoiceItem, index: number) => {
        switch (invoiceData.type) {
            case 'stocktaking':
                return (
                    <tr key={index}>
                        <td className="border border-gray-400 px-4 py-2 text-right">{item.product_name}</td>
                        {/* <td className="border border-gray-400 px-4 py-2 text-center">{item.stock_quantity}</td> */}
                        <td className="border border-gray-400 px-4 py-2 text-center">{item.real_quantity}</td>
                        <td className={`border border-gray-400 px-4 py-2 text-center ${
                            (item.difference || 0) < 0 ? 'text-red-600' :
                            (item.difference || 0) > 0 ? 'text-green-600' : 'text-gray-600'
                        }`}>
                            {item.difference}
                        </td>
                        <td className="border border-gray-400 px-4 py-2 text-center">{item.price ? Number(item.price).toFixed(2) : '0.00'} ج.م</td>
                        <td className="border border-gray-400 px-4 py-2 text-center">{Number(item.total).toFixed(2)} ج.م</td>
                    </tr>
                );
            default:
                return (
                    <tr key={index}>
                        <td className="border border-gray-400 px-4 py-2 text-right">{item.product_name}</td>
                        <td className="border border-gray-400 px-4 py-2 text-center">{item.quantity}</td>
                        <td className="border border-gray-400 px-4 py-2 text-center">{item.price ? Number(item.price).toFixed(2) : '0.00'} ج.م</td>
                        <td className="border border-gray-400 px-4 py-2 text-center">{Number(item.total).toFixed(2)} ج.م</td>
                    </tr>
                );
        }
    };

    return (
        <>
            <Head title={`طباعة ${invoiceData.title}`} />

            <div className="min-h-screen bg-white">
                {/* Print Button - Hidden when printing */}
                <div className="print:hidden fixed top-4 right-4 z-10">
                    <button
                        onClick={handlePrint}
                        className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 transition-colors"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        طباعة
                    </button>
                </div>

                {/* Invoice Content */}
                <div className="max-w-4xl mx-auto p-8" dir="rtl">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <h1 className="text-3xl font-bold text-gray-800 mb-2">{invoiceData.title}</h1>
                        <p className="text-xl text-gray-600">رقم: {invoiceData.id}</p>
                    </div>

                    {/* Invoice Info */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div className="bg-gray-50 p-4 rounded-lg">
                            <h3 className="text-lg font-semibold text-gray-800 mb-3">معلومات عامة</h3>
                            <div className="space-y-2">
                                <p><span className="font-medium">المستخدم:</span> {invoiceData.user}</p>
                                <p><span className="font-medium">التاريخ:</span> {invoiceData.created_at}</p>
                                {invoiceData.supplier && (
                                    <p><span className="font-medium">المورد:</span> {invoiceData.supplier}</p>
                                )}
                                <p><span className="font-medium">الإجمالي:</span> {Number(invoiceData.total).toFixed(2)} ج.م</p>
                            </div>
                        </div>

                        {invoiceData.additional_info.length > 0 && (
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <h3 className="text-lg font-semibold text-gray-800 mb-3">معلومات إضافية</h3>
                                <div className="space-y-2">
                                    {invoiceData.additional_info.map((info, index) => (
                                        <p key={index}>
                                            <span className="font-medium">{info.label}:</span> {info.value || '-'}
                                        </p>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Notes */}
                    {invoiceData.notes && (
                        <div className="mb-8">
                            <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                                <h3 className="text-lg font-semibold text-gray-800 mb-2">ملاحظات</h3>
                                <p className="text-gray-700">{invoiceData.notes}</p>
                            </div>
                        </div>
                    )}

                    {/* Items Table */}
                    <div className="mb-8">
                        <h3 className="text-xl font-semibold text-gray-800 mb-4">تفاصيل الأصناف</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full border-collapse border-2 border-gray-400">
                                <thead>
                                    {renderTableHeaders()}
                                </thead>
                                <tbody>
                                    {invoiceData.items.map((item, index) => renderTableRow(item, index))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Summary */}
                    <div className="border-t-2 border-gray-300 pt-6">
                        <div className="flex justify-between items-center">
                            <div>
                                <p className="text-sm text-gray-600">تم الطباعة في: {new Date().toLocaleString('ar-EG')}</p>
                            </div>
                            <div className="text-right">
                                <p className="text-2xl font-bold text-gray-800">
                                    الإجمالي النهائي: {Number(invoiceData.total).toFixed(2)} ج.م
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Print Styles */}
            <style>{`
                @media print {
                    body {
                        -webkit-print-color-adjust: exact;
                        color-adjust: exact;
                    }

                    .print\\:hidden {
                        display: none !important;
                    }

                    @page {
                        margin: 1cm;
                        size: A4;
                    }
                }
            `}</style>
        </>
    );
}
