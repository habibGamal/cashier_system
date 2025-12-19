<?php

namespace App\Services\Zatca;

use App\Models\Order;
use KhaledHajSalem\Zatca\Services\ZatcaAPIService;
use KhaledHajSalem\Zatca\Support\Certificate;
use KhaledHajSalem\Zatca\Support\InvoiceSigner;
use KhaledHajSalem\Zatca\ZatcaInvoice;

class ZatcaReportingService
{
    private ZatcaAPIService $apiService;

    private InvoiceGenerator $generator;

    private ZatcaOnboardingService $onboardingService;

    public function __construct(InvoiceGenerator $generator, ZatcaOnboardingService $onboardingService)
    {
        $this->generator = $generator;
        $this->onboardingService = $onboardingService;
        $environment = \App\Models\Zatca::getValue(\App\Enums\ZatcaEnum::ZATCA_ENVIRONMENT->value, 'sandbox');
        $this->apiService = new ZatcaAPIService($environment);
    }

    public function reportOrder(Order $order): array
    {
        // 1. Check if already reported
        if ($order->zatca_status === 'REPORTED') {
            return [
                'status' => 'error',
                'message' => 'Invoice already reported to Zatca.',
            ];
        }

        try {
            // 2. Load Production Certificate & Secret
            // Using onboarding service to get the correct path
            $basePath = $this->onboardingService->getFullStoragePath();

            $productionCertPath = $basePath.'/production_certificate.pem';
            $productionSecretPath = $basePath.'/production_secret.txt';
            $keyPath = $basePath.'/private.pem';

            if (! file_exists($productionCertPath) || ! file_exists($productionSecretPath) || ! file_exists($keyPath)) {
                return [
                    'status' => 'error',
                    'message' => 'Zatca Production Certificate or Private Key not found. Please onboard first.',
                ];
            }

            $productionCertificate = file_get_contents($productionCertPath);
            // Clean certificate content as seen in TestZatca
            $productionCertificateClean = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' ', "\n"], '', $productionCertificate);
            $productionSecretValue = trim(file_get_contents($productionSecretPath));
            $privateKey = file_get_contents($keyPath);

            // 3. Generate Invoice XML
            $uuid = $order->zatca_uuid ?? $this->generateUUID();

            $invoiceData = $this->generator->fromOrder($order, $uuid);
            $zatcaInvoice = new ZatcaInvoice;
            $xml = $zatcaInvoice->generateXml($invoiceData, $uuid);

            // 4. Sign Invoice
            $productionCert = new Certificate($productionCertificateClean, $privateKey, $productionSecretValue);
            $signer = InvoiceSigner::signInvoice($xml, $productionCert);
            $signedXml = $signer->getXML();
            $hash = $signer->getHash();
            $qrCode = $signer->getQRCode();

            // 5. Report/Clear Invoice
            // Simplified -> Report (reportInvoice)
            // Standard -> Clear (clearInvoice)
            // Determine based on customer data
            $isStandard = ! empty($order->customer?->vat_number);

            $responseData = null;

            if ($isStandard) {
                $responseData = $this->apiService->clearInvoice(
                    $productionCertificateClean,
                    $productionSecretValue,
                    $signedXml,
                    $hash,
                    $uuid
                );
            } else {
                $responseData = $this->apiService->reportInvoice(
                    $productionCertificateClean,
                    $productionSecretValue,
                    $signedXml,
                    $hash,
                    $uuid
                );
            }

            // 6. Handle Response
            // Check status in response. For reportInvoice (Simplified), it's async? No, usually sync validation.
            // Zatca API response structure verification needed.
            // Simplified Report returns: { validationResults: { status: 'PASSED' | 'WARNING' | 'FAILED' }, ... }
            // Clearance returns: { clearanceStatus: 'CLEARED' | 'NOT_CLEARED', ... }

            $status = 'FAILED';
            $isSuccess = false;

            if ($isStandard) {
                $status = $responseData['clearanceStatus'] ?? 'FAILED';
                if ($status === 'CLEARED') {
                    $isSuccess = true;
                }
            } else {
                $validationStatus = $responseData['validationResults']['status'] ?? 'FAILED';
                if ($validationStatus === 'PASSED' || $validationStatus === 'WARNING') {
                    $status = 'REPORTED';
                    $isSuccess = true;
                }
            }

            // Update Order
            $order->update([
                'zatca_status' => $status,
                'zatca_last_response' => json_encode($responseData),
                'zatca_uuid' => $uuid,
                'zatca_hash' => $hash,
                'zatca_qr_base64' => $qrCode,
                'zatca_xml_path' => $this->saveXml($uuid, $signedXml),
                'zatca_submitted_at' => now(),
            ]);

            if ($isSuccess) {
                return [
                    'status' => 'success',
                    'message' => 'Invoice reported successfully to Zatca.',
                    'data' => $responseData,
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Zatca reporting failed.',
                    'data' => $responseData,
                ];
            }

        } catch (\Exception $e) {
            $order->update([
                'zatca_status' => 'FAILED',
                'zatca_last_response' => json_encode(['error' => $e->getMessage()]),
            ]);

            return [
                'status' => 'error',
                'message' => 'Exception during Zatca reporting: '.$e->getMessage(),
            ];
        }
    }

    private function generateUUID(): string
    {
        // Custom UUID logic from TestZatca
        if (function_exists('random_bytes')) {
            $data = random_bytes(16);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes(16);
        } else {
            $data = uniqid('', true);
        }

        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function saveXml(string $uuid, string $xmlContent): string
    {
        $path = 'zatca/invoices/'.date('Y/m/d').'/'.$uuid.'.xml';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $xmlContent);

        return $path;
    }
}
