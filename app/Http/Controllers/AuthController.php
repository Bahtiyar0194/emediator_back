<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Config;
use Validator;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Models\User;
use App\Models\UserRole;
use App\Models\Language;

class AuthController extends Controller
{
    public function __construct(Request $request){
        app()->setLocale($request->header('Accept-Language'));
    }

    public function get_token(Request $request){
        $api_url = Config::get('constants.sigex_api').'/auth';
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $response = $client->post($api_url,
            ['body' => '{}']
        )->getBody()->getContents();

        $response = json_decode($response);

        return response()->json($response, 200);
    }

    public function get_qr(Request $request){
        $api_url = Config::get('constants.sigex_api').'/egovQr';
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $response = $client->post($api_url,
            ['body' => '{
                "description": "Аутентификация"
            }']
        )->getBody()->getContents();

        $response = json_decode($response);

        $qrId = str_replace('https://sigex.kz/api/egovQr/', '', $response->signURL);

        //Generates a QrCode with an image centered in the middle.  The inserted image takes up 30% of the QrCode.
        $qrCode = base64_encode(QrCode::format('png')->size(400)->merge('https://i.pinimg.com/originals/5b/2d/bb/5b2dbbc4c2f3b7db7cad60cd89997e30.png', .2, true)->generate('mobileSign:'.Config::get('constants.sigex_api').'/egovQr/egov/'.$qrId));

        $response->qrCode = $qrCode;

        return response()->json($response, 200);
    }

    public function login(Request $request){
        $language = Language::where('lang_tag', '=', $request->lang)->first();

        $validator = Validator::make($request->all(), [
            'sigex' => 'required',
            'lang' => 'required',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $api_url = Config::get('constants.sigex_api').'/auth';
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $response = $client->post($api_url, 
            [
                'body' => json_encode($request->sigex)
            ]
        )->getBody()->getContents();

        $response = json_decode($response);

        if($response->userId){

            $find_iin = User::where('iin', '=', str_replace("IIN", "", $response->userId))
            ->first();

            if(!isset($find_iin)){
                $new_user = new User();

                $subject = explode(",", $response->subject);

                foreach($subject as $subject_item){
                    if(strpos($subject_item, 'CN=') !== false){
                        $cn = explode(' ', str_replace('CN=', '', $subject_item));

                        $new_user->first_name = $cn[1];
                        $new_user->last_name = $cn[0];
                    }  

                    if(strpos($subject_item, 'GIVENNAME=') !== false){
                        $new_user->given_name = str_replace('GIVENNAME=', '', $subject_item);
                    }  
                }

                $new_user->iin = str_replace("IIN", "", $response->userId);
                $new_user->lang_id = $language->lang_id;
                $new_user->save();

                $new_user_role = new UserRole();
                $new_user_role->user_id = $new_user->user_id;
                $new_user_role->role_type_id = 1;
                $new_user_role->save();

                return response()->json(['token' => $new_user->createToken('API Token')->plainTextToken], 200);
            }
            else{

                Auth::login($find_iin);

                if(auth()->user()->status_type_id == 2){
                    return response()->json(['auth_failed' => trans('auth.failed')], 401);
                }

                return response()->json(['token' => auth()->user()->createToken('API Token')->plainTextToken], 200);
            }
        }
    }

    public function me(Request $request){
        $user = auth()->user();

        $language = Language::where('lang_tag', '=', $request->header('Accept-Language'))->first();

        // $roles = UserRole::leftJoin('types_of_user_roles', 'users_roles.role_type_id', '=', 'types_of_user_roles.role_type_id')
        // ->leftJoin('types_of_user_roles_lang', 'types_of_user_roles.role_type_id', '=', 'types_of_user_roles_lang.role_type_id')
        // ->where('users_roles.user_id', $user->user_id)
        // ->where('types_of_user_roles_lang.lang_id', $language->lang_id)
        // ->select(
        //     'users_roles.role_type_id',
        //     'types_of_user_roles.role_type_slug',
        //     'types_of_user_roles_lang.user_role_type_name'
        // )
        // ->get();

        // foreach ($roles as $key => $role) {
        //     if($role->role_type_id == $user->current_role_id){
        //         $user->current_role_name = $role->user_role_type_name;
        //         break;
        //     }
        // }

        // $user->roles = $roles;

        return response()->json($user, 200);
    }

    public function logout(){
        auth()->user()->tokens()->delete();
        return response()->json('Logout successful', 200);
    }
}