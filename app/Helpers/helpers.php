<?php
use App\Models\LegalFormType;
use App\Models\OrganizationPostType;
use App\Models\Location;
use App\Models\Bank;
use App\Models\Color;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Carbon\Carbon;

function morph($n, $f1, $f2, $f5) {
    $n = abs(intval($n)) % 100;
    if ($n>10 && $n<20) return $f5;
    $n = $n % 10;
    if ($n>1 && $n<5) return $f2;
    if ($n==1) return $f1;
    return $f5;
}

if(!function_exists('mb_ucwords')){
    function mb_ucwords($string) {
        $words = explode(' ', $string);

        $words = array_map(function ($word) {
            $first = mb_substr($word, 0, 1, 'UTF-8');
            $rest = mb_substr($word, 1, null, 'UTF-8');

            return mb_strtoupper($first, 'UTF-8') . $rest;
        }, $words);

        return implode(' ', $words);
    }
}

if(!function_exists('getShortLegalForm')){
    function getShortLegalForm($legal_form_id, $language_id)
    {
        return LegalFormType::find($legal_form_id)
            ->types_of_legal_forms_lang()
            ->where('lang_id', $language_id)
            ->value('short_name');
    }
}

if(!function_exists('getOrganizationPost')){
    function getOrganizationPost($post_type_id, $language_id)
    {
        return OrganizationPostType::find($post_type_id)
            ->types_of_organization_posts_lang()
            ->where('lang_id', $language_id)
            ->value('post_type_name');
    }
}

if (!function_exists('getLocation')) {
    function getLocation($location_id, $language_id)
    {
        return Location::find($location_id)
            ->locations_lang()
            ->where('lang_id', $language_id)
            ->value('location_name');
    }
}

if (!function_exists('getFullLocation')) {
    function getFullLocation($location_id, $language_id)
    {
        $location = Location::with(['locations_lang' => function ($q) use ($language_id) {
            $q->where('locations_lang.lang_id', $language_id);
        }])->find($location_id);

        $names = [];
        $loc = $location;

        while ($loc) {
            $name = $loc->locations_lang()
                ->where('lang_id', $language_id)
                ->value('location_name');

            if ($name) {
                $names[] = $name;
            }

            $loc = $loc->parent;
        }

        return implode(', ', array_reverse(array_filter($names)));
    }
}

if (!function_exists('numStr')) {
    function numStr($num, $lang) {
        if($lang === 'ru'){
            $nul='ноль';
            $ten=array(
                array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
                array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
            );
            $a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
            $tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
            $hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
            $unit=array(
                array('тиын' ,'тиына' ,'тиынов',    1),
                array(''   ,''   ,''    ,0),
                array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
                array('миллион' ,'миллиона','миллионов' ,0),
                array('миллиард','милиарда','миллиардов',0),
            );
        }
        elseif($lang === 'kk'){
            $nul='нөл';
            $ten=array(
                array('','бір','екі','үш','төрт','бес','алты','жеті', 'сегіз','тоғыз'),
                array('','бір','екі','үш','төрт','бес','алты','жеті', 'сегіз','тоғыз'),
            );
            $a20=array('он','он бір','он екі','он үш','он төрт' ,'он бес','он алты','он жеті','он сегіз','он тоғыз');
            $tens=array(2=>'жиырма','отыз','қырық','елу','алпыс','жетпіс' ,'сексен','тоқсан');
            $hundred=array('','жүз','екі жүз','үш жүз','төрт жүз','бес жүз','алты жүз', 'жеті жүз','сегіз жүз','тоғыз жүз');

            $unit=array(
                array('тиын' ,'тиын' ,'тиын',    1),
                array(''   ,''   ,''    ,0),
                array('мың'  ,'мың'  ,'мың'     ,1),
                array('миллион' ,'миллион','миллион' ,0),
                array('миллиард','миллиард','миллиард',0),
            );
        }

        list($tenge,$tiyn) = explode('.',sprintf("%015.2f", floatval($num)));
        $out = array();
        if (intval($tenge)>0) {
            foreach(str_split($tenge,3) as $uk=>$v) {
            if (!intval($v)) continue;
            $uk = sizeof($unit)-$uk-1;
            $gender = $unit[$uk][3];
            list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
            
            $out[] = $hundred[$i1];
            if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3];
            else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3];
            if ($uk>1) $out[]= morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
            }
        }
        else $out[] = $nul;

        $out[] = morph(intval($tenge), $unit[1][0],$unit[1][1],$unit[1][2]);
        return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
    }
}

if (!function_exists('formatDate')) {
    function formatDate(string $date, string $format = 'Y-m-d'): string
    {
        return Carbon::parse($date)->format($format);
    }
}

if(!function_exists('addMonths')){
    function addMonths(string $date, int $num){
        $start_date = Carbon::parse($date);

        return $start_date->copy()->addMonths($num);
    }
}

if (!function_exists('monthFromAblative')) {
    function monthFromAblative(string $date, string $format = 'Y-m-d', ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $month  = Carbon::createFromFormat($format, $date)->month;

        // Русский (родительный падеж)
        if ($locale === 'ru') {
            $months = [
                1  => 'января',
                2  => 'февраля',
                3  => 'марта',
                4  => 'апреля',
                5  => 'мая',
                6  => 'июня',
                7  => 'июля',
                8  => 'августа',
                9  => 'сентября',
                10 => 'октября',
                11 => 'ноября',
                12 => 'декабря',
            ];

            return $months[$month];
        }

        // Казахский (шығыс септік)
        if ($locale === 'kk') {
            $months = [
                1  => 'қаңтардан',
                2  => 'ақпаннан',
                3  => 'наурыздан',
                4  => 'сәуірден',
                5  => 'мамырдан',
                6  => 'маусымнан',
                7  => 'шілдеден',
                8  => 'тамыздан',
                9  => 'қыркүйектен',
                10 => 'қазаннан',
                11 => 'қарашадан',
                12 => 'желтоқсаннан',
            ];

            return $months[$month];
        }

        // fallback (английский)
        return Carbon::createFromFormat($format, $date)
            ->locale($locale)
            ->translatedFormat('F');
    }
}

if (!function_exists('getColorName')) {
    function getColorName($color_id, $language_id)
    {
        return Color::find($color_id)
            ->colors_lang()
            ->where('lang_id', $language_id)
            ->value('color_name');
    }
}

if (!function_exists('getBankName')) {
    function getBankName($bank_id, $language_id)
    {
        return Bank::find($bank_id)
            ->banks_lang()
            ->where('lang_id', $language_id)
            ->value('bank_name');
    }
}

if (!function_exists('monthsWord')) {
    function monthsWord(int $number): string
    {
        $number = abs($number) % 100;
        $last = $number % 10;

        if ($number >= 11 && $number <= 19) {
            return 'месяцев';
        }

        if ($last === 1) {
            return 'месяц';
        }

        if ($last >= 2 && $last <= 4) {
            return 'месяца';
        }

        return 'месяцев';
    }
}

if (!function_exists('generateQr')) {
    function generateQr($text, $size, $margin) {
        return base64_encode(
            QrCode::format('png')->size($size)->margin($margin)->generate($text)
        );
    }
}
?>