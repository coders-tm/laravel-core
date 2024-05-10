<ul class="social-icons {{ $class }}">
    @foreach ($socials as $item)
        <li>
            @if ($tooltip)
                <a class="tooltip-top" href="{{ $item['href'] }}" rel="noopener" data-tooltip="{{ $item['name'] }}">
                    <i class="{{ $item['icon'] }}"></i>
                </a>
            @else
                <a href="{{ $item['href'] }}" rel="noopener"><i class="{{ $item['icon'] }}"></i></a>
            @endif
        </li>
    @endforeach
</ul>
