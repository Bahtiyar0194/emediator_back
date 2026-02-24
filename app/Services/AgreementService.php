<?php
namespace App\Services;
use App\Models\AgreementType;

class AgreementService
{
    public function get_agreement_types($language){
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
        )
        ->orderBy('types_of_agreements.agreement_type_id', 'asc')
        ->orderBy('types_of_agreements_lang.agreement_type_name', 'desc')
        ->get();

        $agreementTree = $this->buildTree($agreement_types);

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