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
                'agreement_parties.is_mediator',
                'agreement_parties.sigex_sign_id',
                'agreement_parties.signed_at'
            )
            ->where('agreement_parties.agreement_id', $agreement->agreement_id)
            ->orderBy('id', 'asc')
            ->get();

            foreach ($agreement_parties as $key => $party) {
                if(isset($party->data)){
                    $party->data = json_decode(Crypt::decryptString($party->data));
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
                'mediation_contract_parties.is_mediator',
                'mediation_contract_parties.sigex_sign_id',
                'mediation_contract_parties.signed_at'
            )
            ->where('mediation_contract_parties.mediation_contract_id', $contract->mediation_contract_id)
            ->orderBy('mediation_contract_parties.id', 'asc')
            ->get();

            foreach ($contract_parties as $key => $party) {
                if(isset($party->data)){
                    $party->data = json_decode(Crypt::decryptString($party->data));
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

        // Вариант 1: пользователь авторизован по токену
        if ($authUser) {
            $isAuthorized = true;
        }

        // Вариант 2: пользователь ввёл последние 4 цифры телефона
        if (!$isAuthorized && $request->filled('phone')) {
            if($request->document === 'agreement'){
                $agreement = Agreement::where('uuid', $request->uuid)
                ->first();

                if(isset($agreement)){
                    $agreement_parties = AgreementParty::leftJoin('users', 'agreement_parties.user_id', '=', 'users.user_id')
                    ->select(
                        'users.data'
                    )
                    ->where('agreement_parties.agreement_id', $agreement->agreement_id)
                    ->orderBy('id', 'asc')
                    ->get();

                    foreach ($agreement_parties as $key => $party) {
                        if (!$party->data) {
                            continue;
                        }

                        $data = json_decode(Crypt::decryptString($party->data));

                        if (empty($data->phone)) {
                            continue;
                        }

                        $phone = preg_replace('/\D/', '', $data->phone);

                        if (
                            preg_match('/^\d{4}$/', $request->phone) &&
                            substr($phone, -4) === $request->phone
                        ) {
                            $isAuthorized = true;
                            break;
                        }
                    }
                }
            }
            elseif($request->document === 'contract'){
                $contract = MediationContract::where('uuid', '=', $request->uuid)
                ->first();

                if(isset($contract)){
                    $contract_parties = MediationContractParty::leftJoin('users', 'mediation_contract_parties.user_id', '=', 'users.user_id')
                    ->select(
                        'users.data'
                    )
                    ->where('mediation_contract_parties.mediation_contract_id', $contract->mediation_contract_id)
                    ->orderBy('mediation_contract_parties.id', 'asc')
                    ->get();

                    foreach ($contract_parties as $key => $party) {
                        if (!$party->data) {
                            continue;
                        }

                        $data = json_decode(Crypt::decryptString($party->data));

                        if (empty($data->phone)) {
                            continue;
                        }

                        $phone = preg_replace('/\D/', '', $data->phone);

                        if (
                            preg_match('/^\d{4}$/', $request->phone) &&
                            substr($phone, -4) === $request->phone
                        ) {
                            $isAuthorized = true;
                            break;
                        }
                    }
                }
            }

            if(!$isAuthorized){
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