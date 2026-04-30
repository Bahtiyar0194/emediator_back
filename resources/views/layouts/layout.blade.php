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