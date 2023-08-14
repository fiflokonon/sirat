<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use StephaneAss\Payplus\Pay\PayPlus;

class UserController extends Controller
{

    public function initUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unique_id' => [
                'required', 'string', 'max:255',
                Rule::unique('users')->where(function ($query) use ($request) {
                    return $query->where('unique_id', $request->unique_id)->where('id', '<>', $request->id);
                }),
            ],
            'phone' => 'nullable|string|max:255', // Ajout de la validation pour 'phone'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = $errors->first();
            return response()->json(['success' => false, 'message' => $errorMessage], 400);
        }

        try {
            $user = User::create([
                'unique_id' => $request->unique_id,
                'phone' => $request->phone, // Ajout de la sauvegarde du numéro de téléphone
            ]);
            $user = User::where('unique_id', $request->unique_id)->get();
            return response()->json(['success' => true, 'response' => $user]);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }


    public function infos(string $unique_id)
    {
        $user = User::where('unique_id', $unique_id)->first();
        if (!$user)
        {
            return response()->json(['success' => false, 'message' => 'Utilisateur indisponible'], 404);
        }
        else{
            return response()->json(['success' => true, 'response' => $user]);
        }
    }

    public function userPayments(string $unique_id)
    {
        $user = User::where('unique_id', $unique_id)->first();
        if (!$user)
        {
            return response()->json(['success' => false, 'message' => 'Utilisateur indisponible'], 404);
        }
        else{
            return response()->json(['success' => true, 'response' => $user->payments]);
        }
    }

    public function scan(string $unique_id, Request $request)
    {
        if ($request->user() == null)
        {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        else
        {
            $agent = $request->user();
            $user = User::where('unique_id', $unique_id)->first();
            if ($user)
            {
                if ($user->balance >= 100)
                {
                    $user->balance = $user->balance - 100;
                    Scan::create([
                        'agent_id' => $agent->id,
                        'scanned_id' => $user->id
                    ]);
                    $user->save();
                    return response()->json(['success' => true, 'message' => 'Débit effectué avec succès']);
                }else{
                    return response()->json(['success' => false, 'message' => 'Solde insuffisant']);
                }
            }else{
                return response()->json(['success' => false, 'message' => 'Utilisateur indisponible'], 404);
            }
        }
    }

    public function newScan(string $unique_id, Request $request)
    {
        if ($request->user() == null)
        {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        else
        {
            $agent = $request->user();
            $user = User::where('unique_id', $unique_id)->first();
            if ($user)
            {
                $paymentRequest = $this->lauchPayment($user->phone);
                if (!$paymentRequest['success']) {
                    return response()->json(['success' => false, 'message' => 'La requête de paiement a échoué']);
                }

                $payment = Payment::create([
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'amount' => 100,
                    'token' => $paymentRequest['token'],
                    'status' => 'pending'
                ]);
                return response()->json($paymentRequest);
            }else{
                return response()->json(['success' => false, 'message' => 'Utilisateur indisponible'], 404);
            }
        }
    }

    public function changePhone(string $unique_id, Request $request)
    {
        $validator = Validator::make(['unique_id' => $unique_id], [
            'unique_id' => [
                'required', 'string', 'max:255',
                Rule::exists('users', 'unique_id'),
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = $errors->first();
            return response()->json(['success' => false, 'message' => $errorMessage], 400);
        }

        try {
            $user = User::where('unique_id', $unique_id)->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
            }

            $user->phone = $request->phone;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Numéro de téléphone mis à jour avec succès']);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }


    public function lauchPayment(string $phone)
    {
        $co = (new PayPlus())->init();
        $co->addItem("Abonnement Sirat", 1, 100, 100, "test");
        $total_amount =  100;
        $co->setTotalAmount($total_amount);
        $co->setDescription("Abonnement Sirat");
        $phone = '229' . $phone;
        $co->setCustomerNumber($phone); // It must be on this format 22967710659
        $co->setDevise("xof"); // By defaut, it is already on xof
        $co->setOtp(""); // Contains the otp code of the transaction (only for orange money subscribers, otherwise leave empty).
        $result = $co->launchPaiement();
        $responseData = $result;
        if ($responseData->response_code === '00') {
            return [
                'success' => true,
                'token' => $responseData->token,
                'redirect_url' => $responseData->response_text,
                'description' => $responseData->description,
            ];
        } else {
            return [
                'success' => false,
                'message' => $responseData->description,
            ];
        }
    }
    public function scans(Request $request)
    {
        if ($request->user() == null)
        {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        else
        {
            $agent = $request->user();
            $scannedUsers = $agent->scannedBy;
            if ($scannedUsers){
                return response()->json(['success' => true, 'response' => $scannedUsers]);
            }
            else{
                return response()->json(['success' => false, 'message' => 'Pas de scan'], 404);
            }
        }
    }
}
