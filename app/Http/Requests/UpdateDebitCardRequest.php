<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDebitCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $cardId = $this->route('debitCard')->id;

        return [
            '_method' => ['required', 'in:PUT'],
            'number' => ['sometimes', 'unique:debit_cards,number,' . $cardId],
            'bank_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
