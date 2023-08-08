<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = $errors->first();
            return response()->json(['success' => false, 'message' => $errorMessage], 400);
        }

        try {
            $user = User::create([
                'unique_id' => $request->unique_id,
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
                if ($user->balance < 100)
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
