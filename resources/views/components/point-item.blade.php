@foreach($points as $point)
    <li>
        {!! preg_replace(
            ['/^<p>(.*?)<\/p>/is', '/<table[^>]*>/is'], 
            ['$1', '<table class="bordered" style="margin-top: 10px">'], 
            $point->content, 
            1
        ) !!}

        @if(!empty($point->children))
            <ol>
                <x-point-item :points="$point->children" />
            </ol>
        @endif
    </li>
@endforeach