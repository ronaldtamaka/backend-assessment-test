<?php

namespace Tests\Feature\Helper;

class DebitCardTestHelper
{
    public static function createDebitCard($request, $data=['type' => 'VISA'])
    {
        return $request->post('/api/debit-cards', $data);
    }

    public static function createTransaction($request, $debitCardId)
    {
        // create transaction
        $urlCreateTransaction = '/api/debit-card-transactions';
        $responseCreateTransaction = $request->post($urlCreateTransaction, [
            'currency_code' => 'IDR',
            'amount' => 100,
            'debit_card_id' => $debitCardId,
        ]);
        return $responseCreateTransaction;
    }
}
