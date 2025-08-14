<header id="masthead" class="header ct-header-style-02">
    <div class="top-bar-main text-white clearfix">
        <div id="site-header-menu" class="site-header-menu">
            <div class="site-header-menu-inner ct-stickable-header">
                <div class="{{ $container }}">
                    <div class="row">
                        <div class="col">
                            <div class="site-navigation d-flex align-items-center">
                                <div class="site-branding">
                                    <a class="home-link" href="{{ route('home') }}" title="{{ config('app.name') }}"
                                        rel="home">
                                        <img id="logo-img" class="img-left standardlogo"
                                            src="{{ config('app.logo-alt', asset('images/logo-alt.png')) }}"
                                            alt="{{ config('app.name') }}">
                                        <img id="logo-dark" class="img-center stickylogo"
                                            src="{{ config('app.logo', asset('images/logo.png')) }}"
                                            alt="{{ config('app.name') }}">
                                    </a>
                                </div>
                                <div class="btn-show-menu-mobile menubar menubar--squeeze">
                                    <span class="menubar-box">
                                        <span class="menubar-inner"></span>
                                    </span>
                                </div>

                                <nav class="main-menu menu-mobile" id="menu">
                                    [menu id="{{ $menu }}"]
                                </nav>

                                <div
                                    class="header--extra text-white d-flex flex-row align-items-center justify-content-end ml-auto">
                                    @if ($ctalink)
                                        <a id="header--btn"
                                            class="btn btn--md btn--square btn--fill btn--primary pt-15 pb-15"
                                            href="{{ $ctalink }}">{{ $ctalabel }}</a>
                                    @else
                                        <div class="top_bar_contact_item top_bar_social">
                                            [socials class="d-flex"]
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
