<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Config;
use Validator;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

use App\Models\User;
use App\Models\UserRole;
use App\Models\Language;
use App\Models\TempAuthToken;

class AuthController extends Controller
{
    protected $locationService;

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

        $response = $client->post($api_url, [
            'body' => json_encode([
                'description' => 'Authentication Emediator.kz',
                // 'whenDone' => [
                //     'backUrl' => env('FRONTEND_URL') . '/auth'
                // ]
            ]),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ])->getBody()->getContents();

        $response = json_decode($response);

        $qrId = str_replace('https://sigex.kz/api/egovQr/', '', $response->signURL);

        //Generates a QrCode with an image centered in the middle.  The inserted image takes up 30% of the QrCode.
        //$qrCode = base64_encode(QrCode::format('png')->size(400)->merge('https://i.pinimg.com/originals/5b/2d/bb/5b2dbbc4c2f3b7db7cad60cd89997e30.png', .2, true)->generate('mobileSign:'.Config::get('constants.sigex_api').'/egovQr/egov/'.$qrId));
        $qrCode = base64_encode(QrCode::format('png')->size(400)->generate('mobileSign:'.Config::get('constants.sigex_api').'/egovQr/egov/'.$qrId));

        $response->qrCode = $qrCode;

        return response()->json($response, 200);
    }

    public function send_qr(Request $request)
    {
        // 1. Валидация входных данных
        $validated = $request->validate([
            'url'  => 'required|url',
            'data' => 'required|string',
        ]);

        try {
            // 2. Отправка POST запроса через HTTP Client Laravel
            $response = Http::acceptJson()->post($validated['url'], [
                'signMethod'      => 'CMS_SIGN_ONLY',
                'documentsToSign' => [
                    [
                        'id'     => 1,
                        'nameEn' => 'Authentication on portal Emediator.kz',
                        'nameRu' => 'Вход в личный кабинет на портале Emediator.kz',
                        'nameKz' => 'Emediator.kz порталындағы жеке кабинетке өту',
                        'document' => [
                            'file' => [
                                'data' => $validated['data'],
                                'mime' => '@file/pdf',
                            ],
                        ],
                    ],
                ],
            ]);

            info('POST', [
                'nonce' => $validated['data'],
                'url' => $validated['url']
            ]);

            // 3. Проверка успешности ответа и наличия signURL
            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['signURL'])) {
                    return response()->json([
                        'status' => 'success',
                        'url' => $responseData['signURL']
                    ], 200);
                }

                return response()->json([
                    'code' => 4558,
                    'message' => json_encode($responseData),
                ], 400);
            }

            // Ошибка на стороне внешнего сервиса (4xx / 5xx)
            return response()->json([
                'code' => 4562,
                'message' => $response->json('message') ?? $response->body(),
            ], $response->status());

        } catch (\Exception $e) {
            // Ошибка соединения / таймаут
            return response()->json([
                'code' => 4560,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function sign_qr(Request $request)
    {
        // 1. Валидация входных данных
        $validated = $request->validate([
            'url'  => 'required|url',
            'nonce' => 'required|string', // nonce
            'lang' => 'required|string'
        ]);

        $temp_token = TempAuthToken::where('data', '=', $validated['nonce'])
        ->first();

        if(isset($temp_token)){
            return response()->json([
                'token' => $temp_token->token
            ], 200);
        }

        try {
            // Выполняем GET-запрос к signURL
            $response = Http::acceptJson()->get($validated['url']);

            info('GET', [
                'nonce' => $validated['nonce'],
                'url' => $validated['url']
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Извлекаем signed data используя опциональное связывание (data_get)
                $signData = data_get($data, 'documentsToSign.0.document.file.data');

                if ($signData) {
                    $sigex = [
                        'nonce'     => $validated['nonce'],
                        'signature' => $signData,
                        'external'  => true,
                    ];

                    return $this->processSigexLogin($sigex, $validated['lang'], true);
                }
            }

            return response()->json([
                'message' => $response->json('message') ?? $response->body(),
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'code' => 4561,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sigex' => 'required',
            'lang'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->processSigexLogin($request->sigex, $request->lang, false);
    }

    public function me(Request $request){
        $user = auth()->user();

        if(isset($user)){
            if(isset($user->data)){
                $user->data = json_decode(Crypt::decryptString($user->data));
            }
        }

        $language = Language::where('lang_id', '=', $user->lang_id)->first();

        $roles = UserRole::leftJoin('types_of_user_roles', 'users_roles.role_type_id', '=', 'types_of_user_roles.role_type_id')
            ->leftJoin('types_of_user_roles_lang', 'types_of_user_roles.role_type_id', '=', 'types_of_user_roles_lang.role_type_id')
            ->where('users_roles.user_id', '=', $user->user_id)
            ->where('types_of_user_roles_lang.lang_id', '=', $language->lang_id)
            ->select(
                'users_roles.role_type_id',
                'types_of_user_roles.role_type_slug',
                'types_of_user_roles_lang.user_role_type_name'
            )
            ->get();

        foreach ($roles as $role) {
            if ($role->role_type_id == $user->current_role_id) {
                $user->current_role_name = $role->user_role_type_name;
                break;
            }
        }

        $user->roles = $roles;

        return response()->json($user, 200);
    }

    public function change_mode(Request $request)
    {
        $user = auth()->user();
        $role_found = false;

        $roles = UserRole::where('user_id', $user->user_id)
            ->select('role_type_id')->get();

        foreach ($roles as $value) {
            if ($value->role_type_id == $request->role_type_id) {
                $role_found = true;
                break;
            }
        }

        if ($role_found === true) {
            $change_user = User::find($user->user_id);
            $change_user->current_role_id = $request->role_type_id;
            $change_user->save();

            return response()->json('User mode change successful', 200);
        } else {
            return response()->json('Access denied', 403);
        }
    }

    public function change_language(Request $request)
    {
        $user = auth()->user();

        $language = Language::where('lang_tag', '=', $request->lang_tag)->first();

        $findUser = User::find($user->user_id);
        $findUser->lang_id = $language->lang_id;
        $findUser->save();

        return response()->json('User language change successful', 200);
    }

    public function logout(){
        auth()->user()->tokens()->delete();
        return response()->json('Logout successful', 200);
    }

    // Общая чистая логика авторизации Sigex (принимает обычные массивы/строки)
    private function processSigexLogin(array $sigexData, string $lang, $temp_token)
    {
        $language = Language::where('lang_tag', '=', $lang)->first();
        $apiUrl = Config::get('constants.sigex_api') . '/auth';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($apiUrl, $sigexData);
        

        if (!$response->successful()) {
            return response()->json([
                'code' => 4556,
                'message' => $response->json()
            ], 400);
        }

        $responseData = $response->object();

        if (!isset($responseData->userId)) {
            return response()->json([
                'code' => 4557,
                'message' => $response->json(),
            ], 400);
        }

        $cleanIin = str_replace("IIN", "", $responseData->userId);
        $user = User::where('iin', '=', $cleanIin)->first();

        if (isset($responseData->subject)) {
            $subject = explode(",", $responseData->subject);

            if (! $user) {
                $user = new User();
                $this->parseSubjectToUser($subject, $user);

                $user->iin = $cleanIin;
                if (isset($responseData->businessId)) {
                    $user->bin = str_replace("BIN", "", $responseData->businessId);
                }
                $user->lang_id = $language->lang_id ?? 1;
                $user->save();

                $newUserRole = new UserRole();
                $newUserRole->user_id = $user->user_id;
                $newUserRole->role_type_id = 4;
                $newUserRole->save();

                $token = $user->createToken('API Token')->plainTextToken;

                if($temp_token === true){
                    $newTempAuthToken = new TempAuthToken();
                    $newTempAuthToken->data = $sigexData['nonce'];
                    $newTempAuthToken->token = $token;
                    $newTempAuthToken->save();
                }

                return response()->json([
                    'token' => $token
                ], 200);
            } else {
                $this->parseSubjectToUser($subject, $user);

                if (isset($responseData->businessId)) {
                    $user->bin = str_replace("BIN", "", $responseData->businessId);
                }
                $user->save();

                Auth::login($user);

                if (auth()->user()->status_type_id == 2) {
                    return response()->json(['auth_failed' => trans('auth.failed')], 401);
                }

                $token = auth()->user()->createToken('API Token')->plainTextToken;

                if($temp_token === true){
                    $newTempAuthToken = new TempAuthToken();
                    $newTempAuthToken->data = $sigexData['nonce'];
                    $newTempAuthToken->token = $token;
                    $newTempAuthToken->save();
                }

                return response()->json([
                    'token' => $token
                ], 200);
            }
        }

        return response()->json([
            'code' => 4559,
            'message' => 'Subject not found'
        ], 400);
    }

    // Вспомогательный метод парсинга CN и GIVENNAME
    private function parseSubjectToUser(array $subject, User $user): void
    {
        foreach ($subject as $item) {
            if (strpos($item, 'CN=') !== false) {
                $cn = explode(' ', str_replace('CN=', '', $item));
                $user->first_name = mb_ucwords($cn[1] ?? '');
                $user->last_name = mb_ucwords($cn[0] ?? '');
            }

            if (strpos($item, 'GIVENNAME=') !== false) {
                $user->given_name = mb_ucwords(str_replace('GIVENNAME=', '', $item));
            }
        }
    }
}