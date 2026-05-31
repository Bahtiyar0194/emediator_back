<?php
namespace App\Services;
use App\Models\Agreement;
use App\Models\AgreementType;
use App\Models\User;
use App\Models\Mediator;
use App\Models\AgreementParty;
use App\Models\AgreementTypicalPoint;
use App\Models\MediationContract;
use App\Models\MediationContractParty;

use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Storage;

class AgreementService
{
    public function create_agreement_preview($parties, $mediator_id){
        
        foreach ($parties as &$party) {
            $party['is_mediator'] = 0;
        }

        unset($party); // важно!

        $mediator = User::select(
            'iin',
            'first_name',
            'last_name',
            'given_name',
            'data',
            'user_id'
        )
        ->where('user_id', $mediator_id)
        ->firstOrFail();

        $mediator_data = Mediator::where('user_id', $mediator->user_id)
        ->first();

        $mediatorObj = (object) [
            'iin' => $mediator->iin,
            'first_name' => $mediator->first_name,
            'last_name' => $mediator->last_name,
            'given_name' => $mediator->given_name,
            'data' => isset($mediator->data) ? json_decode(Crypt::decryptString($mediator->data)) : null,
            'user_id' => $mediator->user_id,
            'mediator' => $mediator_data,
            'is_mediator' => 1,
        ];

        $parties[] = $mediatorObj;

        return view('layouts.parts.header', [
            'doctype' => 'agreement',
            'document' => (object) [
                'updated_at' => now()
            ],
            // 'data' => json_decode(json_encode($agreement_data)),
            'parties' => json_decode(json_encode($parties)),
            // 'signed' => true
        ])->render();
    }

    public function create_agreement_file($uuid, $lang_id, $signed){
        $agreement = Agreement::leftJoin('types_of_agreements', 'agreements.agreement_type_id', '=', 'types_of_agreements.agreement_type_id')
        ->leftJoin('types_of_agreements_lang', 'types_of_agreements.agreement_type_id', '=', 'types_of_agreements_lang.agreement_type_id')
        ->leftJoin('users as initiator', 'agreements.initiator_id', '=', 'initiator.user_id')
        ->select(
            'agreements.agreement_id',
            'agreements.uuid',
            'agreements.data',
            'agreements.sigex_document_id',
            'initiator.first_name as initiator_first_name',
            'initiator.last_name as initiator_last_name',
            'types_of_agreements.agreement_slug',
            'types_of_agreements_lang.agreement_type_name',
            'agreements.created_at',
            'agreements.updated_at'
        )
        ->where('agreements.uuid', $uuid)
        ->where('types_of_agreements_lang.lang_id', '=', $lang_id)
        ->distinct()
        ->firstOrFail();

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
        ->orderBy('agreement_parties.id', 'asc')
        ->get();

        foreach ($agreement_parties as $key => $party) {
            if(isset($party->data)){
                $party->data = json_decode(Crypt::decryptString($party->data));
            }

            if($party->is_mediator === 1){
                $mediator = Mediator::where('user_id', $party->user_id)
                ->first();

                $party->mediator = $mediator;
            }
        }

        $typical_points = AgreementTypicalPoint::where('show_status_id', 1)
        ->orderBy('sort_num', 'asc')
        ->get();

        $custom_agreement_types = ['arbitary', 'custom'];

        $agreement_slug = in_array($agreement->agreement_slug, $custom_agreement_types) ? 'arbitary_custom' : $agreement->agreement_slug;
        
        $pdf = Pdf::loadView('agreements.'.$agreement_slug, [
            'doctype' => 'agreement',
            'document' => $agreement,
            'data' => json_decode(Crypt::decryptString($agreement->data)),
            'parties' => $agreement_parties,
            'points' => $typical_points,
            'signed' => $signed
        ])
        ->setOption('title', $uuid.'.pdf')
        ->setOption('author', 'EMediator.kz');

        // $dompdf = $pdf->getDomPDF();
        // $dompdf->render(); // <-- обязательно!
        // $canvas = $dompdf->getCanvas();

        // $width  = $canvas->get_width();
        // $height = $canvas->get_height();

        // $dompdf = $pdf->getDomPDF();
        // $canvas = $dompdf->getCanvas();

        // $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {

        //     $text = "ЧЕРНОВИК";
        //     $font = $fontMetrics->getFont("Roboto", "Bold");
        //     $size = 70;

        //     // размеры страницы
        //     $width  = $canvas->get_width();
        //     $height = $canvas->get_height();

        //     // центр страницы
        //     $centerX = $width / 2;
        //     $centerY = $height / 2;
        //     $y = $centerY - ($size / 3);

        //     // ширина текста
        //     $textWidth = $fontMetrics->getTextWidth($text, $font, $size);

        //     $canvas->set_opacity(0.2);

        //     // поворачиваем ВОКРУГ центра страницы
        //     $canvas->rotate(45, $centerX, $centerY);

        //     $canvas->text(
        //         $centerX - ($textWidth / 2),
        //         $y,
        //         $text,
        //         $font,
        //         $size,
        //         [1, 0, 0] //красный
        //     );

        //     // вернуть обратно систему координат
        //     $canvas->rotate(0, 0, 0);

        //         // вернуть стандартную непрозрачность
        //     $canvas->set_opacity(1);
        // });

        // $filename = sprintf(
        //     'agreement_%s_%s.pdf',
        //     $agreement->uuid,
        //     $agreement->created_at->format('d-m-Y')
        // );

        $path = $signed === true ? 'signed' : 'original';

        // путь относительно storage/app/public
        $path = 'agreements/'.$path.'/'.$uuid.'.pdf';

        // сохраняем файл
        Storage::disk('public')->put($path, $pdf->output());
    }

