<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CompleteOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash' => 'required|numeric|min:0',
            'card' => 'required|numeric|min:0',
            'talabat_card' => 'required|numeric|min:0',
            'print' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'cash.required' => 'مبلغ النقدي مطلوب',
            'cash.numeric' => 'مبلغ النقدي يجب أن يكون رقم',
            'cash.min' => 'مبلغ النقدي يجب أن يكون أكبر من أو يساوي صفر',
            'card.required' => 'مبلغ البطاقة مطلوب',
            'card.numeric' => 'مبلغ البطاقة يجب أن يكون رقم',
            'card.min' => 'مبلغ البطاقة يجب أن يكون أكبر من أو يساوي صفر',
            'talabat_card.required' => 'مبلغ بطاقة طلبات مطلوب',
            'talabat_card.numeric' => 'مبلغ بطاقة طلبات يجب أن يكون رقم',
            'talabat_card.min' => 'مبلغ بطاقة طلبات يجب أن يكون أكبر من أو يساوي صفر',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cash' => $this->input('cash', 0),
            'card' => $this->input('card', 0),
            'talabat_card' => $this->input('talabat_card', 0),
            'print' => $this->boolean('print'),
        ]);
    }
}
