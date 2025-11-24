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
        $debitCard = DebitCard::find($this->input('debit_card_id'));

        if (!$debitCard) {
            return true;
        }

        return $this->user()->can('view', $debitCard);
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

    /**
     * Get all of the input and files for the request.
     *
     * @param array|mixed $keys
     * @return array
     */
    public function all($keys = null)
    {
        $data = parent::all($keys);

        if ($this->header('debit_card_id')) {
            $data['debit_card_id'] = $this->header('debit_card_id');
        }

        return $data;
    }

    /**
     * Retrieve an input item from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        $value = parent::input($key, null);

        if ($value === null && $key && $this->header($key)) {
            return $this->header($key);
        }

        return $value ?? $default;
    }
}
