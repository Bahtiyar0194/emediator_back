    @if($signed === true)
        <table class="bordered">
            <tbody>
                <tr>
                    @foreach($parties as $k => $party)
                        <td style="padding: 10px">
                            @if ($party->is_mediator === 0)
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
                            ФИО: <b>{{ $party->last_name }} {{ $party->first_name }} @if (isset($party->given_name)){{ $party->given_name }}@endif</b>
                            <br>
                            ИИН: <b>{{ $party->iin }}</b>
                            <br>
                            <br>
                            @if(isset($party->sigex_sign))
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