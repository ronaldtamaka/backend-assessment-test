<?php

namespace App\Http\Requests;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebitCardTransactionCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Always allow authorization, let validation handle non-existent debit_card_id
        // Only check policy if debit card exists
        if ($this->input('debit_card_id')) {
            $debitCard = DebitCard::find($this->input('debit_card_id'));
            
            if ($debitCard) {
                return $this->user()->can('create', [DebitCardTransaction::class, $debitCard]);
            }
        }
        
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'debit_card_id' => 'required|integer|exists:debit_cards,id',
            'amount' => 'required|integer',
            'currency_code' => [
                'required',
                Rule::in(DebitCardTransaction::CURRENCIES),
            ],
        ];
    }
}
