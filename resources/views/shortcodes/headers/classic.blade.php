<header id="masthead" class="header ct-header-style-04">
    <div class="ct-topbar-wrapper bg-darkgrey text-white clearfix">
        <div class="container">
            <ul class="list-inline top-contact  topbar-left text-left">
                <li>[phone]</li>
                <li>[email]</li>
            </ul>
            <div class="topbar-right text-right">
                <div class="ct-social-links-wrapper list-inline">
                    [socials]
                </div>
            </div>
        </div>
    </div><!-- ct-topbar-wrapper end -->
    <div id="site-header-menu" class="site-header-menu bg-white">
        <div class="site-header-menu-inner ct-stickable-header">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 ">
                        <!--site-navigation -->
                        <div class="site-navigation d-flex flex-row  justify-content-between align-items-center">
                            <!-- site-branding -->
                            <div class="site-branding ">
                                <a class="home-link" href="{{ route('home') }}" title="nitrofit28" rel="home">
                                    <img id="logo-img" height="45" width="175" class="img-fluid auto_size"
                                        src="{{ config('app.logo', asset('images/logo.png')) }}" alt="logo-img">
                                </a>
                            </div><!-- site-branding end -->
                            <div class="d-flex flex-row">
                                <div class="btn-show-menu-mobile menubar menubar--squeeze">
                                    <span class="menubar-box">
                                        <span class="menubar-inner"></span>
                                    </span>
                                </div>

                                <nav class="main-menu menu-mobile" id="menu">
                                    [menu id="menu-1"]
                                </nav>

                                <div class="header--extra d-flex flex-row align-items-center justify-content-end">
                                    <a id="header--btn"
                                        class="btn btn--md btn--square btn--fill btn--primary pt-15 pb-15"
                                        href="{{ $ctalink ?? '/contact' }}">{{ $ctalabel }}</a>
                                </div>
                            </div>
                        </div><!-- site-navigation end-->
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
