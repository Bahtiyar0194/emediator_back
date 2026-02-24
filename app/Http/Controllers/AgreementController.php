<?php

namespace App\Http\Controllers;
use App\Models\LegalFormType;
use App\Models\OrganizationPostType;
use App\Models\AgreementType;
use App\Models\Agreement;
use App\Models\Location;
use App\Models\Language;
use App\Models\Color;
use Validator;
use Log;
use Str;

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

        $locations = $this->locationService->get_locations($language);
        $agreement_types = $this->agreementService->get_agreement_types($language);

        $attributes = new \stdClass();

        $attributes->locations = $locations;
        $attributes->agreement_types = $agreement_types;
        $attributes->legal_forms = $legal_forms;
        $attributes->posts = $posts;
        $attributes->colors = $colors;

        return response()->json($attributes, 200);
    }

    public function get_agreements(Request $request){
        $language = Language::where('lang_tag', '=', $request->header('Accept-Language'))->first();

        // Получаем параметры лимита на страницу
        $per_page = $request->per_page ? $request->per_page : 10;
        // Получаем параметры сортировки
        $sortKey = $request->input('sort_key', 'agreements.created_at');  // Поле для сортировки по умолчанию
        $sortDirection = $request->input('sort_direction', 'asc');  // Направление по умолчанию

        $agreements = Agreement::leftJoin('types_of_agreements', 'agreements.agreement_type_id', '=', 'types_of_agreements.agreement_type_id')
        ->leftJoin('types_of_agreements_lang', 'types_of_agreements.agreement_type_id', '=', 'types_of_agreements_lang.agreement_type_id')
        ->leftJoin('users as initiator', 'agreements.initiator_id', '=', 'initiator.user_id')
        ->leftJoin('users as mediator', 'agreements.mediator_id', '=', 'mediator.user_id')
        ->select(
            'agreements.uuid',
            'initiator.first_name as initiator_first_name',
            'initiator.last_name as initiator_last_name',
            'mediator.first_name as mediator_first_name',
            'mediator.last_name as mediator_last_name',
            'types_of_agreements_lang.agreement_type_name',
            'agreements.created_at'
        )
        ->where('types_of_agreements_lang.lang_id', '=', $language->lang_id)
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
        ->leftJoin('users as mediator', 'agreements.mediator_id', '=', 'mediator.user_id')
        ->leftJoin('mediators', 'agreements.mediator_id', '=', 'mediators.user_id')
        ->select(
            'agreements.uuid',
            'agreements.data',
            'initiator.first_name as initiator_first_name',
            'initiator.last_name as initiator_last_name',
            'mediator.first_name as mediator_first_name',
            'mediator.last_name as mediator_last_name',
            'mediator.given_name as mediator_given_name',
            'mediators.association_name_short',
            'mediators.association_name_full',
            'mediators.cert_num',
            'mediators.cert_date',
            'types_of_agreements.agreement_slug',
            'types_of_agreements_lang.agreement_type_name',
            'agreements.created_at'
        )
        ->where('agreements.uuid', $request->uuid)
        ->where('types_of_agreements_lang.lang_id', '=', $language->lang_id)
        ->distinct()
        ->firstOrFail();

        return response()->json($agreement, 200); 
    }

    public function get_agreement_file(Request $request){
        // $language = Language::where('lang_tag', '=', $request->header('Accept-Language'))->first();

        $agreement = Agreement::leftJoin('types_of_agreements', 'agreements.agreement_type_id', '=', 'types_of_agreements.agreement_type_id')
        // ->leftJoin('types_of_agreements_lang', 'types_of_agreements.agreement_type_id', '=', 'types_of_agreements_lang.agreement_type_id')
        ->leftJoin('users as initiator', 'agreements.initiator_id', '=', 'initiator.user_id')
        ->leftJoin('users as mediator', 'agreements.mediator_id', '=', 'mediator.user_id')
        ->leftJoin('mediators', 'agreements.mediator_id', '=', 'mediators.user_id')
        ->select(
            'agreements.agreement_id',
            'agreements.uuid',
            'agreements.data',
            'initiator.first_name as initiator_first_name',
            'initiator.last_name as initiator_last_name',
            'mediator.first_name as mediator_first_name',
            'mediator.last_name as mediator_last_name',
            'mediator.given_name as mediator_given_name',
            'mediators.association_name_short',
            'mediators.association_name_full',
            'mediators.cert_num',
            'mediators.cert_date',
            'types_of_agreements.agreement_slug',
            // 'types_of_agreements_lang.agreement_type_name',
            'agreements.created_at'
        )
        ->where('agreements.uuid', $request->uuid)
        // ->where('types_of_agreements_lang.lang_id', '=', $language->lang_id)
        ->distinct()
        ->firstOrFail();
        

        $pdf = Pdf::loadView('agreements.'.$agreement->agreement_slug, [
            'agreement' => $agreement,
            'data' => $agreement->data
        ])
        ->setOption('title', 'fdsf')
        ->setOption('author', 'EMediator.kz');

        $filename = sprintf(
            'agreement_%s_%s.pdf',
            $agreement->agreement_id,
            $agreement->created_at->format('Y-m-d')
        );

        //return $pdf->download('invoice.pdf');
        // или
        return $pdf->stream($filename);
    }

    public function create(Request $request){
        $language = Language::where('lang_tag', '=', $request->lang)->first();

        $rules = [];

        if ($request->step == 1) {
            $rules = [
                'first_name_1' => 'required|string',
                'last_name_1' => 'required|string',
                'iin_1' => 'required|string|size:12',
                'location_id_1' => 'required|numeric',
                'street_1' => 'required|string|between:2,100',
                'house_1' => 'required|between:1,10',
                'step' => 'required|numeric',
            ];

            if($request->is_legal_1 === true){
                $rules['legal_form_id_1'] = 'required|numeric';
                $rules['post_type_id_1'] = 'required|numeric';
                $rules['bin_1'] = 'required|string|size:12';
                $rules['company_name_1'] = 'required|string';
                $rules['company_location_id_1'] = 'required|numeric';
                $rules['company_street_1'] = 'required|string|between:2,100';
                $rules['company_building_1'] = 'required|between:1,10';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            return response()->json([
                'step' => 1
            ], 200);
        }
        elseif ($request->step == 2) {
            $rules = [
                'first_name_2' => 'required|string',
                'last_name_2' => 'required|string',
                'iin_2' => 'required|string|size:12',
                'location_id_2' => 'required|numeric',
                'street_2' => 'required|string|between:2,100',
                'house_2' => 'required|between:1,10',
                'step' => 'required|numeric',
            ];

            if($request->is_legal_2 === true){
                $rules['legal_form_id_2'] = 'required|numeric';
                $rules['post_type_id_2'] = 'required|numeric';
                $rules['bin_2'] = 'required|string|size:12';
                $rules['company_name_2'] = 'required|string';
                $rules['company_location_id_2'] = 'required|numeric';
                $rules['company_street_2'] = 'required|string|between:2,100';
                $rules['company_building_2'] = 'required|between:1,10';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            return response()->json([
                'step' => 2
            ], 200);
        }
        elseif($request->step == 3){

            $rules = [
                'agreement_type_id' => 'required|numeric',
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
                            $agreementRules['bank_name'] = 'required';
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

            $new_agreement = new Agreement();
            $new_agreement->uuid = str_replace('-', '', (string) Str::uuid());
            $new_agreement->initiator_id = auth()->user()->user_id;
            $new_agreement->mediator_id = 3;
            $new_agreement->data = $data;
            $new_agreement->agreement_type_id = $request->agreement_type_id;
            $new_agreement->status_type_id = 1;
            $new_agreement->save();

            return response()->json('success', 200);
        }
    }
}
