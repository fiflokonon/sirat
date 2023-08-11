<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function recharge(string $identifiant, Request $request)
    {
        $user = User::where('unique_id', $identifiant)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur indisponible'], 404);
        }

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:100']
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            return response()->json(['success' => false, 'message' => $errorMessage], 400);
        }

        $paymentRequest = $this->createPayplusInvoice($request);
        #dd($paymentRequest['token']);
        if (!$paymentRequest['success']) {
            return response()->json(['success' => false, 'message' => 'La requête de paiement a échoué']);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'phone' => $request->phone,
            'amount' => $request->amount,
            'token' => $paymentRequest['token'],
            'status' => 'pending'
        ]);
        return response()->json($paymentRequest);
    }

    public function checking(Request $request)
    {
        $token = $request->token;
        if ($token)
        {
            $payment = Payment::where('token', $token)->first();
            if ($payment)
            {
                $checkingRequest = $this->fetchInvoiceStatus($token);
                if ($checkingRequest['success'] && $checkingRequest['status'] == 'completed')
                {
                    if ($payment->status != 'completed')
                    {
                        $user = $payment->user;
                        $user->balance = $user->balance + $payment->amount;
                        $user->save();
                        $payment->status = 'completed';
                        $payment->save();
                        return response()->json(['success' => true, 'message' => "Recharge validée", 'response' => $payment->user]);
                    }else{
                        return response()->json(['success' => false, 'message' => 'Transaction dejà validée'], 400);
                    }
                }
                elseif ($checkingRequest['success'] && $checkingRequest['status'] == 'pending'){
                    return response()->json(['success' => false, 'message' => 'Paiement en cours de validation', 'pending' => true]);
                }
                else{
                    return response()->json(['success' => false, 'message' => 'Paiement non validé']);
                }
            }else{
                return response()->json(['success' => false, 'message' => 'Paiement indisponible'], 404);
            }
        }else{
            return response()->json(['success' => false, 'message' => 'Token requis']);
        }
    }

    public function newChecking(Request $request)
    {
        $token = $request->token;
        if ($request->user() == null)
        {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        else{
            $agent = $request->user();
            if ($token)
            {
                $payment = Payment::where('token', $token)->first();
                if ($payment)
                {
                    $checkingRequest = $this->fetchInvoiceStatus($token);
                    if ($checkingRequest['success'] && $checkingRequest['status'] == 'completed')
                    {
                        if ($payment->status != 'completed')
                        {
                            $payment->status = 'completed';
                            Scan::create([
                                'agent_id' => $agent->id,
                                'scanned_id' => $payment->user->id
                            ]);
                            $payment->save();
                            return response()->json(['success' => true, 'message' => "Recharge validée", 'response' => $payment->user]);
                        }else{
                            return response()->json(['success' => false, 'message' => 'Transaction dejà validée'], 400);
                        }
                    }
                    elseif ($checkingRequest['success'] && $checkingRequest['status'] == 'pending'){
                        return response()->json(['success' => false, 'message' => 'Paiement en cours de validation', 'pending' => true]);
                    }
                    else{
                        return response()->json(['success' => false, 'message' => 'Paiement non validé']);
                    }
                }else{
                    return response()->json(['success' => false, 'message' => 'Paiement indisponible'], 404);
                }
            }else{
                return response()->json(['success' => false, 'message' => 'Token requis']);
            }
        }
    }
}
