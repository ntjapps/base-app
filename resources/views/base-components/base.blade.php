<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="cypress" content="Cypress Testing">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- <link rel="icon" type="image/webp" href="{{ Vite::asset('resources/images/icon.webp') }}"/> --}}

    <title>@yield('title', $pageTitle) - {{ config('app.name') }}</title>
    </head>

    <body>
    @section('base')
    @php
        $isIdStatic = str_starts_with(app()->getLocale(), 'id');
    @endphp

    <noscript>
        <main class="mx-auto max-w-[980px] p-5 font-sans leading-relaxed text-slate-900">
            @include('base-components.partials.static-site-content', ['isIdStatic' => $isIdStatic])
        </main>
    </noscript>

    <section
        id="seo-static-content"
        aria-label="SEO Static Content"
        class="sr-only"
    >
        <main>
            @include('base-components.partials.static-site-content', ['isIdStatic' => $isIdStatic])
        </main>
    </section>
    <div id="app">
        <main-app></main-app>
    </div>
    @show

    @section('font')
    @show

    @section('css')
        @vite(['resources/css/app.css'])
    @show

    @section('script')
        @vite(['resources/ts/app.ts'])

        {{-- Google Analytics --}}
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-PH2J98QCD3"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-PH2J98QCD3');
        </script>
    @show
    </body>
</html>