<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DebitCardController;
use App\Http\Controllers\DebitCardTransactionController;
use App\Models\DebitCardTransaction;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('index');
});

Route::get('/register', [AuthController::class, 'register']);
Route::post('new-member-reg', [AuthController::class, 'new_member_reg']);
Route::post('login-user', [AuthController::class, 'login_user']);

//Route::get('debit-cards', 'DebitCardController@indexdebit');
//Route::get('debit-cards', 'DebitCardController@indexdebit')->name('dashboard');
Route::get('/list-debit-cards', [DebitCardController::class, 'indexdebit']);



Route::get('/debit-cards', [DebitCardController::class, 'index']);
Route::post('/debit-cards', [DebitCardController::class, 'store']);
Route::get('/debit-cards/{debitCard}', [DebitCardController::class, 'show']);
Route::put('/debit-cards/{debitCard}', [DebitCardController::class, 'update']);
Route::delete('/debit-cards/{debitCard}', [DebitCardController::class, 'destroy']);

Route::get('/debit-card-transactions', [DebitCardTransactionController::class, 'index']);
Route::post('/debit-card-transactions', [DebitCardTransactionController::class, 'store']);
Route::get('/debit-card-transactions/{debitCardTransaction}', [DebitCardTransactionController::class, 'show']);
