<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: "Roboto", sans-serif;
                font-size: 14px;
                text-align: justify
            }

            ol {
                counter-reset: item;
            }

            ol > li {
                display: block;
            }

            ol > li:before {
                content: counters(item, ".") ". ";
                counter-increment: item;
            }

            .main-ol > li{
                margin-bottom: 15px;
                text-align: center;
            }

            .main-ol > li > b{
                margin-bottom: 15px;
            }

            .main-ol{
                padding-left: 0px;
            }

            .main-ol > li > ol{
                padding-left: 0px;
            }

            .main-ol > li > ol > li{
                text-align: justify;
            }

            table.bordered {
                border-collapse: collapse;
                width: 100%;
            }

            table.bordered td, 
            table.bordered th {
                border: 1px solid #000;
                padding-left: 4px;
            }
        </style>
    </head>

    <body>
        <header>
            @if($doctype === 'contract')
                <h3 style="text-align: center;">ДОГОВОР<br>о проведении процедуры медиации</h3>
            @elseif($doctype === 'agreement')
                <h3 style="text-align: center;">СОГЛАШЕНИЕ<br>по результатам проведения процедуры медиации</h3>
            @endif
            <table width="100%" style="margin-bottom: 20px">
                <tr>
                    <td align="left"><b>{{ getLocation($parties[0]->data->location_id, 2) }}</b></td>
                    <td align="right">
                        <b>{{ $document->created_at->locale('ru')->translatedFormat('"d" F Y г.') }}</b>
                    </td>
                </tr>
            </table>

            <div style="margin-bottom: 0px">

                @if($doctype === 'contract')
                    @foreach($parties as $key => $party)
                        @if ($party['is_mediator'] === 0)
                            @if ($party->data->is_legal === true)
                                <b>{{ getShortLegalForm($party->data->legal_form_id, 2) }} "{{ $party->data->company_name }}"</b>, БИН:
                                <b>{{ $party->data->bin }}</b>, расположенное по адресу:
                                <b>{{ getFullLocation($party->data->company_location_id, 2) }}, ул. {{ $party->data->company_street }}, д.
                                    {{ $party->data->company_building }}@if (isset($party->data->company_cabinet)), кабинет {{ $party->data->company_cabinet }}@endif</b>, во главе <span
                                    style="text-transform: lowercase">{{ getOrganizationPost($party->data->post_type_id, 2) }}</span>
                            @endif
                            <b>{{ $party['last_name'] }} {{ $party['first_name'] }} @if (isset($party['given_name'])){{ $party['given_name'] }}@endif</b>, ИИН: <b>{{ $party['iin'] }}</b>,
                            проживающий(-ая) по адресу: <b>{{ getFullLocation($party->data->location_id, 2) }}, ул.
                            {{ $party->data->street }}, д. {{ $party->data->house }}@if (isset($party->data->flat)), кв. {{ $party->data->flat }}@endif</b>, именуемый(-ая) в дальнейшем <b>"Сторона"</b>, и<br>
                        @else
                        Профессиональный медиатор общественного объединения <b>"{{ $party->mediator->association_name_full }}"</b> <b>{{ $party['last_name'] }} {{ $party['first_name'] }} @if(isset($party['given_name'])){{ $party['given_name'] }}@endif</b>, именуемый(-ая) в дальнейшем <b>"Медиатор"</b>, действующий(-ая) на основании Сертификата <b>№ {{ $party->mediator->cert_num }} от {{ formatDate($party->mediator->cert_date, 'd.m.Y') }}г.</b> включенный в реестр профессиональных медиаторов, заключили настоящий Договор о нижеследующем:
                        @endif
                    @endforeach 
                @elseif($doctype === 'agreement')
                    @foreach($parties as $key => $party)
                        @if ($party['is_mediator'] === 0)
                            @if ($party->data->is_legal === true)
                                <b>{{ getShortLegalForm($party->data->legal_form_id, 2) }} "{{ $party->data->company_name }}"</b>, БИН:
                                <b>{{ $party->data->bin }}</b>, расположенное по адресу:
                                <b>{{ getFullLocation($party->data->company_location_id, 2) }}, ул. {{ $party->data->company_street }}, д.
                                    {{ $party->data->company_building }}@if (isset($party->data->company_cabinet)), кабинет {{ $party->data->company_cabinet }}@endif</b>, во главе <span
                                    style="text-transform: lowercase">{{ getOrganizationPost($party->data->post_type_id, 2) }}</span>
                            @endif
                            <b>{{ $party['last_name'] }} {{ $party['first_name'] }} @if (isset($party['given_name'])){{ $party['given_name'] }}@endif</b>, ИИН: <b>{{ $party['iin'] }}</b>,
                            проживающий(-ая) по адресу: <b>{{ getFullLocation($party->data->location_id, 2) }}, ул.
                            {{ $party->data->street }}, д. {{ $party->data->house }}@if (isset($party->data->flat)), кв. {{ $party->data->flat }}@endif</b>, именуемый(-ая) в дальнейшем <b>"Сторона-{{$key + 1}}"</b>, @if($key > 0) c другой стороны, @else с одной стороны и<br>@endif
                        @else
                            совместно именуемые <b>"Стороны"</b>,<br>с участием профессионального медиатора общественного объединения
                            <b>"{{ $party->mediator->association_name_full }}"</b> <b>{{ $party['last_name'] }} {{ $party['first_name'] }} @if(isset($party['given_name'])){{ $party['given_name'] }}@endif</b>, действующий(-ая) на основании Сертификата <b>№ {{ $party->mediator->cert_num }} от
                                {{ formatDate($party->mediator->cert_date, 'd.m.Y') }}г.</b> заключили настоящее Соглашение о нижеследующем:
                        @endif
                    @endforeach 
                @endif
            </div>
        </header>

        <main>
            @yield('document')
        </main>

        <footer>
            @if($signed === true)
                <table class="bordered">
                    <tbody>
                        <tr>
                            @foreach($parties as $k => $party)
                                <td style="padding: 10px">
                                    @if ($party['is_mediator'] === 0)
                                        @if($doctype === 'contract')
                                            <b>Сторона</b>
                                        @elseif($doctype === 'agreement')
                                            <b>Сторона-{{$k + 1}}</b>
                                        @endif
                                    @else
                                        <b>Медиатор</b>
                                    @endif
                                    <br>
                                    <br>
                                    ФИО: <b>{{ $party['last_name'] }} {{ $party['first_name'] }} @if (isset($party['given_name'])){{ $party['given_name'] }}@endif</b>
                                    <br>
                                    ИИН: <b>{{ $party['iin'] }}</b>
                                    <br>
                                    <br>
                                    @if(isset($party['sigex_sign']))
                                        <img src="data:image/png;base64, {!! generateQr(env('FRONTEND_URL').'/agreement/signs/'.$doctype.'/'.$document->uuid.'?sign_id='.$party->sigex_sign_id, 120, 0) !!}">
                                    @else
                                        <b style="color: red">Подпись ожидается</b>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            @endif
        </footer>
    </body>
</html>