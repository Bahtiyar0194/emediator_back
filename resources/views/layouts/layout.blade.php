<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <style>

            @page {
                margin: 15mm 15mm 15mm 25mm;
            }

            body {
                font-family: "Roboto", sans-serif;
                font-size: 14px;
                text-align: justify
            }

            ol {
                counter-reset: item;
                list-style-position: inside;
            }

            ol > li {
                display: block;
                margin-top: 10px !important;
            }

            ol > li:before {
                content: counters(item, ".") ". ";
                counter-increment: item;
            }

            /* Для договоров */
            .main-ol > li{
                text-align: center;
            }

            .main-ol > li > b{
                margin-top: 10px;
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

            p{
                margin-bottom: 5px !important;
                margin-top: 5px !important;
                text-indent: 3ch !important;
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

            .signs_stamp{
                position: fixed;
                bottom: 0mm;
                left: -20mm;
            }

            .signs_stamp > div{
                display: block;
                margin-top: 20px;
            }
        </style>
    </head>

    <body>
        @if($document->status_type_id !== 11)
        <div class="signs_stamp">
            @foreach($parties as $k => $party)
                @if(isset($party->qr_text))
                    <div>
                        <img width="60" src="data:image/png;base64, {!! generateQr($party->qr_text, 500, 0) !!}">
                    </div>
                @endif
            @endforeach

            <div>
                <img width="60" src="data:image/png;base64, {!! generateQr(signedDocumentLink($document->uuid), 500, 0) !!}">
            </div>
        </div>
        @endif

        <header>
            @include('layouts.parts.header')
        </header>

        <main>
            @yield('document')
        </main>

        <footer>
            @include('layouts.parts.footer')
        </footer>
    </body>
</html>