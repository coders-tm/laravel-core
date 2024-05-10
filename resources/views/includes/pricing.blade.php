<div class="ct-pricing-plan" data-plan="{{ json_encode($plan) }}">
    <div class="ct-plan-head">
        <div class="ct-plan-icon">
            <img style="width: 110px" src="{{ config('app.logo', asset('images/logo.png')) }}">
        </div>
        <div class="ct-plan-title">
            <h3>{{ $label }}
                @if ($monthly_fee > 0)
                    <span>*</span>
                @endif
            </h3>
        </div>
        <div class="ct-plan-box-desc"></div>
    </div>
    <ul class="list list--icon color-darkgrey pt-15 pb-15">
        @foreach ($feature_lines as $feature)
            <li>
                <span class="list--content text-left">{!! $feature !!}</span>
            </li>
        @endforeach
    </ul>
    <div class="ct-plan-amount @if ($monthly_fee <= 0) visibility-hidden @endif">
        <span class="cur_symbol">{{ $cur_symbol }}</span>
        <span class="ct-ptablebox-price month">{{ $monthly_fee }}</span>
        <span style="display: none" class="ct-ptablebox-price year">{{ $yearly_fee }}</span>
        <span class="pac_frequency month">/Per Month</span>
        <span style="display: none" class="pac_frequency year">/Per Year</span>
    </div>
    <div class="ct-plan-footer">
        <form action="{{ app_url('sign-up') }}" method="get">
            <input type="hidden" name="plan" value="{{ $id }}">
            <input class="plan-interval" type="hidden" name="interval" value="month">
            <button
                class="btn btn--md btn--square btn--fill btn--icon-right btn--primary text-center margin_top30 z-index-1">Signup</button>
        </form>
    </div>
</div>
