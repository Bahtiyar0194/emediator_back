    @if($doctype === 'contract')
        <p style="text-align: center;"><b>ДОГОВОР<br>о проведении процедуры медиации</b></p>
    @elseif($doctype === 'agreement')
        <p style="text-align: center;"><b>СОГЛАШЕНИЕ<br>по результатам проведения процедуры медиации</b></p>
    @endif
    <table width="100%" style="margin-bottom: 20px">
        <tr>
            @if(isset($parties[count($parties) - 1]->data->location->id))
            <td align="left"><b>{{ getLocation($parties[count($parties) - 1]->data->location->id, 2) }}</b></td>
            @endif
            <td align="right">
                <b>{{ $document->updated_at->locale('ru')->translatedFormat('"d" F Y г.') }}</b>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 0px">

        @foreach($parties as $key => $party)
            <p style="text-indent: 22px">
                @if ($party->is_mediator === 0)
                    @if ($party->data->is_legal === true)
                        <b>{{ getShortLegalForm($party->data->legal_form_id, 2) }} "{{ $party->data->company_name }}"</b>, БИН:
                        <b>{{ $party->data->bin }}</b>, расположенное по адресу:
                        <b>{{ getFullLocation($party->data->company_location->id, 2) }}, @if($party->data->company_location->is_district === true && isset($party->data->company_location->village)) пос. {{ $party->data->company_location->village }}, @endif ул/мкр. {{ $party->data->company_location->street }} стр. {{ $party->data->company_location->building }}@if (isset($party->data->company_location->cabinet)) каб. {{ $party->data->company_location->cabinet }}@endif</b>, во главе <span
                            style="text-transform: lowercase">{{ getOrganizationPost($party->data->post_type_id, 2) }}</span>
                    @endif
                    <b>{{ $party->last_name }} {{ $party->first_name }} @if (isset($party->given_name)){{ $party->given_name }}@endif</b>, ИИН: <b>{{ $party->iin }}</b>,
                    проживающий(-ая) по адресу: <b>{{ getFullLocation($party->data->location->id, 2) }}, @if($party->data->location->is_district === true && isset($party->data->location->village)) пос. {{ $party->data->location->village }}, @endif ул/мкр.
                    {{ $party->data->location->street }} д. {{ $party->data->location->house }} @if(isset($party->data->location->flat))кв. {{ $party->data->location->flat }}@endif</b>, @if(isset($party->data->attorney) && $party->data->attorney->includes === true && isset($party->data->attorney->person)) а также представляющий(-ая) его(её) интересы на основании доверенности за № <b>{{ $party->data->attorney->num}}</b> от <b>{{ formatDate($party->data->attorney->date, 'd.m.Y') }} г.</b>, <b>{{ $party->data->attorney->person->last_name }} {{ $party->data->attorney->person->first_name }} @if (isset($party->data->attorney->person->given_name)){{ $party->data->attorney->person->given_name }}@endif</b>, ИИН: <b>{{ $party->data->attorney->person->iin }}</b>,
                    проживающий(-ая) по адресу: <b>{{ getFullLocation($party->data->attorney->person->data->location->id, 2) }}, @if($party->data->attorney->person->data->location->is_district === true && isset($party->data->attorney->person->data->location->village)) пос. {{ $party->data->attorney->person->data->location->village }}, @endif ул/мкр.
                    {{ $party->data->attorney->person->data->location->street }} д. {{ $party->data->attorney->person->data->location->house }} @if(isset($party->data->attorney->person->data->location->flat))кв. {{ $party->data->attorney->person->data->location->flat }}@endif</b>, @endif именуемый(-ая) в дальнейшем <b>"Сторона@if(count($parties) > 2)-{{$key + 1}}@endif"</b> @if($key > 0) c другой стороны, @if($key === (count($parties) - 2))совместно именуемые <b>"Стороны"</b>@else и @endif @else с одной стороны и @endif<br>
                @else
                    @if($doctype === 'contract')
                        Профессиональный медиатор общественного объединения <b>"{{ $party->mediator->association_name_full }}"</b> <b>{{ $party->last_name }} {{ $party->first_name }} @if(isset($party->given_name)){{ $party->given_name }}@endif</b>, именуемый(-ая) в дальнейшем <b>"Медиатор"</b>, действующий(-ая) на основании Сертификата <b>№ {{ $party->mediator->cert_num }} от {{ formatDate($party->mediator->cert_date, 'd.m.Y') }}г.</b>, включенный в реестр профессиональных медиаторов, заключили настоящий Договор о нижеследующем:
                    @elseif($doctype === 'agreement')
                        с участием профессионального медиатора общественного объединения <b>"{{ $party->mediator->association_name_full }}"</b> <b>{{ $party->last_name }} {{ $party->first_name }} @if(isset($party->given_name)){{ $party->given_name }}@endif</b>, действующий(-ая) на основании Сертификата <b>№ {{ $party->mediator->cert_num }} от {{ formatDate($party->mediator->cert_date, 'd.m.Y') }}г.</b>, заключили настоящее Соглашение о нижеследующем:
                    @endif
                @endif
            </p>
        @endforeach 
    </div>