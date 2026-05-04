    @if($doctype === 'contract')
        <p style="text-align: center;"><b>ДОГОВОР<br>о проведении процедуры медиации</b></p>
    @elseif($doctype === 'agreement')
        <p style="text-align: center;"><b>СОГЛАШЕНИЕ<br>по результатам проведения процедуры медиации</b></p>
    @endif
    <table width="100%" style="margin-bottom: 20px">
        <tr>
            <td align="left"><b>{{ getLocation($parties[0]->data->location_id, 2) }}</b></td>
            <td align="right">
                <b>{{ $document->updated_at->locale('ru')->translatedFormat('"d" F Y г.') }}</b>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 0px">

        @if($doctype === 'contract')
            @foreach($parties as $key => $party)
                <p style="text-indent: 22px">
                    @if ($party->is_mediator === 0)
                        @if ($party->data->is_legal === true)
                            <b>{{ getShortLegalForm($party->data->legal_form_id, 2) }} "{{ $party->data->company_name }}"</b>, БИН:
                            <b>{{ $party->data->bin }}</b>, расположенное по адресу:
                            <b>{{ getFullLocation($party->data->company_location_id, 2) }}, ул. {{ $party->data->company_street }}, д.
                                {{ $party->data->company_building }}@if (isset($party->data->company_cabinet)), кабинет {{ $party->data->company_cabinet }}@endif</b>, во главе <span
                                style="text-transform: lowercase">{{ getOrganizationPost($party->data->post_type_id, 2) }}</span>
                        @endif
                        <b>{{ $party->last_name }} {{ $party->first_name }} @if (isset($party->given_name)){{ $party->given_name }}@endif</b>, ИИН: <b>{{ $party->iin }}</b>,
                        проживающий(-ая) по адресу: <b>{{ getFullLocation($party->data->location_id, 2) }}, ул.
                        {{ $party->data->street }}, д. {{ $party->data->house }}@if (isset($party->data->flat)), кв. {{ $party->data->flat }}@endif</b>, именуемый(-ая) в дальнейшем <b>"Сторона"</b>, и<br>
                    @else
                    Профессиональный медиатор общественного объединения <b>"{{ $party->mediator->association_name_full }}"</b> <b>{{ $party->last_name }} {{ $party->first_name }} @if(isset($party->given_name)){{ $party->given_name }}@endif</b>, именуемый(-ая) в дальнейшем <b>"Медиатор"</b>, действующий(-ая) на основании Сертификата <b>№ {{ $party->mediator->cert_num }} от {{ formatDate($party->mediator->cert_date, 'd.m.Y') }}г.</b> включенный в реестр профессиональных медиаторов, заключили настоящий Договор о нижеследующем:
                    @endif
                </p>
            @endforeach 
        @elseif($doctype === 'agreement')
            @foreach($parties as $key => $party)
                <p style="text-indent: 22px">
                    @if ($party->is_mediator === 0)
                        @if ($party->data->is_legal === true)
                            <b>{{ getShortLegalForm($party->data->legal_form_id, 2) }} "{{ $party->data->company_name }}"</b>, БИН:
                            <b>{{ $party->data->bin }}</b>, расположенное по адресу:
                            <b>{{ getFullLocation($party->data->company_location_id, 2) }}, ул. {{ $party->data->company_street }}, д.
                                {{ $party->data->company_building }}@if (isset($party->data->company_cabinet)), кабинет {{ $party->data->company_cabinet }}@endif</b>, во главе <span
                                style="text-transform: lowercase">{{ getOrganizationPost($party->data->post_type_id, 2) }}</span>
                        @endif
                        <b>{{ $party->last_name }} {{ $party->first_name }} @if (isset($party->given_name)){{ $party->given_name }}@endif</b>, ИИН: <b>{{ $party->iin }}</b>,
                        проживающий(-ая) по адресу: <b>{{ getFullLocation($party->data->location_id, 2) }}, ул.
                        {{ $party->data->street }}, д. {{ $party->data->house }}@if (isset($party->data->flat)), кв. {{ $party->data->flat }}@endif</b>, именуемый(-ая) в дальнейшем <b>"Сторона-{{$key + 1}}"</b>, @if($key > 0) c другой стороны, совместно именуемые <b>"Стороны"</b>, @else с одной стороны и @endif
                    @else
                        с участием профессионального медиатора общественного объединения
                        <b>"{{ $party->mediator->association_name_full }}"</b> <b>{{ $party->last_name }} {{ $party->first_name }} @if(isset($party->given_name)){{ $party->given_name }}@endif</b>, действующий(-ая) на основании Сертификата <b>№ {{ $party->mediator->cert_num }} от
                            {{ formatDate($party->mediator->cert_date, 'd.m.Y') }}г.</b> заключили настоящее Соглашение о нижеследующем:
                    @endif
                </p>
            @endforeach 
        @endif
    </div>