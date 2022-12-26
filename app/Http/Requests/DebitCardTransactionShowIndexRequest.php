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
        dd($this->input());
        $debitCard = DebitCard::find($this->input('debit_card_id'));
        dd($debitCard);
        return $debitCard && $this->user()->can('view', $debitCard);
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
