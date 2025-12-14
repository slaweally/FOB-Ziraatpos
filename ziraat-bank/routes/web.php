<?php

use Botble\ZiraatBank\Http\Controllers\ZiraatBankController;
use Illuminate\Support\Facades\Route;

Route::middleware('core')->group(function () {
    Route::post('payment/ziraat-bank/callback', [ZiraatBankController::class, 'callback'])
        ->name('ziraat-bank.callback');
});
