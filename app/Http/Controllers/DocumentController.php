<?php

namespace App\Http\Controllers;

use App\Models\Language;

use App\Models\Agreement;
use App\Models\AgreementParty;

use App\Models\Mediator;
use App\Models\MediationContract;
use App\Models\MediationContractParty;

use File;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Response;


class DocumentController extends Controller
{
    public function get_document(Request $request){

        $agreement = Agreement::where('uuid', $request->uuid)
        ->first();

        if(isset($agreement)){
            $agreement_parties = AgreementParty::leftJoin('users', 'agreement_parties.user_id', '=', 'users.user_id')
            ->select(
                'users.first_name',
                'users.last_name',
                'users.given_name',
                'users.iin',
                'users.data',
                'agreement_parties.is_mediator',
                'agreement_parties.sigex_sign_id',
                'agreement_parties.signed_at',
                'agreement_parties.attorney_data'
            )
            ->where('agreement_parties.agreement_id', $agreement->agreement_id)
            ->orderBy('id', 'asc')
            ->get();

            foreach ($agreement_parties as $key => $party) {
                if(isset($party->data)){
                    $party->data = json_decode(Crypt::decryptString($party->data));

                    if(isset($party->data->attorney)){
                        unset($party->data->attorney);
                    }

                    if(isset($party->attorney_data)){
                        $party->data->attorney = json_decode(Crypt::decryptString($party->attorney_data));
                        unset($party->attorney_data);
                    }
                }

                if($party->is_mediator === 1){
                    $agreement->mediator_id = $party->user_id;
                }
            }

            return response()->json([
                'parties' => $agreement_parties,
                'type' => 'agreement'
            ], 200); 
        }

        $contract = MediationContract::where('uuid', '=', $request->uuid)
        ->first();

        if(isset($contract)){
            $contract_parties = MediationContractParty::leftJoin('users', 'mediation_contract_parties.user_id', '=', 'users.user_id')
            ->select(
                'users.first_name',
                'users.last_name',
                'users.given_name',
                'users.iin',
                'users.data',
                'mediation_contract_parties.is_mediator',
                'mediation_contract_parties.sigex_sign_id',
                'mediation_contract_parties.signed_at',
                'mediation_contract_parties.attorney_data'
            )
            ->where('mediation_contract_parties.mediation_contract_id', $contract->mediation_contract_id)
            ->orderBy('mediation_contract_parties.id', 'asc')
            ->get();

            foreach ($contract_parties as $key => $party) {
                if(isset($party->data)){
                    $party->data = json_decode(Crypt::decryptString($party->data));

                    if(isset($party->data->attorney)){
                        unset($party->data->attorney);
                    }

                    if(isset($party->attorney_data)){
                        $party->data->attorney = json_decode(Crypt::decryptString($party->attorney_data));
                        unset($party->attorney_data);
                    }
                }

                if($party->is_mediator === 1){
                    $mediator = Mediator::where('user_id', $party->user_id)
                    ->first();

                    $party->mediator = $mediator;
                }
            }

            return response()->json([
                'parties' => $contract_parties,
                'type' => 'contract'
            ], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'Document not found'], 404);
    }

