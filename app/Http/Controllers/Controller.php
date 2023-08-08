<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use GuzzleHttp\Psr7\Request as GRequest;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client as GClient;
use StephaneAss\Payplus\Pay\PayPlus;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function fetchInvoiceStatus($invoiceToken) {
        $apiKey = env('PAYPLUS_API_KEY');
        $accessToken = env('PAYPLUS_TOKEN');
        $url = "https://app.payplus.africa/pay/v01/redirect/checkout-invoice/confirm/?invoiceToken={$invoiceToken}";
        $response = Http::withHeaders([
            'Apikey' => $apiKey,
            'Authorization' => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZF9hcHAiOiI5NzYiLCJpZF9hYm9ubmUiOjc4NTcsImRhdGVjcmVhdGlvbl9hcHAiOiIyMDIyLTA3LTE5IDExOjI2OjUwIn0.2USDGyfTAS-fchV5bimOShq95cjH_I2kKTWSDblQgCI"
        ])->get($url);
        #dd($response);
        if ($response->successful() && $response->header('content-type') === 'application/json') {
            $responseData = $response->json();
            if ($responseData['response_code'] === '00') {
                return [
                    'success' => true,
                    'status' => $responseData['status'],
                    'token' => $responseData['token'],
                    'response' => $responseData['response_text']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['response_text'],
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'La requête a échoué. Veuillez réessayer ultérieurement.',
            ];
        }
    }

    public function createPayplusInvoice(Request $request)
    {
        $co = (new PayPlus())->init();
        $co->addItem("Abonnement Sirat", 1, $request->amount, $request->amount, "test");
        $total_amount =  $request->amount;
        $co->setTotalAmount($total_amount);
        $co->setDescription("Abonnement Sirat");
        $phone = '229' . $request->phone;
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
}
