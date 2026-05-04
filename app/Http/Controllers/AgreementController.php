<?php

namespace App\Http\Controllers;
use App\Models\LegalFormType;
use App\Models\OrganizationPostType;
use App\Models\AgreementType;
use App\Models\Agreement;
use App\Models\Location;
use App\Models\Language;
use App\Models\Bank;
use App\Models\Color;
use App\Models\User;
use App\Models\AgreementParty;
use App\Models\AgreementTypicalPoint;
use App\Models\Mediator;
use App\Models\CustomAgreementTemplate;

use App\Models\MediationContract;
use App\Models\MediationContractParty;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Crypt;
use Storage;
use File;
use Config;
use Validator;
use Log;
use Str;
use Carbon\Carbon;

use App\Services\LocationService;
use App\Services\AgreementService;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AgreementController extends Controller
{
    public function __construct(Request $request, LocationService $locationService, AgreementService $agreementService){
        $this->locationService = $locationService;
        $this->agreementService = $agreementService;
    }

    public function get_attributes(Request $request){
        $language = Language::where('lang_tag', '=', $request->header('Accept-Language'))->first();

        $legal_forms = LegalFormType::leftJoin('types_of_legal_forms_lang', 'types_of_legal_forms.legal_form_id', '=', 'types_of_legal_forms_lang.legal_form_id')
        ->select(
            'types_of_legal_forms.legal_form_id',
            'types_of_legal_forms_lang.legal_form_name'
        )
        ->where('types_of_legal_forms_lang.lang_id', '=', $language->lang_id)
        ->get();

        $posts = OrganizationPostType::leftJoin('types_of_organization_posts_lang', 'types_of_organization_posts.post_type_id', '=', 'types_of_organization_posts_lang.post_type_id')
        ->select(
            'types_of_organization_posts.post_type_id',
            'types_of_organization_posts_lang.post_type_name'
        )
        ->where('types_of_organization_posts_lang.lang_id', '=', $language->lang_id)
        ->get();


        $colors = Color::leftJoin('colors_lang', 'colors.color_id', '=', 'colors_lang.color_id')
        ->select(
            'colors.color_id',
            'colors.color_class',
            'colors_lang.color_name'
        )
        ->where('colors_lang.lang_id', '=', $language->lang_id)
        ->orderBy('colors_lang.color_name', 'asc')
        ->get();

        $banks = Bank::leftJoin('banks_lang', 'banks.bank_id', '=', 'banks_lang.bank_id')
        ->select(
            'banks.bank_id',
            'banks_lang.bank_name'
        )
        ->where('banks_lang.lang_id', '=', $language->lang_id)
        ->orderBy('banks_lang.bank_name', 'asc')
        ->get();

        $mediators = Mediator::leftJoin('users', 'mediators.user_id', '=', 'users.user_id')
        ->select(
            'users.first_name',
            'users.last_name',
            'users.given_name',
            'users.user_id',
            'mediators.association_name_full'
        )
        ->get();

        $typical_points = AgreementTypicalPoint::where('show_status_id', 1)
        ->orderBy('sort_num', 'asc')
        ->get();

        $locations = $this->locationService->get_locations($language);
        $agreement_types = $this->agreementService->get_agreement_types($language);

        $attributes = new \stdClass();

        $attributes->locations = $locations;
        $attributes->agreement_types = $agreement_types;
        $attributes->legal_forms = $legal_forms;
        $attributes->posts = $posts;
        $attributes->colors = $colors;
        $attributes->banks = $banks;
        $attributes->mediators = $mediators;
        $attributes->typical_points = $typical_points;

        return response()->json($attributes, 200);
    }

    public function get_my_templates(Request $request){
        $auth_user = auth()->user();

        $templates = CustomAgreementTemplate::where('user_id', $auth_user->user_id)
        ->where('status_type_id', 1)
        ->get();

        foreach ($templates as $key => $template) {
            if(isset($template->data)){
                $template->data = json_decode(Crypt::decryptString($template->data));
            }
        }

        return response()->json($templates, 200);
    }

    public function get_agreements(Request $request){
        $language = Language::where('lang_tag', '=', $request->header('Accept-Language'))->first();

        $auth_user = auth()->user();

        // Получаем параметры лимита на страницу
        $per_page = $request->per_page ? $request->per_page : 10;
        // Получаем параметры сортировки
        $sortKey = $request->input('sort_key', 'agreements.created_at');  // Поле для сортировки по умолчанию
        $sortDirection = $request->input('sort_direction', 'asc');  // Направление по умолчанию

        $agreements = Agreement::leftJoin('agreement_parties', 'agreements.agreement_id', '=', 'agreement_parties.agreement_id')
            ->leftJoin('types_of_agreements', 'agreements.agreement_type_id', '=', 'types_of_agreements.agreement_type_id')
            ->leftJoin('types_of_agreements_lang', 'types_of_agreements.agreement_type_id', '=', 'types_of_agreements_lang.agreement_type_id')
            ->leftJoin('users as initiator', 'agreements.initiator_id', '=', 'initiator.user_id')

            // ✅ статус agreement
            ->leftJoin('types_of_status as agreement_status', 'agreements.status_type_id', '=', 'agreement_status.status_type_id')
            ->leftJoin('types_of_status_lang as agreement_status_lang', function ($join) use ($language) {
                $join->on('agreement_status.status_type_id', '=', 'agreement_status_lang.status_type_id')
                    ->where('agreement_status_lang.lang_id', $language->lang_id);
            })

            // ✅ контракт
            ->leftJoin('mediation_contracts', 'agreements.agreement_id', '=', 'mediation_contracts.agreement_id')

            // ✅ статус contract
            ->leftJoin('types_of_status as contract_status', 'mediation_contracts.status_type_id', '=', 'contract_status.status_type_id')
            ->leftJoin('types_of_status_lang as contract_status_lang', function ($join) use ($language) {
                $join->on('contract_status.status_type_id', '=', 'contract_status_lang.status_type_id')
                    ->where('contract_status_lang.lang_id', $language->lang_id);
            })

            // ✅ кастомный шаблон
            ->leftJoin('custom_agreement_templates', 'agreements.custom_template_id', '=', 'custom_agreement_templates.template_id')

            ->select(
                'agreements.uuid',
                'agreements.custom_template_id',
                'initiator.first_name as initiator_first_name',
                'initiator.last_name as initiator_last_name',
                'types_of_agreements_lang.agreement_type_name',
                'agreements.created_at',
                'agreements.custom_template_id',
                'custom_agreement_templates.template_name',

                // 👇 статусы
                'agreements.status_type_id as agreement_status_id',
                'agreement_status.color as agreement_status_color',
                'agreement_status_lang.status_type_name as agreement_status_name',

                'mediation_contracts.status_type_id as contract_status_id',
                'contract_status.color as contract_status_color',
                'contract_status_lang.status_type_name as contract_status_name',
            )
            ->where('agreement_parties.user_id', $auth_user->user_id)
            ->where('types_of_agreements_lang.lang_id', $language->lang_id)
            ->distinct()
            ->orderBy($sortKey, $sortDirection);

        // Применяем фильтрацию по параметрам из запроса
        $created_at_from = $request->created_at_from;
        $created_at_to = $request->created_at_to;

        // Фильтрация по дате создания
        if ($created_at_from && $created_at_to) {
            $agreements->whereBetween('agreements.created_at', [$created_at_from . ' 00:00:00', $created_at_to . ' 23:59:59']);
        } elseif ($created_at_from) {
            $agreements->where('agreements.created_at', '>=', $created_at_from . ' 00:00:00');
        } elseif ($created_at_to) {
            $agreements->where('agreements.created_at', '<=', $created_at_to . ' 23:59:59');
        }

        // Возвращаем пагинированный результат
        return response()->json($agreements->paginate($per_page)->onEachSide(1), 200);
    }

    public function get_agreement(Request $request){
       $language = Language::where('lang_tag', '=', $request->lang)->first();

        $agreement = Agreement::leftJoin('types_of_agreements', 'agreements.agreement_type_id', '=', 'types_of_agreements.agreement_type_id')
        ->leftJoin('types_of_agreements_lang', 'types_of_agreements.agreement_type_id', '=', 'types_of_agreements_lang.agreement_type_id')
        ->leftJoin('users as initiator', 'agreements.initiator_id', '=', 'initiator.user_id')
        // ✅ кастомный шаблон
        ->leftJoin('custom_agreement_templates', 'agreements.custom_template_id', '=', 'custom_agreement_templates.template_id')
        ->select(
            'agreements.agreement_id',
            'agreements.agreement_type_id',
            'agreements.custom_template_id',
            'agreements.uuid',
            'agreements.data',
            'agreements.sigex_document_id',
            'custom_agreement_templates.template_name',
            'initiator.first_name as initiator_first_name',
            'initiator.last_name as initiator_last_name',
            'types_of_agreements.agreement_slug',
            'types_of_agreements_lang.agreement_type_name',
            'agreements.created_at'
        )
        ->where('agreements.uuid', $request->uuid)
        ->where('types_of_agreements_lang.lang_id', '=', $language->lang_id)
        ->distinct()
        ->firstOrFail();

        $custom_agreement_types = ['arbitary', 'custom'];

        if(in_array($agreement->agreement_slug, $custom_agreement_types)){
            $agreement->points = json_decode(Crypt::decryptString($agreement->data));
        }
        else{
            $agreement->data = json_decode(Crypt::decryptString($agreement->data));
        }

        $agreement_parties = AgreementParty::leftJoin('users', 'agreement_parties.user_id', '=', 'users.user_id')
        ->select(
            'users.first_name',
            'users.last_name',
            'users.given_name',
            'users.iin',
            'users.data',
            'agreement_parties.user_id',
            'agreement_parties.is_mediator',
            'agreement_parties.sigex_sign_id',
            'agreement_parties.sigex_sign',
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

        $agreement->parties = $agreement_parties;

        $contract = MediationContract::where('agreement_id', '=', $agreement->agreement_id)
        ->firstOrFail();

        $contract->data = json_decode(Crypt::decryptString($contract->data));

        $contract_parties = MediationContractParty::leftJoin('users', 'mediation_contract_parties.user_id', '=', 'users.user_id')
        ->select(
            'users.first_name',
            'users.last_name',
            'users.given_name',
            'users.iin',
            'users.data',
            'mediation_contract_parties.user_id',
            'mediation_contract_parties.is_mediator',
            'mediation_contract_parties.sigex_sign_id',
            'mediation_contract_parties.sigex_sign',
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

        $contract->parties = $contract_parties;

        return response()->json([
            'agreement' => $agreement,
            'contract' => $contract
        ], 200); 
    }

    public function get_file(Request $request){

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
        return $response;
    }

    public function save(Request $request){
        $language = Language::where('lang_tag', '=', $request->lang)->first();

        app()->setLocale($request->lang);

        $rules = [];

        if ($request->step === 1) {

            foreach ($request['agreement_parties'] as $index => $party) {
                $rules = array_merge($rules, [
                    "agreement_parties.$index.data.is_legal" => 'required|boolean',
                    "agreement_parties.$index.first_name" => 'required|string',
                    "agreement_parties.$index.last_name" => 'required|string',
                    "agreement_parties.$index.iin" => 'required|string|size:12',
                    "agreement_parties.$index.data.location_id" => 'required|numeric',
                    "agreement_parties.$index.data.street" => 'required|string|between:2,100',
                    "agreement_parties.$index.data.house" => 'required|regex:/^\d+(\/\d+)?$/',

                    "agreement_parties.$index.data.legal_form_id" =>
                        "nullable|required_if:agreement_parties.$index.data.is_legal,true|numeric",

                    "agreement_parties.$index.data.post_type_id" =>
                        "nullable|required_if:agreement_parties.$index.data.is_legal,true|numeric",

                    "agreement_parties.$index.data.bin" =>
                        "nullable|required_if:agreement_parties.$index.data.is_legal,true|string|size:12",

                    "agreement_parties.$index.data.company_name" =>
                        "nullable|required_if:agreement_parties.$index.data.is_legal,true|string",

                    "agreement_parties.$index.data.company_location_id" =>
                        "nullable|required_if:agreement_parties.$index.data.is_legal,true|numeric",

                    "agreement_parties.$index.data.company_street" =>
                        "nullable|required_if:agreement_parties.$index.data.is_legal,true|string|between:2,100",

                    "agreement_parties.$index.data.company_building" =>
                        "nullable|required_if:agreement_parties.$index.data.is_legal,true|between:1,10",
                ]);
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            return response()->json([
                'step' => $request->step
            ], 200);
        }
        elseif($request->step === 2){

            $rules = [
                'mediator_id' => 'required|numeric',
                'contract_data.prepayment' => 'required|string',
                'contract_data.award' => 'required|string',
                'step' => 'required|numeric',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            return response()->json([
                'step' => $request->step,
                'preview' => $this->agreementService->create_agreement_preview($request->agreement_parties, $request->mediator_id)
            ], 200);
        }
        else{
            $rules = [
                'agreement_type_id' => 'required|numeric',
                'custom_template.name' => "nullable|required_if:custom_template.save,true",
                'step' => 'required|numeric',
            ];

            $agreementRules = [];

            if(isset($request->agreement_type_id)){
                $agreement_type = AgreementType::findOrFail($request->agreement_type_id);
            
                switch ($agreement_type->agreement_slug) {
                    case 'debt_repayment':
                        $agreementRules['amount_of_debt'] = 'required|string';
                        $agreementRules['basis_for_issuing_loan'] = 'required|string';

                        if(!empty($request['agreement_data']['basis_for_issuing_loan'])){
                            switch ($request['agreement_data']['basis_for_issuing_loan']) {
                                case 'receipt':
                                    $agreementRules['receipt_date'] = 'required|date';
                                    break;
                                case 'contract':
                                    $agreementRules['contract_num'] = 'required|numeric';
                                    $agreementRules['contract_date'] = 'required|date';
                                    break;
                                default:
                                    # code...
                                    break;
                            }

                            $agreementRules['repayment_start_date'] = 'required|date';
                            $agreementRules['repayment_period'] = 'required|numeric';
                            $agreementRules['bank_id'] = 'required';
                            $agreementRules['iik'] = 'required';
                            $agreementRules['bik'] = 'required';
                        }
                        break;

                    case 'rent_car':
                        $agreementRules['car_brand'] = 'required|string';
                        $agreementRules['car_model'] = 'required|string';
                        $agreementRules['state_registration_number'] = 'required|string';
                        $agreementRules['vin_code'] = 'required|string|size:17';
                        $agreementRules['year_of_release'] = 'required|string|size:4';
                        $agreementRules['body_color_id'] = 'required|numeric';
                        $agreementRules['car_amount'] = 'required|string';
                        $agreementRules['payment_format'] = 'required';
                        $agreementRules['rent_amount'] = 'required|string';
                        $agreementRules['rent_start_date'] = 'required|date';
                        $agreementRules['rent_period'] = 'required|numeric';
                        break;

                    case 'alimony_payments':

                        $agreementRules['monthly_amount'] = 'required|string';
                        $agreementRules['start_payment_date'] = 'required|date';

                        $agreementRules['children'] = 'required|array|min:1';
                        $agreementRules['children.*.first_name'] = 'required|string';
                        $agreementRules['children.*.last_name'] = 'required|string';
                        $agreementRules['children.*.given_name'] = 'nullable|string';
                        $agreementRules['children.*.birth_date'] = 'required|date';
                        break;

                    case 'determining_the_place_of_residence':

                        $agreementRules['children'] = 'required|array|min:1';
                        $agreementRules['children.*.first_name'] = 'required|string';
                        $agreementRules['children.*.last_name'] = 'required|string';
                        $agreementRules['children.*.given_name'] = 'nullable|string';
                        $agreementRules['children.*.birth_date'] = 'required|date';
                        $agreementRules['children.*.residential_address'] = 'required|numeric';
                        break;

                    case 'custom' :
                        $rules["custom_template.id"] = 'nullable||required_if:custom_template.new,false|numeric';
                        break;
                    
                    default:
                        # code...
                        break;
                }

                foreach ($agreementRules as $field => $rule) {
                    $rules["agreement_data.$field"] = $rule;
                }
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $data = collect($request->except(['lang', 'step']))
            ->map(function ($v){
                return is_string($v) ? strip_tags($v) : $v;
            })
            ->toArray();

            $custom_agreement_types = ['arbitary', 'custom'];

            $agreement_data = Crypt::encryptString(json_encode(in_array($agreement_type->agreement_slug, $custom_agreement_types) ? $data['points'] : $data['agreement_data']));

            if(isset($request->uuid)){
                $edit_agreement = Agreement::where('uuid', $request->uuid)
                ->firstOrFail();

                $edit_agreement->data = $agreement_data;
                $edit_agreement->sigex_document_id = null;
                $edit_agreement->status_type_id = 11;

                if(isset($request->custom_template)){
                    $custom_template = $request->custom_template;

                    if(isset($custom_template['id'])){
                        $search_template = CustomAgreementTemplate::findOrFail($custom_template['id']);
                        
                        if($custom_template['save'] === true){
                            $search_template->template_name = $custom_template['name'];
                            $search_template->data = $agreement_data;
                            $search_template->save();
                        }

                        $edit_agreement->custom_template_id = $search_template->template_id;
                    }
                    elseif ($custom_template['save'] === true) {
                        $new_custom_agreement_template = new CustomAgreementTemplate();
                        $new_custom_agreement_template->template_name = $custom_template['name'];
                        $new_custom_agreement_template->user_id = auth()->user()->user_id;
                        $new_custom_agreement_template->data = $agreement_data;
                        $new_custom_agreement_template->save();

                        $edit_agreement->custom_template_id = $new_custom_agreement_template->template_id;
                    }
                }

                $edit_agreement->save();

                $uuid = $request->uuid;

                AgreementParty::where('agreement_id', $edit_agreement->agreement_id)
                ->delete();
            }
            else{
                $new_agreement = new Agreement();
                $new_agreement->uuid = str_replace('-', '', (string) Str::uuid());
                $new_agreement->initiator_id = auth()->user()->user_id;
                $new_agreement->data = $agreement_data;
                $new_agreement->agreement_type_id = $request->agreement_type_id;
                $new_agreement->status_type_id = 11;

                if(isset($request->custom_template)){
                    $custom_template = $request->custom_template;

                    if(isset($custom_template['id'])){
                        $search_template = CustomAgreementTemplate::findOrFail($custom_template['id']);

                        if($custom_template['save'] === true){
                            $search_template->template_name = $custom_template['name'];
                            $search_template->data = $agreement_data;
                            $search_template->save();
                        }

                        $new_agreement->custom_template_id = $search_template->template_id;
                    }
                    elseif ($custom_template['save'] === true) {
                        $new_custom_agreement_template = new CustomAgreementTemplate();
                        $new_custom_agreement_template->template_name = $custom_template['name'];
                        $new_custom_agreement_template->user_id = auth()->user()->user_id;
                        $new_custom_agreement_template->data = $agreement_data;
                        $new_custom_agreement_template->save();

                        $new_agreement->custom_template_id = $new_custom_agreement_template->template_id;
                    }
                }

                $new_agreement->save();
                $uuid = $new_agreement->uuid;
            }

            $agreement_id = isset($edit_agreement) ? $edit_agreement->agreement_id : $new_agreement->agreement_id;

            foreach ($data['agreement_parties'] as $key => $party) {
                $find_iin = User::where('iin', '=', $party['iin'])
                ->first();

                if(!isset($find_iin)){
                    $new_user = new User();
                    $new_user->iin = $party['iin'];
                    $new_user->first_name = mb_ucwords($party['first_name']);
                    $new_user->last_name = mb_ucwords($party['last_name']);
                    $new_user->given_name = mb_ucwords($party['given_name']);
                    $new_user->data = Crypt::encryptString(json_encode($party['data']));
                    $new_user->save();
                }
                else{
                    $find_iin->first_name = mb_ucwords($party['first_name']);
                    $find_iin->last_name = mb_ucwords($party['last_name']);
                    $find_iin->given_name = mb_ucwords($party['given_name']);
                    $find_iin->data = Crypt::encryptString(json_encode($party['data']));
                    $find_iin->save();
                }

                $new_party = new AgreementParty();
                $new_party->user_id = isset($find_iin) ? $find_iin->user_id : $new_user->user_id;
                $new_party->agreement_id = $agreement_id;
                $new_party->save();

                if($key === 0){
                    $firstPartyUserId = $new_party->user_id;
                }
            }

            $new_party = new AgreementParty();
            $new_party->user_id = $data['mediator_id'];
            $new_party->agreement_id = $agreement_id;
            $new_party->is_mediator = 1;
            $new_party->save();

            $this->agreementService->create_agreement_file($uuid, $language->lang_id, false);
            $this->agreementService->create_agreement_file($uuid, $language->lang_id, true);

            if(isset($firstPartyUserId)){

                if(isset($edit_agreement)){
                    MediationContract::where('agreement_id', $edit_agreement->agreement_id)
                        ->get()
                        ->each(function ($contract) {
                            foreach (['original', 'signed'] as $type) {
                                $path = storage_path("app/public/agreements/contracts/{$type}/{$contract->uuid}.pdf");

                                if (File::exists($path)) {
                                    File::delete($path);
                                }
                            }
                        });

                    MediationContract::where('agreement_id', $edit_agreement->agreement_id)->delete();
                }

                $new_mediation_contract = new MediationContract();
                $new_mediation_contract->uuid = str_replace('-', '', (string) Str::uuid());
                $new_mediation_contract->data = Crypt::encryptString(json_encode($data['contract_data']));
                $new_mediation_contract->agreement_id = $agreement_id;
                $new_mediation_contract->status_type_id = 11;
                $new_mediation_contract->save();

                $new_party = new MediationContractParty();
                $new_party->user_id = $firstPartyUserId;
                $new_party->mediation_contract_id = $new_mediation_contract->mediation_contract_id;
                $new_party->is_mediator = 0;
                $new_party->save();

                $new_party = new MediationContractParty();
                $new_party->user_id = $data['mediator_id'];
                $new_party->mediation_contract_id = $new_mediation_contract->mediation_contract_id;
                $new_party->is_mediator = 1;
                $new_party->save();

                $this->agreementService->create_mediation_contract_file($agreement_id, $language->lang_id, false);
                $this->agreementService->create_mediation_contract_file($agreement_id, $language->lang_id, true);
            }

            return response()->json([
                'status' => 'success',
                'uuid' => $uuid
            ], 200);
        }
    }

    public function sign(Request $request)
    {
        try {
            $apiUrl = Config::get('constants.sigex_api');

            $agreement = Agreement::where('uuid', $request->uuid)->firstOrFail();
            $contract = MediationContract::where('agreement_id', $agreement->agreement_id)->first();

            $language = Language::where('lang_tag', $request->lang)->firstOrFail();

            [$document, $parties, $model, $type] = $this->resolveContext($request->mode, $agreement, $contract);

            $ids = collect($parties)->map(function ($p) {
                return [
                    'iin' => 'IIN' . $p->iin
                ];
            })->values()->toArray();

            if (!$document->sigex_document_id) {
                // 📌 Создание документа (если нет)
                $responseData = $this->sigexRequest('POST', $apiUrl, [
                    'title' => $document->uuid . '.pdf',
                    'signType' => 'cms',
                    'signature' => $request->signature,
                    'settings' => $this->buildSettings($ids, count($parties)),
                ]);

                $documentId = $responseData['documentId'];

                // 📌 Отправка файла (фикс хеша)
                $filePath = $this->getFilePath($type, $document->uuid);

                if (!File::exists($filePath)) {
                    return response()->json(['status' => 'error', 'message' => 'File not found'], 404);
                }

                $this->sigexUpload($apiUrl, $documentId, $filePath);

                $document->sigex_document_id = $documentId;
                $document->save();
            }
            else {
                // 📌 Подпись существующего документа
                $this->sigexRequest('POST', $apiUrl . '/' . $document->sigex_document_id, [
                    'signType' => 'cms',
                    'signature' => $request->signature,
                ]);
            }

            // 📌 Получение подписей
            $responseData = $this->sigexRequest('GET', $apiUrl . '/' . $document->sigex_document_id);

            $this->updateSignatures($parties, $responseData['signatures'], $model, $document);

            // 📌 Генерация файла
            $this->generateDocument($type, $agreement, $language);

            return response()->json([
                'status' => 'success',
                'uuid' => $agreement->uuid
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function get_cms_file(Request $request){
        $agreement = Agreement::where('uuid', $request->uuid)
        ->firstOrFail();

        $contract = MediationContract::where('agreement_id', $agreement->agreement_id)->first();

        $api_url = Config::get('constants.sigex_api');

        [$document, $parties, $model, $type] = $this->resolveContext($request->type, $agreement, $contract);

        if($document->sigex_document_id){
            $filePath = $this->getFilePath($type, $document->uuid);

            if (!File::exists($filePath)) {
                return response()->json(['status' => 'error', 'message' => 'File not found'], 404);
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => filesize($filePath),
            ])->withBody(
                file_get_contents($filePath),
                'application/octet-stream'
            )->post($api_url.'/'.$document->sigex_document_id.'/buildEzSigner');

            $responseData = $response->json();

            if (!$response->successful() || isset($responseData['message'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => $responseData['message'] ?? 'Unknown error'
                ], 500);
            }

            $cmsBinary = base64_decode($responseData['signature']);

            return response($cmsBinary, 200, [
                'Content-Type' => 'application/pkcs7-mime'
            ]);
        }
    }

    private function resolveContext($mode, $agreement, $contract)
    {
        if ($mode === 'agreement') {
            return [
                $agreement,
                AgreementParty::leftJoin('users', 'agreement_parties.user_id', '=', 'users.user_id')
                    ->select('agreement_parties.id', 'users.iin')
                    ->where('agreement_parties.agreement_id', $agreement->agreement_id)
                    ->get(),
                AgreementParty::class,
                'agreement'
            ];
        }

        if ($mode === 'contract') {
            if (!$contract) {
                throw new \Exception('Contract not found');
            }

            return [
                $contract,
                MediationContractParty::leftJoin('users', 'mediation_contract_parties.user_id', '=', 'users.user_id')
                    ->select('mediation_contract_parties.id', 'users.iin')
                    ->where('mediation_contract_parties.mediation_contract_id', $contract->mediation_contract_id)
                    ->get(),
                MediationContractParty::class,
                'contract'
            ];
        }

        throw new \Exception('Invalid mode');
    }

    private function buildSettings($ids, $limit)
    {
        return [
            'private' => false,
            'signaturesLimit' => $limit,
            'switchToPrivateAfterLimitReached' => false,
            'unique' => ['iin'],
            'signersRequirements' => $ids,
            'strictSignersRequirements' => true,
            'publicDuringPreregistration' => false,
            'publicWhileLessThanSignatures' => 0,
            'documentAccess' => [],
            'forceArchive' => false,
            'tempStorageAfterRegistration' => 0,
        ];
    }

    private function sigexRequest($method, $url, $data = [])
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->$method($url, $data);

        $json = $response->json();

        if (!$response->successful() || isset($json['message'])) {
            throw new \Exception($json['message'] ?? 'Sigex error');
        }

        return $json;
    }

    private function sigexUpload($apiUrl, $documentId, $filePath)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => filesize($filePath),
        ])->withBody(
            file_get_contents($filePath),
            'application/octet-stream'
        )->post("$apiUrl/$documentId/data");

        $json = $response->json();

        if (!$response->successful() || isset($json['message'])) {
            throw new \Exception($json['message'] ?? 'Upload error');
        }
    }

    private function getFilePath($type, $uuid)
    {
        return $type === 'agreement'
            ? storage_path("app/public/agreements/original/$uuid.pdf")
            : storage_path("app/public/agreements/contracts/original/$uuid.pdf");
    }

    private function updateSignatures($parties, $signatures, $model, $document)
    {
        $index = collect($parties)->pluck('id', 'iin')->toArray();

        $signedIins = [];

        foreach ($signatures as $signature) {
            $iin = str_replace('IIN', '', $signature['userId']);

            if (isset($index[$iin])) {
                $signedIins[] = $iin;

                $model::where('id', $index[$iin])->update([
                    'sigex_sign_id' => $signature['signId'],
                    'sigex_sign' => Crypt::encryptString(json_encode($signature)),
                    'signed_at' => Carbon::createFromTimestamp($signature['storedAt'] / 1000, 'Asia/Almaty'),
                ]);
            }
        }

        $totalCount = count($index);
        $signedCount = count(array_unique($signedIins));

        if ($signedCount > 0) {
            $document->status_type_id = 12;
        }

        if ($signedCount === $totalCount && $totalCount > 0) {
            $document->status_type_id = 13;
        }

        $document->save();
    }

    private function generateDocument($type, $agreement, $language)
    {
        if ($type === 'agreement') {
            $this->agreementService->create_agreement_file(
                $agreement->uuid,
                $language->lang_id,
                true
            );
        } else {
            $this->agreementService->create_mediation_contract_file(
                $agreement->agreement_id,
                $language->lang_id,
                true
            );
        }
    }
}
