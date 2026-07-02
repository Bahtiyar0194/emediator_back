    @if($signed === true)
        <table class="bordered">
            <tbody>
                <tr>
                    @foreach($parties as $k => $party)
                        <td style="padding: 10px">
                            @if ($party->is_mediator === 0)
                                @if($doctype === 'contract')
                                    <b>Сторона:</b>
                                @elseif($doctype === 'agreement')
                                    <b>Сторона-{{$k + 1}}:</b>
                                @endif
                            @else
                                <b>Медиатор:</b>
                            @endif
                            <br>
                            <br>
                            Ф.И.О: <b>{{ $party->last_name }} {{ $party->first_name }} @if (isset($party->given_name)){{ $party->given_name }}@endif</b>
                            <br>
                            ИИН: <b>{{ $party->iin }}</b>
                            <br>
                            <br>

                            @if(isset($party->data->attorney) && $party->data->attorney->includes === true && isset($party->data->attorney->person))
                            <b>Представитель стороны:</b>
                            <br>
                            Ф.И.О: <b>{{ $party->data->attorney->person->last_name }} {{ $party->data->attorney->person->first_name }} @if (isset($party->data->attorney->person->given_name)){{ $party->data->attorney->person->given_name }}@endif</b>
                            <br>
                            ИИН: <b>{{ $party->data->attorney->person->iin }}</b>
                            <br>
                            <br>
                            @endif

                            @if(isset($party->qr_text))
                                <img width="120" src="data:image/png;base64, {!! generateQr($party->qr_text, 500, 0) !!}">
                            @else
                                <b style="color: red">Не подписано</b>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif
    
    @if($document->status_type_id !== 11)
        <br>
        <br>
        <br>
        <table style="padding: 0px; margin: 0px">
            <tbody>
                <td>
                    <p>Бұл құжатқа E-Mediator жүйесі арқылы қол қойылды. Электрондық құжаттың түпнұсқасын көру үшін <a href="{{signedDocumentLink($document->uuid)}}"><u>мына сілтеме арқылы өтіңіз</u></a></p>
                    <p>Этот документ был подписан через систему E-Mediator. Для проверки подлинности документа <a href="{{signedDocumentLink($document->uuid)}}"><u>перейдите по данной ссылке</u></a></p>
                    <p>This document was signed through the E-Mediator system. To verify the authenticity of an electronic document, <a href="{{signedDocumentLink($document->uuid)}}"><u>click on the link</u></a></p>
                </td>
                <td style="padding-left: 20px">
                    <img width="120" src="data:image/png;base64, {!! generateQr(signedDocumentLink($document->uuid), 500, 0) !!}">
                </td>
            </tbody>
        </table>
    @endif