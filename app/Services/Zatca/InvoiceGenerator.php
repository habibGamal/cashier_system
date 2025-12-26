<?php

namespace App\Services\Zatca;

use App\Models\Order;
use Illuminate\Support\Str;
use KhaledHajSalem\Zatca\Data\BuyeRData;
use KhaledHajSalem\Zatca\Data\InvoiceData;
use KhaledHajSalem\Zatca\Data\InvoiceLineData;
use KhaledHajSalem\Zatca\Data\SellerData;

class InvoiceGenerator
{
    private SellerData $seller;

    private BuyerData $buyer;

    public function __construct(private array $config = [])
    {
        $this->initParties();
    }

    private function initParties(): void
    {
        // Seller Data (Your Company)
        $this->seller = new SellerData;
        $this->seller->setRegistrationName($this->config['registered_name'] ?? 'ZATCA Sandbox Company')
            ->setVatNumber($this->config['vat_number'] ?? '310928273100003')
            ->setPartyIdentification($this->config['crn'] ?? '1010101010')
            ->setPartyIdentificationId('CRN')
            ->setAddress($this->config['address'] ?? '123 Main Street, Riyadh, Saudi Arabia')
            ->setCountryCode($this->config['country_code'] ?? 'SA')
            ->setCityName($this->config['city'] ?? 'Riyadh')
            ->setPostalZone($this->config['postal_code'] ?? '12345')
            ->setStreetName($this->config['street'] ?? 'Main Street')
            ->setBuildingNumber($this->config['building_number'] ?? '1234')
            ->setPlotIdentification($this->config['plot_identification'] ?? '1234')
            ->setCitySubdivisionName($this->config['city_subdivision'] ?? 'District 1');

        // Buyer Data (Customer)
        $this->buyer = new BuyerData;
        $this->buyer->setRegistrationName('Customer Company')
            ->setVatNumber('300000000000003')
            ->setPartyIdentification('1010101010')
            ->setPartyIdentificationId('CRN')
            ->setAddress('456 Customer Street, Jeddah, Saudi Arabia')
            ->setCountryCode('SA');
    }

    /**
     * Standard Tax Invoice (B2B) - Type 388
     */
    public function standardInvoice(string $uuid): InvoiceData
    {
        $invoice = new InvoiceData;
        $this->setupCommonData($invoice, $uuid, '0100000', 388); // 0100000 = Standard
        $invoice->standard();

        $this->addSampleLine($invoice, 'Standard Product', 100.00);
        $invoice->calculateTotals();

        return $invoice;
    }

    /**
     * Simplified Tax Invoice (B2C) - Type 388
     */
    public function simplifiedInvoice(string $uuid): InvoiceData
    {
        $invoice = new InvoiceData;
        $this->setupCommonData($invoice, $uuid, '0200000', 388); // 0200000 = Simplified
        $invoice->simplified();

        $this->addSampleLine($invoice, 'Consumer Product', 50.00);
        $invoice->calculateTotals();

        return $invoice;
    }

    /**
     * Standard Credit Note - Type 381
     */
    public function standardCreditNote(string $uuid): InvoiceData
    {
        $invoice = new InvoiceData;
        $this->setupCommonData($invoice, $uuid, '0100000', 381);
        $invoice->standard()->creditNote();

        $this->addBillingReference($invoice);
        $this->addSampleLine($invoice, 'Returned Item', 100.00);
        $invoice->calculateTotals();

        return $invoice;
    }

    /**
     * Standard Debit Note - Type 383
     */
    public function standardDebitNote(string $uuid): InvoiceData
    {
        $invoice = new InvoiceData;
        $this->setupCommonData($invoice, $uuid, '0100000', 383);
        $invoice->standard()->debitNote();

        $this->addBillingReference($invoice);
        $this->addSampleLine($invoice, 'Extra Charge', 20.00);
        $invoice->calculateTotals();

        return $invoice;
    }

    /**
     * Simplified Credit Note - Type 381
     */
    public function simplifiedCreditNote(string $uuid): InvoiceData
    {
        $invoice = new InvoiceData;
        $this->setupCommonData($invoice, $uuid, '0200000', 381);
        $invoice->simplified()->creditNote();

        $this->addBillingReference($invoice);
        $this->addSampleLine($invoice, 'Returned Consumer Item', 50.00);
        $invoice->calculateTotals();

        return $invoice;
    }

