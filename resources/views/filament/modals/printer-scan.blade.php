<div x-data="printerScanModal()" x-init="init()">
    <div class="space-y-4">
        <!-- Scan Status -->
        <div x-show="scanning" class="flex items-center space-x-2 p-4 bg-blue-50 rounded-lg">
            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-blue-700">جار البحث عن الطابعات...</span>
        </div>

        <!-- Network Range Input -->
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">نطاق الشبكة</label>
            <input
                x-model="networkRange"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                placeholder="192.168.1.0/24"
            >
            <p class="text-sm text-gray-500">أدخل نطاق الشبكة للبحث (مثال: 192.168.1.0/24)</p>
        </div>

        <!-- Found Printers -->
        <div x-show="printers.length > 0" class="space-y-3">
            <h3 class="text-lg font-medium text-gray-900">الطابعات المكتشفة</h3>

            <template x-for="printer in printers" :key="printer.ip">
                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-gray-900" x-text="printer.ip"></span>
                            <span class="ml-2 px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">متاح</span>
                        </div>
                    </div>

                    <div class="flex space-x-2 space-x-reverse">
                        <!-- Test Button -->
                        <button
                            @click="testPrinter(printer.ip)"
                            :disabled="printer.testing"
                            class="px-3 py-1 text-sm bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-300 text-white rounded-md transition-colors duration-200"
                        >
                            <span x-show="!printer.testing">اختبار</span>
                            <span x-show="printer.testing" class="flex items-center">
                                <svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                جار الاختبار...
                            </span>
                        </button>

                        <!-- Select Button -->
                        <button
                            @click="selectPrinter(printer.ip)"
                            class="px-3 py-1 text-sm bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors duration-200"
                        >
                            تحديد
                        </button>
                    </div>

                    <!-- Test Result -->
                    <div x-show="printer.testResult" class="mt-2">
                        <div
                            :class="printer.testResult.success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                            class="p-2 rounded-sm text-sm"
                            x-text="printer.testResult.message"
                        ></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- No Printers Found -->
        <div x-show="!scanning && printers.length === 0 && hasScanned" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-12V9a2 2 0 00-2-2H9a2 2 0 00-2 2v8.83" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">لم يتم العثور على طابعات</h3>
            <p class="mt-1 text-sm text-gray-500">تأكد من أن الطابعات متصلة بالشبكة وأن نطاق الشبكة صحيح</p>
        </div>
    </div>
</div>

<script>
function printerScanModal() {
    return {
        printers: [],
        scanning: false,
        hasScanned: false,
        networkRange: '192.168.1.0/24',

        init() {
            // Listen for printer scan results
            this.$wire.on('printers-scanned', (data) => {
                this.printers = data[0].map(printer => ({
                    ...printer,
                    testing: false,
                    testResult: null
                }));
                this.scanning = false;
                this.hasScanned = true;
            });
        },

        async testPrinter(ip) {
            const printer = this.printers.find(p => p.ip === ip);
            if (!printer) return;

            printer.testing = true;
            printer.testResult = null;

            try {
                const response = await fetch('/admin/printers/test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ip_address: ip })
                });

                const result = await response.json();
                printer.testResult = result;
            } catch (error) {
                printer.testResult = {
                    success: false,
                    message: 'حدث خطأ أثناء الاختبار'
                };
            } finally {
                printer.testing = false;
            }
        },

        selectPrinter(ip) {
            // Set the IP address in the form
            this.$wire.set('data.ip_address', ip);

            // Close the modal
            this.$wire.dispatch('close-modal', { id: 'scan_printers' });

            // Show success notification
            window.$wireui.notify({
                title: 'تم التحديد',
                description: `تم تحديد الطابعة: ${ip}`,
                icon: 'success'
            });
        }
    }
}
</script>
