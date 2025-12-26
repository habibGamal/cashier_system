<?php

namespace App\Services\Zatca;

use App\Enums\ZatcaEnum;
use App\Models\Zatca;
use KhaledHajSalem\Zatca\Support\CertificateBuilder;

class ZatcaOnboardingService
{
    /**
     * Get full storage path for ZATCA files
     */
    public function getFullStoragePath(): string
    {
        return storage_path('app/zatca');
    }

    /**
     * Generate CSR and private key
     */
    public function generateCsr(array $data): array
    {
        $storagePath = $this->getFullStoragePath();

        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Convert invoice type from select value to numeric format
        $invoiceType = $this->mapInvoiceType($data['invoice_type']);

        $builder = new CertificateBuilder;
        $builder->setOrganizationIdentifier($data['organization_identifier'])
            ->setSerialNumber(
                $data['serial_number_solution'],
                $data['serial_number_model'],
                $data['serial_number_device']
            )
            ->setCommonName($data['common_name'])
            ->setCountryName($data['country_name'] ?? 'SA')
            ->setOrganizationName($data['organization_name'])
            ->setOrganizationalUnitName($data['organizational_unit_name'])
            ->setAddress($data['address'])
            ->setInvoiceType($invoiceType)
            ->setProduction($data['is_production'] ?? false)
            ->setBusinessCategory($data['business_category']);

        $csrPath = $storagePath.'/certificate.csr';
        $keyPath = $storagePath.'/private.pem';

        $builder->generateAndSave($csrPath, $keyPath);

        // Store paths and config in database
        $this->saveConfig($data, $csrPath, $keyPath);

        return [
            'csr_path' => $csrPath,
            'private_key_path' => $keyPath,
            'csr_content' => file_get_contents($csrPath),
        ];
    }

    /**
     * Map invoice type from frontend value to ZATCA format
     */
    private function mapInvoiceType(string $type): int
    {
        return match ($type) {
            'standard' => 1000,
            'simplified' => 100,
            'both' => 1100,
            default => (int) $type,
        };
    }

    /**
     * Get certificate status
     */
    public function getCertificateStatus(): array
    {
        $basePath = $this->getFullStoragePath();

        $csrExists = file_exists($basePath.'/certificate.csr');
        $privateKeyExists = file_exists($basePath.'/private.pem');
        $complianceCertExists = file_exists($basePath.'/compliance_certificate.pem');
        $productionCertExists = file_exists($basePath.'/production_certificate.pem');

        return [
            'csr_exists' => $csrExists,
            'private_key_exists' => $privateKeyExists,
            'compliance_certificate_exists' => $complianceCertExists,
            'production_certificate_exists' => $productionCertExists,
            'storage_path' => 'zatca',
            'onboarding_status' => Zatca::getValue(ZatcaEnum::ONBOARDING_STATUS->value, 'pending'),
            'csr_path' => $csrExists ? $basePath.'/certificate.csr' : null,
            'private_key_path' => $privateKeyExists ? $basePath.'/private.pem' : null,
        ];
    }

