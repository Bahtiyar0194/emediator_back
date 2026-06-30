<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;

use App\Models\User;
use App\Models\Mediator;
use App\Models\UserRole;
use App\Models\Language;

use Illuminate\Http\Request;
use DB;
use Validator;

use App\Services\LocationService;

class UserController extends Controller
{
    public function __construct(LocationService $locationService){
        $this->locationService = $locationService;
    }

    public function get_user_attributes(Request $request)
    {
        $language = Language::where('lang_tag', '=', $request->lang)->first();

        // Получаем статусы пользователя
        $statuses = DB::table('users')
            ->leftJoin('types_of_status', 'users.status_type_id', '=', 'types_of_status.status_type_id')
            ->leftJoin('types_of_status_lang', 'types_of_status.status_type_id', '=', 'types_of_status_lang.status_type_id')
            ->where('types_of_status_lang.lang_id', '=', $language->lang_id)
            ->select(
                'users.status_type_id',
                'types_of_status_lang.status_type_name'
            )
            ->groupBy('users.status_type_id', 'types_of_status_lang.status_type_name')
            ->get();

        // Формируем запрос для получения ролей
        $roles = DB::table('types_of_user_roles')
            ->leftJoin('types_of_user_roles_lang', 'types_of_user_roles.role_type_id', '=', 'types_of_user_roles_lang.role_type_id')
            ->where('types_of_user_roles_lang.lang_id', '=', $language->lang_id)
            ->select(
                'types_of_user_roles.role_type_id',
                'types_of_user_roles_lang.user_role_type_name'
            );

        // Получаем список ролей
        $rolesList = $roles->get();

        $locations = $this->locationService->get_locations($language);

        // Создаем объект для возвращаемых данных
        $attributes = new \stdClass();
        $attributes->user_statuses = $statuses;
        $attributes->user_roles = $rolesList;
        $attributes->locations = $locations;

        // Возвращаем данные в JSON-формате
        return response()->json($attributes, 200);
    }

    public function get_by_iin(Request $request){
        $iin = trim((string) $request->iin);

        $user = User::select(
            'iin',
            'last_name',
            'first_name',
            'given_name',
            'data'
        )
        ->where('iin', '=', $iin)
        ->first();

        if(isset($user)){
            if(isset($user->data)){
                $user->data = json_decode(Crypt::decryptString($user->data));
            }
        }

        return response()->json([
            'user' => $user
        ], 200);
    }

    public function get(Request $request){
        
        $language = Language::where('lang_tag', '=', $request->lang)->first();
        // Получаем параметры лимита на страницу
        $per_page = $request->per_page ? $request->per_page : 10;

        // Получаем параметры сортировки
        $sortKey = $request->input('sort_key', 'created_at');  // Поле для сортировки по умолчанию
        $sortDirection = $request->input('sort_direction', 'asc');  // Направление по умолчанию

        $users = User::leftJoin('users_roles', 'users.user_id', '=', 'users_roles.user_id')
            ->leftJoin('types_of_user_roles', 'users_roles.role_type_id', '=', 'types_of_user_roles.role_type_id')
            ->leftJoin('types_of_user_roles_lang', 'types_of_user_roles.role_type_id', '=', 'types_of_user_roles_lang.role_type_id')
            ->leftJoin('types_of_status', 'users.status_type_id', '=', 'types_of_status.status_type_id')
            ->leftJoin('types_of_status_lang', 'types_of_status.status_type_id', '=', 'types_of_status_lang.status_type_id')
            ->where('types_of_status_lang.lang_id', '=', $language->lang_id)
            ->select(
                'users.user_id',
                'users.first_name',
                'users.last_name',
                'users.iin',
                'users.email',
                'users.phone',
                'users.avatar',
                'users.created_at',
                'types_of_status_lang.status_type_name',
                'types_of_status.color as status_color',
                DB::raw('GROUP_CONCAT(DISTINCT types_of_user_roles_lang.user_role_type_name) as roles')
            )
            ->groupBy(
                'users.user_id',
                'users.first_name',
                'users.last_name',
                'users.iin',
                'users.email',
                'users.phone',
                'users.avatar',
                'users.created_at',
                'types_of_status_lang.status_type_name',
                'types_of_status.color'
            )
            ->orderBy($sortKey, $sortDirection);

        // Применяем фильтрацию по параметрам из запроса
        $user_fio = $request->user;
        $iin = $request->iin;
        $email = $request->email;
        $phone = $request->phone;
        $statuses_id = $request->statuses;
        $roles_id = $request->roles;
        $created_at_from = $request->created_at_from;
        $created_at_to = $request->created_at_to;

        // Фильтрация по ФИО пользователя
        if (!empty($user_fio)) {
            $users->whereRaw("CONCAT(users.last_name, ' ', users.first_name) LIKE ?", ['%' . $user_fio . '%']);
        }

        // Фильтрация по ИИН
        if (!empty($iin)) {
            $users->where('users.iin', 'LIKE', '%' . $iin . '%');
        }

        // Фильтрация по email
        if (!empty($email)) {
            $users->where('users.email', 'LIKE', '%' . $email . '%');
        }

        // Фильтрация по телефону
        if (!empty($phone)) {
            $users->where('users.phone', 'LIKE', '%' . $phone . '%');
        }

        // Фильтрация по статусу
        if (!empty($statuses_id)) {
            $users->whereIn('users.status_type_id', $statuses_id);
        }

        // Фильтрация по роли
        if (!empty($roles_id)) {
            $users->whereIn('users_roles.role_type_id', $roles_id);
        }

        // Фильтрация по дате создания
        if ($created_at_from && $created_at_to) {
            $users->whereBetween('users.created_at', [$created_at_from . ' 00:00:00', $created_at_to . ' 23:59:59']);
        } elseif ($created_at_from) {
            $users->where('users.created_at', '>=', $created_at_from . ' 00:00:00');
        } elseif ($created_at_to) {
            $users->where('users.created_at', '<=', $created_at_to . ' 23:59:59');
        }
        
        return response()->json($users->paginate($per_page)->onEachSide(1), 200);
    }

