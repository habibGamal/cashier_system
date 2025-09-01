@if (!$analysisResult)
    <div></div>
@elseif (!$analysisResult['success'])
    <div class='text-red-600'>خطأ: {{ $analysisResult['error'] }}</div>
@else
    <div class='mb-4'>
        <strong>عدد الأوراق:</strong> {{ $analysisResult['total_sheets'] }}
    </div>

    @foreach ($analysisResult['sheets'] as $sheetName => $sheetData)
        <div class='border border-gray-300 rounded-sm p-4 mb-4'>
            <h4 class='font-bold text-lg mb-2'>{{ $sheetName }}</h4>
            <div class='space-y-1 mb-3'>
                <div><strong>العناوين:</strong> {{ implode(', ', $sheetData['headers']) }}</div>
                <div><strong>عدد الصفوف:</strong> {{ $sheetData['estimated_data_rows'] }} صف</div>
                <div><strong>عدد الأعمدة:</strong> {{ $sheetData['total_columns'] }} عمود</div>
            </div>

            @if (!empty($sheetData['sample_data']))
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-300 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                @foreach ($sheetData['headers'] as $header)
                                    <th class='border border-gray-300 px-2 py-1'>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($sheetData['sample_data'], 0, 3) as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td class='border border-gray-300 px-2 py-1'>{{ $cell ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
@endif