    /**
     * Simplified Debit Note - Type 383
     */
    public function simplifiedDebitNote(string $uuid): InvoiceData
    {
        $invoice = new InvoiceData;
        $this->setupCommonData($invoice, $uuid, '0200000', 383);
        $invoice->simplified()->debitNote();

        $this->addBillingReference($invoice);
        $this->addSampleLine($invoice, 'Undercharged Item', 10.00);
        $invoice->calculateTotals();

        return $invoice;
    }

    private function setupCommonData(InvoiceData $invoice, string $uuid, string $invoiceTypeParams, int $invoiceTypeCode): void
    {
        $invoice->setInvoiceNumber('INV-'.rand(1000, 9999))
            ->setIssueDate(now()->format('Y-m-d'))
            ->setIssueTime(now()->format('H:i:s'))
            ->setDueDate(now()->addDays(7)->format('Y-m-d'))
            ->setCurrencyCode('SAR')
            ->setDocumentCurrencyCode('SAR')
            ->setTaxCurrencyCode('SAR')
            ->setInvoiceCounter(rand(10, 99)) // Only digits
            ->setPreviousInvoiceHash('MA==')
            ->setSeller($this->seller)
            ->setBuyer($this->buyer);

        // Note: The library methods standard() and simplified() set the invoice type name.
        // taxInvoice(), creditNote(), debitNote() set the type code.
    }

    private function addBillingReference(InvoiceData $invoice): void
    {
        // Random UUID for origin invoice
        $originUuid = (string) Str::uuid();

        $invoice->addBillingReference([
            'id' => 'INV-'.rand(1000, 9999),
            'uuid' => $originUuid,
            'issue_date' => now()->subDays(1)->format('Y-m-d'),
            // 'issue_time' => '10:00:00' // Optional
        ]);
    }

    private function addSampleLine(InvoiceData $invoice, string $name, float $price): void
    {
        $line = new InvoiceLineData;
        $line->setId(1)
            ->setItemName($name)
            ->setQuantity(1.0)
            ->setUnitPrice($price)
            ->setTaxPercent(15.0)
            ->calculateTotals();

        $invoice->addLine($line);
    }

    public function fromOrder(Order $order, string $uuid): InvoiceData
    {
        $invoice = new InvoiceData;

        $issueDate = $order->created_at ?? now();
        $dueDate = $issueDate->copy()->addDays(7);

        // Simplified Invoice (B2C) for most cashier orders
        // Standard (B2B) only if customer has VAT number (we don't have this field currently)
        $subtype = '0200000'; // Simplified

        $invoice->setInvoiceNumber('INV-'.$order->order_number)
            ->setIssueDate($issueDate->format('Y-m-d'))
            ->setIssueTime($issueDate->format('H:i:s'))
            ->setInvoiceTypeCode('388') // Tax Invoice
            ->setInvoiceTypeName($subtype)
            ->setCurrencyCode('SAR')
            ->setDocumentCurrencyCode('SAR')
            ->setTaxCurrencyCode('SAR')
            ->setInvoiceCounter($order->id)
            ->setPreviousInvoiceHash('MA==') // Should fetch from previous order if implementing chaining
            ->setSeller($this->seller);

        $invoice->simplified();

        // Map Buyer Data from Customer
        $buyer = new BuyerData;
        if ($order->customer) {
            $buyer->setRegistrationName($order->customer->name);
            // Simplified invoices don't require full buyer details
        } else {
            $buyer->setRegistrationName('Cash Customer');
        }
        $invoice->setBuyer($buyer);

        // Map Order Items
        foreach ($order->items as $index => $item) {
            $line = new InvoiceLineData;
            $line->setId($index + 1)
                ->setItemName($item->product?->name ?? 'Item '.($index + 1))
                ->setQuantity((float) $item->quantity)
                ->setUnitPrice((float) $item->price) // Price is exclusive of tax in our system
                ->setTaxPercent(15.0) // Standard VAT rate in Saudi Arabia
                ->calculateTotals();

            $invoice->addLine($line);
        }

        $invoice->calculateTotals();

        return $invoice;
    }
}
