<footer class="bg-darkgrey text-white clearfix footer pt-30 widget-footer">
    <div class="ct-row-wrapper-bg-layer bg-layer"></div>
    <div class="second-footer">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-5 widget-area">
                    <div class="widget widget--text clearfix">
                        <div class="footer-logo">
                            <img id="footer-logo-img" class="img-center"
                                src="{{ config('app.logo-alt', asset('images/logo-alt.png')) }}"
                                alt="{{ config('app.name') }}">
                        </div>
                        <div class="textwidget widget-text">
                            <p class="pb-10 pr-30">{{ $desc }}</p>
                        </div>
                        <div class="order-3">
                            <div class="social-icons square">
                                [socials tooltip="true"]
                            </div>
                        </div>
                    </div>
                </div>
                <div class="widget-area col-xs-12 col-sm-6 col-md-6 col-lg-4">
                    <div class="widget widget--text clearfix">
                        <h3 class="widget--title">Get In Touch</h3>
                        <div class="textwidget widget-text">
                            <ul class="widget_contact_wrapper">
                                <li>[company-address]</li>
                                <li>[email]</li>
                                <li>[phone]</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="widget-area col-xs-12 col-sm-6 col-md-6 col-lg-3">
                    <div class="widget widget--nav-menu clearfix">
                        <h3 class="widget--title">Information</h3>
                        [menu id="menu-2"]
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bottom-footer-text copyright">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-md-flex justify-content-between">
                        <span>Copyright © {{ date('Y') }}&nbsp;<a href="/">{{ config('app.name') }}</a>.
                            All rights reserved.</span>
                        <ul class="footer-nav-menu">
                            <li><a href="/pages/terms">Terms & Conditions</a></li>
                            <li><a href="/pages/privacy">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<a id="totop" href="#top" class="top-visible" style="display: none;">
    <i class="fa fa-angle-up"></i>
</a>
