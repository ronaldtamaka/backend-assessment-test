<?php

namespace App\Http\Resources;

use App\Models\DebitCard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DebitCard
 */
class DebitCardTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
        ];
    }

}