    public function create_mediation_contract_file($agreement_id, $lang_id, $signed){

        $mediation_contract = MediationContract::where('agreement_id', '=', $agreement_id)
        ->firstOrFail();

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
        ->where('mediation_contract_parties.mediation_contract_id', $mediation_contract->mediation_contract_id)
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
        
        $pdf = Pdf::loadView('layouts.mediation.contract', [
            'doctype' => 'contract',
            'document' => $mediation_contract,
            'data' => json_decode(Crypt::decryptString($mediation_contract->data)),
            'parties' => $contract_parties,
            'signed' => $signed
        ])
        ->setOption('title', $mediation_contract->uuid.'.pdf')
        ->setOption('author', 'EMediator.kz');

        // $dompdf = $pdf->getDomPDF();
        // $dompdf->render(); // <-- обязательно!
        // $canvas = $dompdf->getCanvas();

        // $width  = $canvas->get_width();
        // $height = $canvas->get_height();

        // $dompdf = $pdf->getDomPDF();
        // $canvas = $dompdf->getCanvas();

        // $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {

        //     $text = "ЧЕРНОВИК";
        //     $font = $fontMetrics->getFont("Roboto", "Bold");
        //     $size = 70;

        //     // размеры страницы
        //     $width  = $canvas->get_width();
        //     $height = $canvas->get_height();

        //     // центр страницы
        //     $centerX = $width / 2;
        //     $centerY = $height / 2;
        //     $y = $centerY - ($size / 3);

        //     // ширина текста
        //     $textWidth = $fontMetrics->getTextWidth($text, $font, $size);

        //     $canvas->set_opacity(0.2);

        //     // поворачиваем ВОКРУГ центра страницы
        //     $canvas->rotate(45, $centerX, $centerY);

        //     $canvas->text(
        //         $centerX - ($textWidth / 2),
        //         $y,
        //         $text,
        //         $font,
        //         $size,
        //         [1, 0, 0] //красный
        //     );

        //     // вернуть обратно систему координат
        //     $canvas->rotate(0, 0, 0);

        //         // вернуть стандартную непрозрачность
        //     $canvas->set_opacity(1);
        // });

        // $filename = sprintf(
        //     'agreement_%s_%s.pdf',
        //     $agreement->uuid,
        //     $agreement->created_at->format('d-m-Y')
        // );

        $path = $signed === true ? 'signed' : 'original';

        // путь относительно storage/app/public
        $path = 'agreements/contracts/'.$path.'/'.$mediation_contract->uuid.'.pdf';

        // сохраняем файл
        Storage::disk('public')->put($path, $pdf->output());
    }

    public function get_agreement_types($language){
        // Получаем текущего аутентифицированного пользователя
        $auth_user = auth()->user();

        // Проверяем роли пользователя
        $isAdmin = $auth_user->hasRole(['super_admin', 'admin']);
        $isMediator = $auth_user->hasRole(['mediator']);

        $agreement_types = AgreementType::leftJoin('types_of_agreements_lang', 'types_of_agreements.agreement_type_id', '=', 'types_of_agreements_lang.agreement_type_id')
        ->where('types_of_agreements_lang.lang_id', '=', $language->lang_id)
        ->where('types_of_agreements.show_status_id', '=', 1)
        ->distinct()
        ->select(
            'types_of_agreements.agreement_type_id',
            'types_of_agreements.parent_id',
            'types_of_agreements.agreement_slug',
            'types_of_agreements.agreement_type_component',
            'types_of_agreements_lang.agreement_type_name'
        );

        // Применяем фильтры ролей в зависимости от роли пользователя
        if ($isAdmin) {
            //Здесь выводим все типы соглашении
        } 
        elseif ($isMediator) {
            //Если имеет роль медиатора то только произвольные типы соглашении
            $agreement_types->whereIn('types_of_agreements.agreement_slug', ['arbitary', 'custom']);
        } 
        else{
            //Если простой пользователь то выводим только конструктор
            $agreement_types->whereNotIn('types_of_agreements.agreement_slug', ['arbitary', 'custom']);
        }

        $typesList = $agreement_types->orderBy('types_of_agreements.agreement_type_id', 'asc')
        ->orderBy('types_of_agreements_lang.agreement_type_name', 'desc')
        ->get();

        $agreementTree = $this->buildTree($typesList);

        return $agreementTree;
    }

    private function buildTree($agreement_types, $parent_id = null, $level = 0)
    {
        $tree = [];

        foreach ($agreement_types as $agreement_type) {
            if ($agreement_type->parent_id == $parent_id) {
                $children = $this->buildTree($agreement_types, $agreement_type->agreement_type_id, $level + 1);
                $tree[] = [
                    'agreement_type_id' => $agreement_type->agreement_type_id,
                    'agreement_slug' => $agreement_type->agreement_slug,
                    'agreement_type_component' => $agreement_type->agreement_type_component,
                    'agreement_type_name' => $agreement_type->agreement_type_name,
                    'childs' => $children,
                    'level' => $level
                ];
            }
        }

        return $tree;
    }
}
?>