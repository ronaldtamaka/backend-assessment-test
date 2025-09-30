<?php

namespace App\Http\Requests;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Illuminate\Foundation\Http\FormRequest;

class DebitCardTransactionShowIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Always allow - authorization handled in controller
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
        ];
    }
}
