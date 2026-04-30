@foreach($points as $point)
    <li>
        {!! preg_replace('/^<p>(.*?)<\/p>/is', '$1', $point->content, 1) !!}

        @if(!empty($point->children))
            <ol>
                <x-point-item :points="$point->children" />
            </ol>
        @endif
    </li>
@endforeach