    public function get_user(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        $language = Language::where('lang_tag', '=', $request->lang)->first();

        // Формируем запрос на получение ролей
        $rolesQuery = DB::table('types_of_user_roles')
            ->leftJoin('types_of_user_roles_lang', 'types_of_user_roles.role_type_id', '=', 'types_of_user_roles_lang.role_type_id')
            ->where('types_of_user_roles_lang.lang_id', '=', $language->lang_id)
            ->select(
                'types_of_user_roles.role_type_id',
                'types_of_user_roles.role_type_slug',
                'types_of_user_roles_lang.user_role_type_name'
            );

        // Выполняем запрос на получение списка ролей
        $rolesList = $rolesQuery->get();

        $available_roles = [];

        // Присваиваем флаг "selected" в зависимости от наличия роли у пользователя
        foreach ($rolesList as $role) {
            $find_user_role = UserRole::where('role_type_id', '=', $role->role_type_id)
                ->where('user_id', '=', $user->user_id)
                ->first();

            if (isset($find_user_role)) {
                $role->selected = true;
                array_push($available_roles, $role);
            } else {
                $role->selected = false;
            }
        }

        // Присваиваем имя текущей роли пользователя
        foreach ($rolesList as $role) {
            if ($role->role_type_id == $user->current_role_id) {
                $user->current_role_name = $role->user_role_type_name;
                break;
            }
        }

        $is_mediator = Mediator::where('user_id', $user->user_id)
        ->first();

        if(isset($is_mediator)){
            $user->mediator = $is_mediator;
        }

        // Добавляем список ролей в ответ
        $user->roles = $rolesList;
        $user->available_roles = $available_roles;

        if(isset($user->data)){
            $user->data = json_decode(Crypt::decryptString($user->data));
        }

        return response()->json($user, 200);
    }

    public function update_user(Request $request)
    {
        $language = Language::where('lang_tag', '=', $request->lang)->firstOrFail();

        app()->setLocale($language->lang_tag);

        $rules = [
            'first_name' => 'required|string|between:2,100',
            'last_name' => 'required|string|between:2,100',
            'data.email' => 'required|string|email|max:100',
            'data.phone' => 'required|regex:/^((?!_).)*$/s',
            'new_roles' => 'required|array',
            'data.location.id' => 'required|numeric',
            'data.location.village' => 'nullable|required_if:data.location.is_district,true|string',
            'mediator.association_name_short' => 'nullable|required_if:mediator_is_selected,true|between:2,200',
            'mediator.association_name_full' => 'nullable|required_if:mediator_is_selected,true|between:2,200',
            'mediator.cert_num' => 'nullable|required_if:mediator_is_selected,true|between:2,200',
            'mediator.cert_date' => 'nullable|required_if:mediator_is_selected,true|between:2,200',
        ];

        $new_roles = [];

        foreach ($request->new_roles as $key => $role) {
            if($role['selected'] === true){
                array_push($new_roles, $role);
            }
        }

        if(count($new_roles) === 0){
            $rules["roles_count"] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::findOrFail($request->user_id);

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->current_role_id = $new_roles[0]['role_type_id'];
        $user->data = Crypt::encryptString(json_encode($request['data']));
        $user->save();

        UserRole::where('user_id', $user->user_id)
        ->delete();

        Mediator::where('user_id', $user->user_id)
        ->delete();

        foreach ($new_roles as $value) {
            $user_role = new UserRole();
            $user_role->user_id = $user->user_id;
            $user_role->role_type_id = $value['role_type_id'];
            $user_role->save();
        }

        if($request->mediator_is_selected === true){
            $new_mediator = new Mediator();
            $new_mediator->user_id = $user->user_id;
            $new_mediator->association_name_short = $request['mediator']['association_name_short'];
            $new_mediator->association_name_full = $request['mediator']['association_name_full'];
            $new_mediator->cert_num = $request['mediator']['cert_num'];
            $new_mediator->cert_date = $request['mediator']['cert_date'];
            $new_mediator->save();
        }

        return response()->json($user, 200);
    }
}