    public function get_file(Request $request){
        $authUser = null;

        $token = $request->bearerToken();

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken) {
                $authUser = $accessToken->tokenable;
            }
        }

        $isAuthorized = false;

        $agreement = Agreement::where('uuid', $request->uuid)
        ->first();

        $contract = MediationContract::where('uuid', '=', $request->uuid)
        ->first();

        // Вариант 1: пользователь авторизован по токену
        if ($authUser) {
            $isAuthorized = true;

            if(isset($agreement)){
                $isParty = AgreementParty::where('agreement_id', $agreement->agreement_id)
                ->where(function ($query) use ($authUser) {
                    $query->where('user_id', $authUser->user_id)
                    ->orWhere('representative_id', $authUser->user_id);
                })
                ->first();

                if(!isset($isParty)){
                    return response()->json([
                        'message' => 'Access denied'
                    ], 403);
                }
            }

            if(isset($contract)){
                $isParty = MediationContractParty::where('mediation_contract_id', $contract->mediation_contract_id)
                ->where(function ($query) use ($authUser) {
                    $query->where('user_id', $authUser->user_id)
                    ->orWhere('representative_id', $authUser->user_id);
                })
                ->first();

                if(!isset($isParty)){
                    return response()->json([
                        'message' => 'Access denied'
                    ], 403);
                }
            }
        }

        // Вариант 2: пользователь ввёл последние 4 цифры телефона
        if (!$isAuthorized && $request->filled('phone')) {
            $partiesCollection = collect();

            if ($request->document === 'agreement' && isset($agreement)) {
                $partiesCollection = AgreementParty::leftJoin('users', 'agreement_parties.user_id', '=', 'users.user_id')
                    ->select('users.data', 'agreement_parties.attorney_data')
                    ->where('agreement_parties.agreement_id', $agreement->agreement_id)
                    ->orderBy('agreement_parties.id', 'asc')
                    ->get();
            } elseif ($request->document === 'contract' && isset($contract)) {
                $partiesCollection = MediationContractParty::leftJoin('users', 'mediation_contract_parties.user_id', '=', 'users.user_id')
                    ->select('users.data', 'mediation_contract_parties.attorney_data')
                    ->where('mediation_contract_parties.mediation_contract_id', $contract->mediation_contract_id)
                    ->orderBy('mediation_contract_parties.id', 'asc')
                    ->get();
            }

            foreach ($partiesCollection as $party) {
                $targetPhone = null;

                // 1. Проверяем доверенность в первую очередь
                if (!empty($party->attorney_data)) {
                    $attorney = json_decode(Crypt::decryptString($party->attorney_data));
                    
                    // Если флаг включает доверенность, берём телефон представителя
                    if (isset($attorney->includes) && $attorney->includes === true) {
                        $targetPhone = $attorney->person->data->phone ?? null;
                    }
                }

                // 2. Если доверенности нет или она не активна, берём телефон основного пользователя
                if (!$targetPhone && !empty($party->data)) {
                    $userData = json_decode(Crypt::decryptString($party->data));
                    $targetPhone = $userData->phone ?? null;
                }

                // 3. Если телефон не найден — пропускаем эту сторону
                if (empty($targetPhone)) {
                    continue;
                }

                // Очищаем телефон от лишних символов
                $cleanPhone = preg_replace('/\D/', '', $targetPhone);

                // 4. Проверяем совпадение последних 4 цифр
                if (
                    preg_match('/^\d{4}$/', $request->phone) &&
                    substr($cleanPhone, -4) === $request->phone
                ) {
                    $isAuthorized = true;
                    break;
                }
            }

            if (!$isAuthorized) {
                return response()->json([
                    'message' => 'Access denied'
                ], 403);
            }
        }

        if ($isAuthorized === true) {
            if($request->document === 'agreement'){
                $document_path = 'app/public/agreements';
            }
            elseif($request->document === 'contract'){
                $document_path = 'app/public/agreements/contracts';
            }

            $path = storage_path($document_path.'/'.$request->type.'/' . $request->uuid . '.pdf');
            
            if (!File::exists($path)) {
                return response()->json(['status' => 'error', 'message' => 'File not found'], 404);
            }

            $type = File::mimeType($path);

            $file = File::get($path);
            $response = Response::make($file, 200);

            if($request->type === 'original'){
                $base64 = base64_encode($file);
                $mime = File::mimeType($path);

                return response()->json([
                    'data' => $base64,
                    'mime' => $mime
                ], 200);
            }

            $response->header("Content-Type", $type);
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
            return $response;
        }
        else{
            return response()->json([
                'message' => 'Unauthorized',
                'phone' => 'required'
            ], 401);
        }
    }
}