    /**
     * Delete all certificates and reset onboarding
     */
    public function deleteCertificates(): bool
    {
        $basePath = $this->getFullStoragePath();

        $files = [
            'certificate.csr',
            'private.pem',
            'compliance_certificate.pem',
            'compliance_secret.txt',
            'compliance_request_id.txt',
            'production_certificate.pem',
            'production_secret.txt',
        ];

        foreach ($files as $file) {
            $fullPath = $basePath.'/'.$file;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // Reset database settings
        Zatca::setValue(ZatcaEnum::ONBOARDING_STATUS->value, 'pending');
        Zatca::setValue(ZatcaEnum::CSR_PATH->value, null);
        Zatca::setValue(ZatcaEnum::PRIVATE_KEY_PATH->value, null);
        Zatca::setValue(ZatcaEnum::COMPLIANCE_CERTIFICATE_PATH->value, null);
        Zatca::setValue(ZatcaEnum::COMPLIANCE_SECRET_PATH->value, null);
        Zatca::setValue(ZatcaEnum::COMPLIANCE_REQUEST_ID_PATH->value, null);
        Zatca::setValue(ZatcaEnum::PRODUCTION_CERTIFICATE_PATH->value, null);
        Zatca::setValue(ZatcaEnum::PRODUCTION_SECRET_PATH->value, null);

        return true;
    }

    /**
     * Start the onboarding process
     */
    public function onboard(string $otp): void
    {
        $this->ensureCsrExists();

        // 1. Request Compliance Certificate (CSID)
        $this->requestComplianceCertificate($otp);

        // 2. Validate Compliance (Check Invoices)
        $this->validateInvoiceCompliance();

        // 3. Request Production Certificate (PCSID)
        $this->requestProductionCertificate();
    }

    /**
     * Request Compliance Certificate from ZATCA
     */
    private function requestComplianceCertificate(string $otp): void
    {
        // Skip if already obtained? For now, let's force refresh to ensure validity
        // Or check if we have compliance cert.
        // User might want to retry validation without re-requesting cert if it failed mid-way.
        // But OTP might be single use? CSID is usually valid for a while.
        // Let's check if we already have a compliance request ID and secret.

        $basePath = $this->getFullStoragePath();
        $csrPath = $basePath.'/certificate.csr';

        // Save OTP to DB for future reference if needed, though usually short lived.
        Zatca::setValue(ZatcaEnum::ZATCA_CREDENTIALS_OTP->value, $otp);

        $environment = Zatca::getValue(ZatcaEnum::ZATCA_ENVIRONMENT->value, 'sandbox');

        // Use the library's APIService
        // We will mock it or use it directly.
        // Note: The library class is KhaledHajSalem\Zatca\Services\ZatcaAPIService
        $apiService = new \KhaledHajSalem\Zatca\Services\ZatcaAPIService($environment);

        // Read CSR content
        $csrContent = file_get_contents($csrPath);

        try {
            // Use requestComplianceCertificate which returns an object
            $result = $apiService->requestComplianceCertificate($csrContent, $otp);

            $complianceCert = $result->getCertificate();
            $complianceSecret = $result->getSecret();
            $requestId = $result->getRequestId();

            // Store files
            file_put_contents($basePath.'/compliance_certificate.pem', $complianceCert);
            file_put_contents($basePath.'/compliance_secret.txt', $complianceSecret);
            file_put_contents($basePath.'/compliance_request_id.txt', $requestId);

            // Update DB paths
            Zatca::setValue(ZatcaEnum::COMPLIANCE_CERTIFICATE_PATH->value, $basePath.'/compliance_certificate.pem');
            Zatca::setValue(ZatcaEnum::COMPLIANCE_SECRET_PATH->value, $basePath.'/compliance_secret.txt');
            Zatca::setValue(ZatcaEnum::COMPLIANCE_REQUEST_ID_PATH->value, $basePath.'/compliance_request_id.txt');

            Zatca::setValue(ZatcaEnum::ONBOARDING_STATUS->value, 'compliance_obtained');

        } catch (\Exception $e) {
            throw new \Exception('Failed to request Compliance Certificate: '.$e->getMessage());
        }
    }

    /**
     * Validate Invoice Compliance
     */
    private function validateInvoiceCompliance(): void
    {
        $basePath = $this->getFullStoragePath();
        $privateKeyPath = $basePath.'/private.pem';
        $complianceCertPath = $basePath.'/compliance_certificate.pem';
        $complianceSecretPath = $basePath.'/compliance_secret.txt';

        if (! file_exists($privateKeyPath) || ! file_exists($complianceCertPath) || ! file_exists($complianceSecretPath)) {
            throw new \Exception('Missing compliance files. Please restart onboarding.');
        }

        $privateKey = file_get_contents($privateKeyPath);
        $complianceCert = file_get_contents($complianceCertPath);
        $complianceSecret = trim(file_get_contents($complianceSecretPath));

        // Clean certificate content
        $complianceCertClean = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '], '', $complianceCert);

        $environment = Zatca::getValue(ZatcaEnum::ZATCA_ENVIRONMENT->value, 'sandbox');
        $apiService = new \KhaledHajSalem\Zatca\Services\ZatcaAPIService($environment);

        // Get Invoice Config
        $invoiceGenerator = new InvoiceGenerator($this->getInvoiceConfig());
        $invoiceType = Zatca::getValue(ZatcaEnum::INVOICE_TYPE->value, '1100');

        // Determine types to validate
        $types = [];
        // Standard (1000 or 1100)
        if ($invoiceType == 'standard' || $invoiceType == 'both' || $invoiceType == '1000' || $invoiceType == '1100') {
            $types['Standard Invoice'] = fn ($uuid) => $invoiceGenerator->standardInvoice($uuid);
            $types['Standard Credit Note'] = fn ($uuid) => $invoiceGenerator->standardCreditNote($uuid);
            $types['Standard Debit Note'] = fn ($uuid) => $invoiceGenerator->standardDebitNote($uuid);
        }
        // Simplified (0100 or 1100)
        if ($invoiceType == 'simplified' || $invoiceType == 'both' || $invoiceType == '0100' || $invoiceType == '1100') {
            $types['Simplified Invoice'] = fn ($uuid) => $invoiceGenerator->simplifiedInvoice($uuid);
            $types['Simplified Credit Note'] = fn ($uuid) => $invoiceGenerator->simplifiedCreditNote($uuid);
            $types['Simplified Debit Note'] = fn ($uuid) => $invoiceGenerator->simplifiedDebitNote($uuid);
        }

        foreach ($types as $name => $generatorFn) {
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            $invoiceData = $generatorFn($uuid);

            $zatcaInvoice = new \KhaledHajSalem\Zatca\ZatcaInvoice;

            // Sign with compliance cert
            $certObj = new \KhaledHajSalem\Zatca\Support\Certificate($complianceCertClean, $privateKey, $complianceSecret);

            // Generate XML
            $xml = $zatcaInvoice->generateXml($invoiceData, $uuid);

            // Sign XML
            $signer = \KhaledHajSalem\Zatca\Support\InvoiceSigner::signInvoice($xml, $certObj);
            $signedXml = $signer->getXML();
            $hash = $signer->getHash();

            // Validate
            $result = $apiService->validateInvoiceCompliance(
                $complianceCertClean,
                $complianceSecret,
                $signedXml,
                $hash,
                $uuid
            );

            $status = $result['validationResults']['status'] ?? 'UNKNOWN';

            if (! in_array($status, ['PASSED', 'WARNING'])) {
                // If failed, throw exception with details
                // We should log the full error for debugging
                \Illuminate\Support\Facades\Log::error("ZATCA Compliance Failed for {$name}", ['result' => $result]);

                $errors = $result['validationResults']['errorMessages'] ?? [];
                $errorMsg = implode(', ', array_map(fn ($e) => $e['message'] ?? 'Unknown error', $errors));

                throw new \Exception("Compliance check failed for {$name}: {$status}. Errors: {$errorMsg}");
            }
        }
    }

    /**
     * Request Production Certificate
     */
    private function requestProductionCertificate(): void
    {
        $basePath = $this->getFullStoragePath();
        $complianceCertPath = $basePath.'/compliance_certificate.pem';
        $complianceSecretPath = $basePath.'/compliance_secret.txt';
        $requestIdPath = $basePath.'/compliance_request_id.txt';

        $complianceCert = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '], '', file_get_contents($complianceCertPath));
        $complianceSecret = trim(file_get_contents($complianceSecretPath));
        $requestId = trim(file_get_contents($requestIdPath));

        $environment = Zatca::getValue(ZatcaEnum::ZATCA_ENVIRONMENT->value, 'sandbox');
        $apiService = new \KhaledHajSalem\Zatca\Services\ZatcaAPIService($environment);

        try {
            $result = $apiService->requestProductionCertificate($complianceCert, $complianceSecret, $requestId);

            $productionCert = $result->getCertificate();
            $productionSecret = $result->getSecret();
            // $productionRequestId = $result->getRequestId();

            // Store files
            file_put_contents($basePath.'/production_certificate.pem', $productionCert);
            file_put_contents($basePath.'/production_secret.txt', $productionSecret);

            // Update DB
            Zatca::setValue(ZatcaEnum::PRODUCTION_CERTIFICATE_PATH->value, $basePath.'/production_certificate.pem');
            Zatca::setValue(ZatcaEnum::PRODUCTION_SECRET_PATH->value, $basePath.'/production_secret.txt');

            Zatca::setValue(ZatcaEnum::ONBOARDING_STATUS->value, 'production_obtained');

        } catch (\Exception $e) {
            throw new \Exception('Failed to request Production Certificate: '.$e->getMessage());
        }
    }

    private function ensureCsrExists(): void
    {
        $basePath = $this->getFullStoragePath();
        if (! file_exists($basePath.'/certificate.csr') || ! file_exists($basePath.'/private.pem')) {
            throw new \Exception('CSR or Private Key missing. Please generate CSR first.');
        }
    }

    private function getInvoiceConfig(): array
    {
        return [
            'registered_name' => Zatca::getValue(ZatcaEnum::ORGANIZATION_NAME->value),
            'vat_number' => Zatca::getValue(ZatcaEnum::ORGANIZATION_IDENTIFIER->value),
            'address' => Zatca::getValue(ZatcaEnum::ADDRESS->value),
            'country_code' => Zatca::getValue(ZatcaEnum::COUNTRY_NAME->value),
            // Add other fields if available in ZatcaEnum and saved in DB
        ];
    }

    /**
     * Save CSR config to database
     */
    private function saveConfig(array $data, string $csrPath, string $keyPath): void
    {
        // Save CSR generation config
        Zatca::setValue(ZatcaEnum::ORGANIZATION_IDENTIFIER->value, $data['organization_identifier']);
        Zatca::setValue(ZatcaEnum::SERIAL_NUMBER_SOLUTION->value, $data['serial_number_solution']);
        Zatca::setValue(ZatcaEnum::SERIAL_NUMBER_MODEL->value, $data['serial_number_model']);
        Zatca::setValue(ZatcaEnum::SERIAL_NUMBER_DEVICE->value, $data['serial_number_device']);
        Zatca::setValue(ZatcaEnum::COMMON_NAME->value, $data['common_name']);
        Zatca::setValue(ZatcaEnum::COUNTRY_NAME->value, $data['country_name'] ?? 'SA');
        Zatca::setValue(ZatcaEnum::ORGANIZATION_NAME->value, $data['organization_name']);
        Zatca::setValue(ZatcaEnum::ORGANIZATIONAL_UNIT_NAME->value, $data['organizational_unit_name']);
        Zatca::setValue(ZatcaEnum::ADDRESS->value, $data['address']);
        Zatca::setValue(ZatcaEnum::INVOICE_TYPE->value, $data['invoice_type']);
        Zatca::setValue(ZatcaEnum::IS_PRODUCTION->value, $data['is_production'] ?? false);
        Zatca::setValue(ZatcaEnum::BUSINESS_CATEGORY->value, $data['business_category']);

        // Save paths
        Zatca::setValue(ZatcaEnum::CSR_PATH->value, $csrPath);
        Zatca::setValue(ZatcaEnum::PRIVATE_KEY_PATH->value, $keyPath);
        Zatca::setValue(ZatcaEnum::ONBOARDING_STATUS->value, 'csr_generated');
    }
}
