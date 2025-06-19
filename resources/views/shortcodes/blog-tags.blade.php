<aside class="widget widget--tagcloud with-title">
    <h3 class="widget--title">Tags</h3>
    <div class="tagcloud">
        @foreach ($tags as $tag)
            <a href="javascript:void(0);" class="tag-cloud-link">{{ $tag->label }}</a>
        @endforeach
    </div>
</aside>
