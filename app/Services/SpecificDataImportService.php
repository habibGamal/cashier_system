<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Printer;
use App\Models\ProductComponent;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

class SpecificDataImportService extends ExcelImportService
{
    /**
     * Analyze Excel file with multiple sheets
     */
    public function analyzeExcelStructure(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheetsAnalysis = [];

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $sheetName = $worksheet->getTitle();

                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                // Get headers from first row
                $headers = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getCalculatedValue();
                    $headers[] = $cellValue;
                }

                // Get sample data from first 3 rows (excluding header)
                $sampleData = [];
                $maxSampleRows = min(4, $highestRow); // Header + 3 data rows

                for ($row = 2; $row <= $maxSampleRows; $row++) {
                    $rowData = [];
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                        $rowData[] = $cellValue;
                    }
                    if (!empty(array_filter($rowData))) {
                        $sampleData[] = $rowData;
                    }
                }

                $sheetsAnalysis[$sheetName] = [
                    'headers' => $headers,
                    'sample_data' => $sampleData,
                    'total_rows' => $highestRow,
                    'total_columns' => $highestColumnIndex,
                    'estimated_data_rows' => $highestRow - 1,
                ];
            }

            return [
                'success' => true,
                'sheets' => $sheetsAnalysis,
                'total_sheets' => count($sheetsAnalysis),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import data from multiple sheets
     */
    public function importExcelData(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());

            $results = [];
            $totalSuccess = 0;
            $totalErrors = 0;
            $allErrors = [];

            DB::beginTransaction();

            // Process sheets in specific order for dependencies
            $processingOrder = ['Printer', 'الفئات', 'الخام', 'الاستهلاكي', 'التصنيع', 'المعياري'];

            foreach ($processingOrder as $sheetName) {
                try {
                    $worksheet = $spreadsheet->getSheetByName($sheetName);
                    if (!$worksheet) {
                        // Skip if sheet doesn't exist
                        $results[$sheetName] = [
                            'success_count' => 0,
                            'error_count' => 0,
                            'errors' => ["الورقة '{$sheetName}' غير موجودة"],
                        ];
                        continue;
                    }

                    $result = $this->processSheet($worksheet, $sheetName);
                    $results[$sheetName] = $result;

                    $totalSuccess += $result['success_count'];
                    $totalErrors += $result['error_count'];
                    $allErrors = array_merge($allErrors, $result['errors']);
                } catch (Exception $e) {
                    $results[$sheetName] = [
                        'success_count' => 0,
                        'error_count' => 1,
                        'errors' => ["خطأ في معالجة الورقة '{$sheetName}': " . $e->getMessage()],
                    ];
                    $totalErrors++;
                    $allErrors[] = "خطأ في معالجة الورقة '{$sheetName}': " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'imported_count' => $totalSuccess,
                'error_count' => $totalErrors,
                'errors' => $allErrors,
                'sheet_results' => $results,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a single sheet based on its type
     */
    private function processSheet($worksheet, string $sheetName): array
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // Get headers
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getCalculatedValue();
            $headers[] = $cellValue;
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Process data rows
        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $rowData = [];

                // Map column data to headers
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
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

                $result = $this->processSheetRow($rowData, $sheetName, $row);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $errors[] = "{$sheetName} - Row {$row}: " . $result['error'];
                    $errorCount++;
                }

            } catch (Exception $e) {
                $errors[] = "{$sheetName} - Row {$row}: " . $e->getMessage();
                $errorCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    /**
     * Process a single row based on sheet type
     */
    private function processSheetRow(array $rowData, string $sheetName, int $rowNumber): array
    {
        switch ($sheetName) {
            case 'Printer':
                return $this->processPrinterRow($rowData);
            case 'الفئات':
                return $this->processCategoryRow($rowData);
            case 'الخام':
                return $this->processRawProductRow($rowData);
            case 'الاستهلاكي':
                return $this->processConsumableRow($rowData);
            case 'التصنيع':
                return $this->processProductionRow($rowData);
            case 'المعياري':
                return $this->processStandardRow($rowData);
            default:
                return [
                    'success' => true,
                    'data' => $rowData,
                ];
        }
    }

    /**
     * Process printer data
     */
    private function processPrinterRow(array $data): array
    {
        try {
            $printerName = $data['printer_name'] ?? null;

            if (empty($printerName)) {
                return ['success' => false, 'error' => 'اسم الطابعة مطلوب'];
            }

            $printer = Printer::updateOrCreate(
                ['name' => $printerName],
                [
                    'name' => $printerName,
                    'ip_address' => $data['ipAddr'] ?? null,
                ]
            );

            return [
                'success' => true,
                'data' => ['printer_id' => $printer->id, 'name' => $printer->name],
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process category data
     */
    private function processCategoryRow(array $data): array
    {
        try {
            $categoryName = $data['الفئات'] ?? null;

            if (empty($categoryName)) {
                return ['success' => false, 'error' => 'اسم الفئة مطلوب'];
            }

            $category = Category::updateOrCreate(
                ['name' => $categoryName],
                ['name' => $categoryName]
            );

            return [
                'success' => true,
                'data' => ['category_id' => $category->id, 'name' => $category->name],
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process raw product data
     */
    private function processRawProductRow(array $data): array
    {
        try {
            $productName = $data['أسم المنتج'] ?? null;

            if (empty($productName)) {
                return ['success' => false, 'error' => 'اسم المنتج مطلوب'];
            }

            // Get or create category
            $categoryId = null;
            if (!empty($data['الفئة'])) {
                $category = Category::firstOrCreate(['name' => $data['الفئة']]);
                $categoryId = $category->id;
            }
            $product = Product::updateOrCreate(
                ['name' => $productName],
                [
                    'name' => $productName,
                    'cost' => $data['التكلفة'] ?? null,
                    'price' => $data['التكلفة'] ?? null,
                    'unit' => $data['الوحدة'] ?? null,
                    'category_id' => $categoryId,
                    'type' => ProductType::RawMaterial,
                ]
            );


            return [
                'success' => true,
                'data' => ['product_id' => $product->id, 'name' => $product->name, 'type' => 'raw'],
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process production product data
     */
    private function processProductionRow(array $data): array
    {
        try {
            $productName = $data['أسم المنتج'] ?? null;

            if (empty($productName)) {
                return ['success' => false, 'error' => 'اسم المنتج مطلوب'];
            }

            // Get or create category
            $categoryId = null;
            if (!empty($data['الفئة'])) {
                $category = Category::firstOrCreate(['name' => $data['الفئة']]);
                $categoryId = $category->id;
            }

            // Get barcode if exists
            $barcode = $data['barcode'] ?? null;

            $product = Product::updateOrCreate(
                ['name' => $productName],
                [
                    'name' => $productName,
                    'price' => $data['السعر'] ?? null,
                    'cost' => 0,
                    'unit' => 'باكت',
                    'category_id' => $categoryId,
                    'type' => ProductType::Manufactured,
                    'barcode' => $barcode,
                ]
            );

            return [
                'success' => true,
                'data' => ['product_id' => $product->id, 'name' => $product->name, 'type' => 'production', 'barcode' => $barcode],
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process consumable product data
     */
    private function processConsumableRow(array $data): array
    {
        try {
            $productName = $data['أسم المنتج'] ?? null;

            if (empty($productName)) {
                return ['success' => false, 'error' => 'اسم المنتج مطلوب'];
            }

            // Get or create category
            $categoryId = null;
            if (!empty($data['الفئة'])) {
                $category = Category::firstOrCreate(['name' => $data['الفئة']]);
                $categoryId = $category->id;
            }

            // Get barcode if exists
            $barcode = $data['barcode'] ?? null;

            $product = Product::updateOrCreate(
                ['name' => $productName],
                [
                    'name' => $productName,
                    'cost' => $data['التكلفة'] ?? null,
                    'price' => $data['السعر'] ?? null,
                    'unit' => $data['الوحدة'] ?? null,
                    'category_id' => $categoryId,
                    'type' => ProductType::Consumable,
                    'barcode' => $barcode,
                ]
            );

            return [
                'success' => true,
                'data' => ['product_id' => $product->id, 'name' => $product->name, 'type' => 'consumable', 'barcode' => $barcode],
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process standard recipe data (product components)
     */
    private function processStandardRow(array $data): array
    {
        try {
            $finalProductName = $data['أسم المنتج المصنع'] ?? null;
            $componentName = $data['أسم المكون'] ?? null;
            $quantity = $data['الكمية'] ?? null;

            if (empty($finalProductName) || empty($componentName) || !is_numeric($quantity)) {
                return ['success' => false, 'error' => 'اسم المنتج النهائي والمكون والكمية مطلوبة'];
            }

            // Find the final product
            $finalProduct = Product::where('name', $finalProductName)->first();
            if (!$finalProduct) {
                return ['success' => false, 'error' => "المنتج النهائي '{$finalProductName}' غير موجود"];
            }

            // Find the component
            $component = Product::where('name', $componentName)->first();
            if (!$component) {
                return ['success' => false, 'error' => "المكون '{$componentName}' غير موجود"];
            }

            // Create or update the component relationship
            ProductComponent::updateOrCreate(
                [
                    'product_id' => $finalProduct->id,
                    'component_id' => $component->id,
                ],
                [
                    'quantity' => (float) $quantity,
                ]
            );

            // update the final product's cost based on components
            $finalProduct->cost = $finalProduct->components()->sum(DB::raw('quantity * (select cost from products WHERE products.id  = `product_components`.`component_id`)'));
            $finalProduct->save();

            return [
                'success' => true,
                'data' => [
                    'final_product' => $finalProduct->name,
                    'component' => $component->name,
                    'quantity' => (float) $quantity,
                ],
            ];

        } catch (Exception $e) {
            dd($e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
