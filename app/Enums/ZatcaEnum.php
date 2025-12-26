<?php

namespace App\Enums;

enum ZatcaEnum: string
{
    // Environment
    case ZATCA_ENVIRONMENT = 'zatca_environment'; // sandbox, simulation, production

    // Phase
    case PHASE_1 = 'phase_1';
    case PHASE_2 = 'phase_2';
    case ZATCA_PHASE = 'zatca_phase';

    // CSR Generation Fields
    case ORGANIZATION_IDENTIFIER = 'zatca_organization_identifier';  // VAT Number (15 digits)
    case SERIAL_NUMBER_SOLUTION = 'zatca_serial_number_solution';
    case SERIAL_NUMBER_MODEL = 'zatca_serial_number_model';
    case SERIAL_NUMBER_DEVICE = 'zatca_serial_number_device';
    case COMMON_NAME = 'zatca_common_name';
    case COUNTRY_NAME = 'zatca_country_name';
    case ORGANIZATION_NAME = 'zatca_organization_name';
    case ORGANIZATIONAL_UNIT_NAME = 'zatca_organizational_unit_name';
    case ADDRESS = 'zatca_address';
    case INVOICE_TYPE = 'zatca_invoice_type';  // 1000, 0100, or 1100
    case IS_PRODUCTION = 'zatca_is_production';  // false for simulation
    case BUSINESS_CATEGORY = 'zatca_business_category';

    // Certificate Paths (stored relative to tenant storage)
    case CSR_PATH = 'zatca_csr_path';
    case PRIVATE_KEY_PATH = 'zatca_private_key_path';
    case COMPLIANCE_CERTIFICATE_PATH = 'zatca_compliance_certificate_path';
    case COMPLIANCE_SECRET_PATH = 'zatca_compliance_secret_path';
    case COMPLIANCE_REQUEST_ID_PATH = 'zatca_compliance_request_id_path';
    case PRODUCTION_CERTIFICATE_PATH = 'zatca_production_certificate_path';
    case PRODUCTION_SECRET_PATH = 'zatca_production_secret_path';

    case ZATCA_CREDENTIALS_OTP = 'zatca_credentials_otp';
    // Onboarding Status
    case ONBOARDING_STATUS = 'onboarding_status';  // pending, csr_generated, compliance_obtained, production_obtained
}
