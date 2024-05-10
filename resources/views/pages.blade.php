<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="{{ $meta_keywords ?? '' }}" />
    <meta name="description" content="{{ $meta_description ?? '' }}" />
    <meta name="author" content="{{ $url ?? config('app.url') }}" />
    <meta name="viewport" content=" width=device-width, initial-scale=1" />
    <title>
        {{ $meta_title ?? $title . ' | ' . config('app.name') }}
    </title>

    {{-- Disable Laravel Routes from Being Indexed on Google --}}
    @if (config('app.env') == 'local')
        <meta name="robots" content="noindex">
        <meta name="googlebot" content="noindex">
    @endif

    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.coderstm.com">
    <link rel="stylesheet" type="text/css" href="https://cdn.coderstm.com/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="{{ asset('statics/js/fullcalendar/main.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="https://cdn.coderstm.com/gimmer/css/styles.min.css" />
    <link rel="stylesheet" type="text/css" href="{{ asset('statics/css/styles.css') }}" />
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap-grid.min.css" />
    <style type="text/css">
        {!! $styles ?? '' !!}
    </style>
</head>

{!! $body ?? '' !!}

</html>
