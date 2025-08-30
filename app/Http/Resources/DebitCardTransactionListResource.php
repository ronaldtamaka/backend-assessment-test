<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebitCardTransactionListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'amount' => (int) $this->amount,
            'currency_code' => $this->currency_code,
        ];
    }
}
