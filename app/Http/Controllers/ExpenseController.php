<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Expense;
use App\Models\ExpenceType;
use App\Services\ShiftService;
use App\Services\ShiftLoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function __construct(
        private ShiftService $shiftService,
        private ShiftLoggingService $loggingService
    ) {
    }

    /**
     * Display a listing of expenses for the current shift
     */
    public function index()
    {
        $currentShift = $this->shiftService->getCurrentShift();

        if (!$currentShift) {
            return redirect()->route('shifts.start');
        }

        $expenses = Expense::with('expenceType')
            ->where('shift_id', $currentShift->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $expenseTypes = ExpenceType::all();

        return Inertia::render('Orders/Index', [
            'expenses' => $expenses,
            'expenseTypes' => $expenseTypes,
        ]);
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'expenseTypeId' => 'required|exists:expence_types,id',
            'description' => 'required|string|max:255',
        ]);

        $currentShift = $this->shiftService->getCurrentShift();

        if (!$currentShift) {
            return redirect()->route('shifts.start');
        }

        try {
            DB::transaction(function () use ($request, $currentShift) {
                $expenseType = ExpenceType::find($request->expenseTypeId);

                $expense = Expense::create([
                    'shift_id' => $currentShift->id,
                    'expence_type_id' => $request->expenseTypeId,
                    'amount' => $request->amount,
                    'notes' => $request->description,
                ]);

                // Log expense creation
                $this->loggingService->logExpenseAction('create', [
                    'id' => $expense->id,
                    'expense_type_name' => $expenseType->name,
                    'amount' => $request->amount,
                    'description' => $request->description,
                ]);
            });

            return back()->with('success', 'تم إضافة المصروف بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في إضافة مصروف', [
                'expense_type_id' => $request->expenseTypeId,
                'amount' => $request->amount,
                'description' => $request->description,
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['message' => 'حدث خطأ أثناء إضافة المصروف: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'expenseTypeId' => 'required|exists:expence_types,id',
            'description' => 'required|string|max:255',
        ]);

        $currentShift = $this->shiftService->getCurrentShift();

        if (!$currentShift || $expense->shift_id !== $currentShift->id) {
            return back()->withErrors(['message' => 'لا يمكن تعديل هذا المصروف']);
        }

        try {
            DB::transaction(function () use ($request, $expense) {
                $oldExpenseType = $expense->expenceType;
                $newExpenseType = ExpenceType::find($request->expenseTypeId);

                $oldData = [
                    'expense_type_name' => $oldExpenseType->name,
                    'amount' => $expense->amount,
                    'description' => $expense->notes,
                ];

                $expense->update([
                    'expence_type_id' => $request->expenseTypeId,
                    'amount' => $request->amount,
                    'notes' => $request->description,
                ]);

                // Log expense update
                $this->loggingService->logExpenseAction('update', [
                    'id' => $expense->id,
                    'old_data' => $oldData,
                    'new_data' => [
                        'expense_type_name' => $newExpenseType->name,
                        'amount' => $request->amount,
                        'description' => $request->description,
                    ],
                ]);
            });

            return back()->with('success', 'تم تعديل المصروف بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في تعديل مصروف', [
                'expense_id' => $expense->id,
                'expense_type_id' => $request->expenseTypeId,
                'amount' => $request->amount,
                'description' => $request->description,
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['message' => 'حدث خطأ أثناء تعديل المصروف: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy(Expense $expense)
    {
        $currentShift = $this->shiftService->getCurrentShift();

        if (!$currentShift || $expense->shift_id !== $currentShift->id) {
            return back()->withErrors(['message' => 'لا يمكن حذف هذا المصروف']);
        }

        try {
            DB::transaction(function () use ($expense) {
                $expenseData = [
                    'id' => $expense->id,
                    'expense_type_name' => $expense->expenceType->name,
                    'amount' => $expense->amount,
                    'description' => $expense->notes,
                ];

                $expense->delete();

                // Log expense deletion
                $this->loggingService->logExpenseAction('delete', $expenseData);
            });

            return back()->with('success', 'تم حذف المصروف بنجاح');
        } catch (Exception $e) {
            $this->loggingService->logAction('فشل في حذف مصروف', [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ], 'error');

            return back()->withErrors(['message' => 'حدث خطأ أثناء حذف المصروف: ' . $e->getMessage()]);
        }
    }
}
