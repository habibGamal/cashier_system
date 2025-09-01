<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Exception;

class ExcelImportService
{
    /**
     * Parse Excel file and return structure analysis
     */
    public function analyzeExcelStructure(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            // Get headers from first row
            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getCalculatedValue();
                $headers[] = $cellValue;
            }

            // Get sample data from first 5 rows (excluding header)
            $sampleData = [];
            $maxSampleRows = min(6, $highestRow); // Header + 5 data rows

            for ($row = 2; $row <= $maxSampleRows; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                    $rowData[] = $cellValue;
                }
                $sampleData[] = $rowData;
            }

            return [
                'success' => true,
                'headers' => $headers,
                'sample_data' => $sampleData,
                'total_rows' => $highestRow,
                'total_columns' => $highestColumnIndex,
                'estimated_data_rows' => $highestRow - 1, // Excluding header
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process Excel file and import data based on detected structure
     */
    public function importExcelData(UploadedFile $file): array
    {
        try {
            $analysis = $this->analyzeExcelStructure($file);

            if (!$analysis['success']) {
                return $analysis;
            }

            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = $analysis['headers'];
            $totalRows = $analysis['total_rows'];
            $totalColumns = $analysis['total_columns'];

            $importedData = [];
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            // Process each data row
            for ($row = 2; $row <= $totalRows; $row++) {
                try {
                    $rowData = [];

                    // Map column data to headers
                    for ($col = 1; $col <= $totalColumns; $col++) {
                        $headerIndex = $col - 1;
                        $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();

                        if (isset($headers[$headerIndex])) {
                            $rowData[$headers[$headerIndex]] = $cellValue;
                        }
                    }

                    // Skip empty rows
                    if (empty(array_filter($rowData))) {
                        continue;
                    }

                    // Here you would process the row data based on your business logic
                    $processed = $this->processRowData($rowData, $row);

                    if ($processed['success']) {
                        $importedData[] = $processed['data'];
                        $successCount++;
                    } else {
                        $errors[] = "Row {$row}: " . $processed['error'];
                        $errorCount++;
                    }

                } catch (Exception $e) {
                    $errors[] = "Row {$row}: " . $e->getMessage();
                    $errorCount++;
                }
            }

            return [
                'success' => true,
                'imported_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
                'sample_imported_data' => array_slice($importedData, 0, 5), // First 5 records
                'headers' => $headers,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process individual row data - customize this based on your needs
     */
    private function processRowData(array $rowData, int $rowNumber): array
    {
        try {
            // This is where you would implement your specific business logic
            // For now, we'll just validate and clean the data

            $processedData = [];

            foreach ($rowData as $header => $value) {
                // Clean and process the value
                $processedValue = $this->cleanValue($value);
                $processedData[$this->normalizeHeader($header)] = $processedValue;
            }

            return [
                'success' => true,
                'data' => $processedData,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean and normalize cell values
     */
    protected function cleanValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Convert to string and trim
        $cleaned = trim((string) $value);

        // Handle numeric values
        if (is_numeric($cleaned)) {
            return is_float($cleaned + 0) ? (float) $cleaned : (int) $cleaned;
        }

        return $cleaned;
    }

    /**
     * Normalize header names for consistent processing
     */
    protected function normalizeHeader(string $header): string
    {
        // Convert to lowercase and replace spaces with underscores
        $normalized = strtolower(trim($header));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        return $normalized;
    }
}
