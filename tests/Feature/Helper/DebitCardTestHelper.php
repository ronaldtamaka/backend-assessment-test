<?php

namespace Tests\Feature\Helper;

use App\Models\DebitCard;

class DebitCardTestHelper
{
    public static function createDebitCard($request, $data=['type' => 'VISA'])
    {
        return $request->post('/api/debit-cards', $data);
    }
}
