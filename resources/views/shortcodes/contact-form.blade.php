<form id="{{ $id }}" action="/contact" class="request_qoute_form wrap-form clearfix {{ $class }}"
    method="post" novalidate="novalidate">
    @csrf
    <input type="hidden" name="recaptcha_token" id="recaptcha_token">
    <div class="row ct-boxes-spacing-20px">
        <div class="col-md-6">
            <label>
                <span class="text-input">
                    <input class="@error('name') is-invalid @enderror" name="name" type="text" value=""
                        placeholder="Name" required="required">
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </span>
            </label>
        </div>
        <div class="col-md-6">
            <label>
                <span class="text-input">
                    <input class="@error('phone') is-invalid @enderror" name="phone" type="text" value=""
                        placeholder="Phone Number" required="required">
                    @error('phone')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
            </label>
        </div>
        <div class="col-md-12">
            <label>
                <span class="text-input">
                    <input class="@error('email') is-invalid @enderror" name="email" type="text" value=""
                        placeholder="Email" required="required">
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
            </label>
        </div>
        <div class="col-md-12">
            <label>
                <span class="text-input">
                    <textarea class="@error('message') is-invalid @enderror" name="message" rows="4"
                        placeholder="Leave a comment or query" required="required"></textarea>
                    @error('message')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </span>
            </label>
        </div>
        <div class="col-lg-12">
            <div class="pt-15 mb_20 text-center">
                <button id="submit" class="btn btn--md btn--shape-rounded btn--fill btn--primary z-index-1 w-100"
                    type="submit">SEND NOW!</button>
            </div>
        </div>
        <div class="col-lg-12">
            <div id="form-success" style="display: none" class="alert alert-success" role="alert">
                Message sent. We will contact you soon.
            </div>
        </div>
    </div>
</form>
