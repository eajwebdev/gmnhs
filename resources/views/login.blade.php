<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · GMNHS Property Registry</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&family=Schibsted+Grotesk:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
    <link rel="stylesheet" href="{{ asset('template/css/custom-style.css') }}?v={{ @filemtime(public_path('template/css/custom-style.css')) }}">
    <link rel="shortcut icon" href="{{ asset('logo.png') }}">
</head>

<body class="hold-transition">
@php
    $loginError = session('error') ?: $errors->first('username') ?: $errors->first('password');
@endphp
<main class="login-screen">
    <section class="login-ledger" aria-label="Gil Montilla National High School">
        <div class="login-seal">
            <img src="{{ asset('logo.png') }}" alt="Gil Montilla National High School seal">
            <div>
                <b>Gil Montilla National High School</b>
                <span>Sipalay City · Negros Occidental</span>
            </div>
        </div>

        <div class="login-statement">
            <div class="eyebrow">Property, Plant, Equipment &amp; Inventory</div>
            <h1>Every piece of school property, <em>accounted for.</em></h1>
            <p>Record acquisitions, issue property to accountable persons, print QR stickers and prepare RPCPPE, ICS and PAR reports from one registry.</p>

            <div class="login-tag" aria-hidden="true">
                <div class="login-tag-hole"></div>
                <div class="login-tag-body">
                    <small>Property No.</small>
                    <strong>1-07-05-030-0001</strong>
                    <div><span>ICT Equipment</span><span>GMNHS</span></div>
                </div>
            </div>
        </div>

        <div class="login-ledger-foot">
            <span>Supply &amp; Property Office</span>
            <span>MIS Office</span>
        </div>
    </section>

    <section class="login-panel">
        <div class="login-form-wrap">
            <div class="eyebrow">Authorized personnel</div>
            <h2>Sign in to the registry</h2>
            <p class="lead-copy">Use the account issued to you by the system administrator.</p>

            @if($loginError)
                <div class="login-error" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $loginError === 'Invalid Credentials' ? 'That username and password don’t match an account.' : $loginError }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('postLogin') }}" method="POST" id="loginForm">
                @csrf

                <div class="login-field">
                    <label for="username">Username</label>
                    <div class="login-input">
                        <input type="text" class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}" id="username" name="username"
                            value="{{ old('username') }}" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <div class="login-input">
                        <input type="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" id="password" name="password"
                            autocomplete="current-password" required>
                        <i class="fas fa-lock"></i>
                        <button type="button" class="login-reveal" id="passwordReveal" aria-label="Show password" aria-pressed="false">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="login-caps" id="capsWarning"><i class="fas fa-triangle-exclamation"></i> Caps Lock is on</div>
                </div>

                <div class="login-row">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remember" name="remember" value="1" @checked(old('remember'))>
                        <label class="custom-control-label" for="remember">Keep me signed in</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary login-submit" id="loginSubmit">
                    <span>Sign in</span> <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <p class="login-note">
                Forgot your password? Ask your system administrator to set a new one from <strong>Users</strong>.
                Accounts are for school personnel only.
            </p>
        </div>

        <div class="login-panel-foot">&copy; {{ now()->year }} GMNHS · Management Information System Office</div>
    </section>
</main>

<script>
    @if(auth()->check())
        window.location.href = "{{ route('dashboard') }}";
    @endif

    (function () {
        var password = document.getElementById('password');
        var reveal = document.getElementById('passwordReveal');
        var caps = document.getElementById('capsWarning');
        var form = document.getElementById('loginForm');
        var submit = document.getElementById('loginSubmit');

        reveal.addEventListener('click', function () {
            var show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            reveal.setAttribute('aria-pressed', show);
            reveal.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            reveal.innerHTML = show ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            password.focus();
        });

        ['keydown', 'keyup'].forEach(function (evt) {
            password.addEventListener(evt, function (e) {
                if (e.getModifierState) {
                    caps.classList.toggle('is-on', e.getModifierState('CapsLock'));
                }
            });
        });

        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.innerHTML = '<span>Signing in…</span>';
        });
    })();
</script>
</body>
</html>
