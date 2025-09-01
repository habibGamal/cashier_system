@if (!$importResult)
    <div></div>
@elseif (!$importResult['success'])
    <div class='text-red-600'>خطأ في الاستيراد: {{ $importResult['error'] }}</div>
@else
    <div class='space-y-2 mb-4'>
        <div class='text-green-600'>
            <strong>إجمالي السجلات المستوردة:</strong> {{ $importResult['imported_count'] }} سجل
        </div>
        <div class='text-red-600'>
            <strong>إجمالي الأخطاء:</strong> {{ $importResult['error_count'] }} سجل
        </div>
    </div>

    @if (!empty($importResult['sheet_results']))
        <div class='space-y-4'>
            <h4 class='font-bold'>نتائج الأوراق:</h4>

            @foreach ($importResult['sheet_results'] as $sheetName => $result)
                <div class='border border-gray-300 rounded-sm p-3'>
                    <h5 class='font-semibold'>{{ $sheetName }}</h5>
                    <div class='text-sm space-y-1'>
                        <div class='text-green-600'>نجح: {{ $result['success_count'] }} سجل</div>
                        <div class='text-red-600'>أخطاء: {{ $result['error_count'] }} سجل</div>

                        @if (!empty($result['errors']))
                            <div class='mt-2'>
                                <strong class='text-red-600'>الأخطاء:</strong>
                                <ul class='list-disc list-inside text-xs'>
                                    @foreach (array_slice($result['errors'], 0, 3) as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                    @if (count($result['errors']) > 3)
                                        <li class='text-gray-500'>...و {{ count($result['errors']) - 3 }} خطأ إضافي</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($importResult['errors']) && count($importResult['errors']) > 0)
        <div class="mt-4">
            <strong class="text-red-600">ملخص جميع الأخطاء:</strong>
            <div class="max-h-32 overflow-y-auto bg-red-50 p-2 rounded-sm text-xs">
                @foreach (array_slice($importResult['errors'], 0, 20) as $error)
                    <div class='text-red-600'>{{ $error }}</div>
                @endforeach
                @if (count($importResult['errors']) > 20)
                    <div class='text-gray-500'>...و {{ count($importResult['errors']) - 20 }} خطأ إضافي</div>
                @endif
            </div>
        </div>
    @endif
@endif
