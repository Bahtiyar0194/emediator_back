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

        .main-ol {
            padding-left: 20px;
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
        <h3 style="text-align: center;">Соглашение<br>по результатам проведения процедуры медиации</h3>
        <table width="100%" style="margin-bottom: 20px">
            <tr>
                <td align="left"><b>{{ getLocation($data['location_id_1'], 2) }}</b></td>
                <td align="right">
                    <b>{{ $agreement->created_at->locale('ru')->translatedFormat('"d" F Y г.') }}</b>
                </td>
            </tr>
        </table>

        <div style="margin-bottom: 0px">
            @if ($data['is_legal_1'] === 'true')
                <b>{{ getShortLegalForm($data['legal_form_id_1'], 2) }} "{{ $data['company_name_1'] }}"</b>, БИН:
                <b>{{ $data['bin_1'] }}</b>, расположенное по адресу:
                <b>{{ getFullLocation($data['company_location_id_1'], 2) }}, ул. {{ $data['company_street_1'] }}, д.
                    {{ $data['company_building_1'] }}@if (isset($data['company_cabinet_1'])), кабинет {{ $data['company_cabinet_1'] }}@endif</b>, во главе <span
                    style="text-transform: lowercase">{{ getOrganizationPost($data['post_type_id_1'], 2) }}</span>
            @endif
            <b>{{ $data['last_name_1'] }} {{ $data['first_name_1'] }} @if (isset($data['given_name_1'])){{ $data['given_name_1'] }}@endif</b>, ИИН: <b>{{ $data['iin_1'] }}</b>,
            проживающий(-ая) по адресу: <b>{{ getFullLocation($data['location_id_1'], 2) }}, ул.
                {{ $data['street_1'] }}, д. {{ $data['house_1'] }}@if (isset($data['flat_1'])), кв. {{ $data['flat_1'] }}@endif</b>, именуемый(-ая) в дальнейшем "<b>Сторона-1</b>", с одной стороны и<br>
            @if ($data['is_legal_2'] === 'true')
                <b>{{ getShortLegalForm($data['legal_form_id_2'], 2) }} "{{ $data['company_name_2'] }}"</b>, БИН:
                <b>{{ $data['bin_2'] }}</b>, расположенное по адресу:
                <b>{{ getFullLocation($data['company_location_id_2'], 2) }}, ул. {{ $data['company_street_2'] }}, д. {{ $data['company_building_2'] }}@if(isset($data['company_cabinet_2'])), кабинет {{ $data['company_cabinet_2'] }}@endif</b>, во главе <span
                    style="text-transform: lowercase">{{ getOrganizationPost($data['post_type_id_2'], 2) }}</span>@endif
            <b>{{ $data['last_name_2'] }} {{ $data['first_name_2'] }} @if (isset($data['given_name_2'])){{ $data['given_name_2'] }}@endif</b>, ИИН: <b>{{ $data['iin_2'] }}</b>,
            проживающий(-ая) по адресу: <b>{{ getFullLocation($data['location_id_2'], 2) }}, ул.
                {{ $data['street_2'] }}, д. {{ $data['house_2'] }}@if (isset($data['flat_2'])), кв. {{ $data['flat_2'] }}@endif</b>, именуемый(-ая) в дальнейшем "<b>Сторона-2</b>", с другой стороны,
            совместно именуемые <b>"Стороны"</b>,<br>с участием профессионального медиатора общественного объединения
            <b>"{{ $agreement->association_name_full }}"</b> <b>{{ $agreement->mediator_last_name }} {{ $agreement->mediator_first_name }} @if(isset($agreement->mediator_given_name)){{ $agreement->mediator_given_name }}@endif</b>, действующий(-ая) на основании Сертификата <b>№ {{ $agreement->cert_num }} от
                {{ formatDate($agreement['cert_date'], 'd.m.Y') }}г.</b> заключили
            настоящее Соглашение о
            нижеследующем:
        </div>
    </header>

    <main>
        <ol class="main-ol">
        @yield('content')
    <!-- Подвал -->
        <li>Стороны подтверждают, что предоставленные ими в процессе медиации данные являются полными и точными. Если одна из
    Сторон умышленно предоставил(-а) ложные сведения, то данная Сторона несет полную ответственность за последствия,
    вызванные этой неверной информацией.</li>
    <li>Стороны подтверждают, что соглашение было достигнуто по их собственной воле. Они дееспособны, не находятся под
    воздействием наркотических, токсических или алкогольных веществ, а также не страдают заболеваниями, которые могли бы
    помешать им осознавать суть подписываемого документа. Стороны утверждают, что на момент подписания соглашения они не
    были введены в заблуждение, не подвергались обману или насилию.</li>
    <li>Настоящее соглашение действует с момента подписания и до момента надлежащего исполнения Сторонами своих
    обязательств.</li>
    <li>Настоящее соглашение является конфиденциальным, его содержание может быть раскрыто только для совершения
    определенных в соглашении, действий и в иных случаях, предусмотренных законом, если стороны не договорятся об
    ином.</li>

    <li style="color: red">Согласно п.1 ст. 27 Закона о медиации "Соглашение об урегулировании спора (конфликта), достигнутое сторонами
    медиации
    при проведении медиации, заключается в письменной форме и подписывается сторонами. Соглашение об урегулировании
    спора
    (конфликта) также имеет законную силу, если стороны обменялись подписанными цифровыми копиями соглашения посредством
    электронной почты без дальнейшего представления оригиналов друг другу".</li>
    
    <li>Согласно п.1 ст.10 Закона об электронном документе и электронной цифровой подписи РК, "Электронная цифровая
    подпись"
    равнозначна собственноручной подписи подписывающего лица и влечет одинаковые юридические последствия".</li>
    <li>
        <span style="text-decoration: underline">Стороны уведомлены, что согласно п.5 и п.6 ст. 640 Кодекса РК "Об
        административных правонарушениях" за
        незаконную
        передачу закрытого ключа электронной цифровой подписи другим лицам, а так же использование закрытого ключа
        электронной
        цифровой подписи другого лица, влечет административную ответственность.</span>
    </li>
        </ol>
    </main>

    <footer>

    </footer>

</body>

</html>
