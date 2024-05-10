<div class="textwidget widget-text">
    <div class="ct-pricelistbox-wrapper">
        <div class="ct-timelist-block-wrapper">
            <ul class="ct-timelist-block">
                @foreach ($opening_times as $item)
                    <li class="@selected($item['is_today'])">
                        {{ $item['name'] }}
                        @if ($item['is_today'])
                            (Today)
                        @endif
                        <span class="service-time">
                            {{ $item['open_at'] }} to {{ $item['close_at'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
