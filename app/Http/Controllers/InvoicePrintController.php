<?php

namespace App\Http\Controllers;

use Exception;
use App\Services\InvoicePrintService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoicePrintController extends Controller
{
    public function __construct(
        private InvoicePrintService $printService
    ) {}

    /**
     * Show print page for invoice
     */
    public function show(string $type, int $id)
    {
        // Validate type parameter
        if (!in_array($type, ['purchase_invoice', 'return_purchase_invoice', 'stocktaking', 'waste'])) {
            abort(404, 'نوع الفاتورة غير صحيح');
        }

        try {
            $data = $this->printService->getPrintData($type, $id);

            return Inertia::render('Print/InvoicePrint', [
                'invoiceData' => $data,
            ]);
        } catch (Exception $e) {
            abort(404, 'فاتورة غير موجودة');
        }
    }
}
