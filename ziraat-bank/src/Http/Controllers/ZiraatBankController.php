<?php

namespace Botble\ZiraatBank\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\InvoicePayment\Models\Invoice;
use Illuminate\Http\Request;

class ZiraatBankController extends BaseController
{
    public function callback(Request $request)
    {
        if (isset($request->Response) && $request->Response == "Approved" && 
            isset($request->mdStatus) && $request->mdStatus == 1 && 
            isset($request->oid)) {
            
            $invoiceId = $request->oid;
            $invoice = Invoice::find($invoiceId);
            
            if ($invoice && $invoice->status === 'pending') {
                // Payment işlemini kaydet
                if (defined('PAYMENT_ACTION_PAYMENT_PROCESSED')) {
                    $status = class_exists('\Botble\Payment\Enums\PaymentStatusEnum') 
                        ? \Botble\Payment\Enums\PaymentStatusEnum::COMPLETED 
                        : 'completed';
                    
                    do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                        'amount' => $invoice->amount,
                        'currency' => $invoice->currency ? $invoice->currency->title : 'TRY',
                        'charge_id' => $request->TransId ?? uniqid(),
                        'payment_channel' => ZIRAAT_BANK_PAYMENT_METHOD_NAME,
                        'status' => $status,
                        'customer_id' => $invoice->customer_id,
                        'customer_type' => $invoice->customer_type,
                        'order_id' => $invoice->id,
                    ], $request);
                }

                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                return redirect()->route('invoice-payment.success');
            }
        }
        
        return redirect()->route('invoice-payment.error')
            ->with('error', 'Ödeme işlemi başarısız oldu.');
    }
}
