<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="{{ asset('installer/img/favicon/favicon.ico') }}">

    <title>License Manager</title>
    <!-- Bootstrap core CSS -->
    <link href="https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            display: -ms-flexbox;
            display: -webkit-box;
            display: flex;
            -ms-flex-align: center;
            -ms-flex-pack: center;
            -webkit-box-align: center;
            align-items: center;
            -webkit-box-pack: center;
            justify-content: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .form-container {
            width: 450px;
            max-width: 95vw;
            padding: 25px;
            margin: 0 auto;
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <form method="post" action="{{ route('license-update') }}" class="form-container">
        @csrf
        <div class="text-center form-group">
            <img class="mb-4" src="{{ asset('installer/img/favicon/favicon.ico') }}" alt="" width="72">
            <h1 class="h4 mb-3 font-weight-normal">License Manager</h1>
            <p>Manage and Update Your Application License.</p>
        </div>
        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" value="{{ old('email') }}" name="email" class="form-control" id="email"
                aria-describedby="emailHelp" placeholder="admin@example.com">
            @error('email')
                <small id="emailHelp" class="form-text text-danger">{{ $message }}</small>
            @else
                <small id="emailHelp" class="form-text text-muted">Your admin login email adrress</small>
            @enderror
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input name="password" type="password" class="form-control" id="password" placeholder="Password">
            @error('password')
                <small id="passwordHelp" class="form-text text-danger">{{ $message }}</small>
            @else
                <small id="passwordHelp" class="form-text text-muted">Your admin login password</small>
            @enderror
        </div>
        <div class="form-group">
            <label for="license">License key</label>
            <input name=license value="{{ old('license') }}" class="form-control" id="license"
                placeholder="7|Inqgp67S5AyLc6Epe73KK8d4OHyTkIHiSBrmUay0">
            @error('license')
                <small id="passwordHelpError" class="form-text text-danger">{{ $message }}</small>
            @enderror
            <small id="licenseHelp" class="form-text text-muted">To obtain or renew your license, please <a
                    href="http://coderstm.com">visit our website</a> or
                contact our support team at <a href="mailto:hello@coderstm.com">hello@coderstm.com</a></small>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Update</button>
    </form>
</body>

</html>
