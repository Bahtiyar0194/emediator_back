@extends('layouts.agreement')

@section('content')
<li>
    Согласно @if ($data['basis_for_issuing_loan'] == 'receipt')
        расписки <b>от {{ formatDate($data['receipt_date'], 'd.m.Y') }}г</b>,
    @elseif($data['basis_for_issuing_loan'] == 'contract')
        договору займа за <b>№{{ $data['contract_num'] }} от
            {{ formatDate($data['contract_date'], 'd.m.Y') }}г</b>,
    @endif задолженность Стороны-1 перед
    Стороной-2 составляет <b>{{ $data['amount_of_debt'] }}
        ({{ numStr((int) str_replace(' ', '', $data['amount_of_debt']), 'ru') }}) тенге.</b>
</li>
<li>
    Стороны подтверждают, что представленная ими в процедуре медиации информация была полной и достоверной. В случае
    если
    одна из Сторон намеренно указала неверные сведения, то данная Сторона будет нести все ответственности за возникшие
    последствия по предоставленной неверной информации.
</li>
<li>
    Сторона-1 обязуется погасить задолженность перед Стороной-2 указанное в п. 1 настоящего соглашения, в следующем
порядке: <b>каждое {{ formatDate($data['repayment_start_date'], 'd') }} число месяца начиная с
    {{ monthFromAblative($data['repayment_start_date'], 'Y-m-d', 'ru') }}
    {{ formatDate($data['repayment_start_date'], 'Y') }} года ежемесячно до
    {{ formatDate(addMonths($data['repayment_start_date'], $data['repayment_period']), 'd') }} числа
    {{ monthFromAblative(addMonths($data['repayment_start_date'], $data['repayment_period']), 'Y-m-d H:i:s', 'ru') }}
    месяца {{ formatDate(addMonths($data['repayment_start_date'], $data['repayment_period']), 'Y') }} года в сумме
    {{ number_format((int) str_replace(' ', '', $data['amount_of_debt']) / $data['repayment_period'], 0, '.', ' ') }}
    ({{ numStr((int) str_replace(' ', '', $data['amount_of_debt']) / $data['repayment_period'], 'ru') }}) тенге безналичным перечислением по
    реквизиту: ИИК: {{ $data['iik'] }}, БИК: {{ $data['bik'] }} в АО "{{ $data['bank_name'] }}"</b>
принадлежащее Стороне-2.</li>
    <li>В случае неисполнения или частично не исполнения Стороной-1 своих обязательств, указанных в п. 3 настоящего
    Соглашения, в указанный срок, то Стороне-2 выдается исполнительный лист для принудительного исполнения путем
    взыскания
    всей суммы задолженности путем предъявления Стороной-2 соответствующего заявления в суд.</li>
@endsection
