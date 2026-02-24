@extends('layouts.agreement')

@section('content')
<p>Стороны завершили процедуру медиации по спору: о выплате алиментов.</p>
<li>В период брака у Сторон @if (count($data['agreement_data']['children']) > 1)имеются общие несовершеннолетние дети: 
    <ul> 
        @foreach($data['agreement_data']['children'] as $child)
            <li><b>{{ $child['last_name'] }} {{ $child['first_name'] }} @if(isset($child['given_name'])){{ $child['given_name'] }}@endif, {{ formatDate($child['birth_date'], 'd.m.Y') }} года рождения{{ $loop->last ? '.' : ';' }}</b></li>
        @endforeach
    <ul>
    @else имеется общий несовершеннолетний ребенок: <b>{{$data['agreement_data']['children'][0]['last_name']}} {{$data['agreement_data']['children'][0]['first_name']}} @if (isset($data['agreement_data']['children'][0]['given_name'])) {{ $data['agreement_data']['children'][0]['given_name'] }}, @endif {{ formatDate($data['agreement_data']['children'][0]['birth_date'], 'd.m.Y') }} года рождения.</b>@endif
</li>
<li>Сторона-2 обязуется ежемесячно <b>до {{ formatDate($data['agreement_data']['start_payment_date'], 'd') }} числа, начиная с {{ monthFromAblative($data['agreement_data']['start_payment_date'], 'Y-m-d', 'ru') }} месяца {{ formatDate($data['agreement_data']['start_payment_date'], 'Y') }} года,</b> выплачивать Стороне-1 денежное средство в сумме <b>{{ $data['agreement_data']['monthly_amount'] }} ({{ numStr((int) str_replace(' ', '', $data['agreement_data']['monthly_amount']), 'ru') }}) тенге</b> как алиментную выплату на содержание @if(count($data['agreement_data']['children']) > 1) общих несовершеннолетних детей, указанных @else общего несовершеннолетего ребенка, указанное @endif в п.1 настоящего соглашения, до достижения @if(count($data['agreement_data']['children']) > 1){{'их'}}@else{{'его (её)'}}@endif совершеннолетнего возраста путем перевода <b style="color: red">на счет АО «Каспий банк»</b> на имя Стороны-1.</li>
<li>Сторона-1, в свою очередь, обязуется не предпринимать в дальнейшем никакие меры по взысканию алиментов со Стороны-2, в случае если Стороной-2 будут соблюдены обязательства указанные в п.2 настоящего соглашения.</li>
<li>В случае если Сторона-2 окажется в затруднительном положении по оплате алиментов указанное в п.2 настоящего соглашения по причине (ухудшение здоровья, ДТП и иные форс-мажорные обстоятельства), то Сторона-1 дает право Стороне-2 отсрочить оплату до одного месяца.</li>
<li>В случае неисполнения «Стороной-2», своих обязательств указанных в п. 2. настоящего Соглашения, в указанный срок, то «Стороне-2» выдается исполнительный лист для принудительного исполнения путем взыскания алиментов путем предъявления «Стороной-1» соответствующего заявления в суд.</li>
<li>Сторона-2 в любое удобное для обеих Сторон время, вправе видеть @if(count($data['agreement_data']['children']) > 1) своих несовершеннолетних детей,@else своего несовершеннолетнего ребенка @endif где Сторона-1 не вправе препятствовать в этом.</li>
@endsection