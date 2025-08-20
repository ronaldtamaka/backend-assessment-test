<?php

namespace App\Http\Requests;

use App\Models\DebitCard;
use Illuminate\Foundation\Http\FormRequest;

class DebitCardTransactionShowIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
          return true;
    }

    public function rules(): array
    {
        return [
            'debit_card_id' => 'required|integer|exists:debit_cards,id',
        ];
    }
}
