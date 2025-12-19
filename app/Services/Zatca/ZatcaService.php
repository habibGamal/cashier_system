<?php

namespace App\Services\Zatca;

use App\Enums\ZatcaEnum;
use App\Models\Zatca;

class ZatcaService
{
    public function __construct(
        private readonly ZatcaOnboardingService $onboardingService
    ) {}

    /**
     * Get all ZATCA configuration for frontend
     */
    public function getZatcaConfig(): array
    {
        return [
            // Environment
            'environment' => Zatca::getValue(ZatcaEnum::ZATCA_ENVIRONMENT->value, 'sandbox'),

            // Phase
            'phase' => Zatca::getValue(ZatcaEnum::ZATCA_PHASE->value, ZatcaEnum::PHASE_1->value),

            // CSR Generation Fields
            'organization_identifier' => Zatca::getValue(ZatcaEnum::ORGANIZATION_IDENTIFIER->value, ''),
            'serial_number_solution' => Zatca::getValue(ZatcaEnum::SERIAL_NUMBER_SOLUTION->value, ''),
            'serial_number_model' => Zatca::getValue(ZatcaEnum::SERIAL_NUMBER_MODEL->value, ''),
            'serial_number_device' => Zatca::getValue(ZatcaEnum::SERIAL_NUMBER_DEVICE->value, ''),
            'common_name' => Zatca::getValue(ZatcaEnum::COMMON_NAME->value, ''),
            'country_name' => Zatca::getValue(ZatcaEnum::COUNTRY_NAME->value, 'SA'),
            'organization_name' => Zatca::getValue(ZatcaEnum::ORGANIZATION_NAME->value, ''),
            'organizational_unit_name' => Zatca::getValue(ZatcaEnum::ORGANIZATIONAL_UNIT_NAME->value, ''),
            'address' => Zatca::getValue(ZatcaEnum::ADDRESS->value, ''),
            'invoice_type' => Zatca::getValue(ZatcaEnum::INVOICE_TYPE->value, 'both'),
            'is_production' => (bool) Zatca::getValue(ZatcaEnum::IS_PRODUCTION->value, false),
            'business_category' => Zatca::getValue(ZatcaEnum::BUSINESS_CATEGORY->value, ''),
        ];
    }

    /**
     * Save phase setting
     */
    public function savePhase(string $phase): void
    {
        Zatca::setValue(ZatcaEnum::ZATCA_PHASE->value, $phase);
    }

    /**
     * Get certificate status (delegated to onboarding service)
     */
    public function getCertificateStatus(): array
    {
        return $this->onboardingService->getCertificateStatus();
    }
}
