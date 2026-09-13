<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GMNHS Inventory | Admin Log in</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,600,700,800&display=fallback">
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
    <link rel="stylesheet" href="{{ asset('template/css/custom-style.css') }}">
    <link rel="shortcut icon" href="{{ asset('logo.png') }}">
</head>

<body class="hold-transition login-page modern-login-page">
    <main class="modern-login-shell">
        <section class="login-identity-panel" aria-label="Gil Montilla National High School">
            <div class="login-identity-top">
                <img src="{{ asset('logo.png') }}" alt="GMNHS logo" class="login-brand-logo">
                <div>
                    <span>Gil Montilla National High School</span>
                    <h1>GMNHS PPEI</h1>
                </div>
            </div>

            <div class="login-identity-copy">
                <span class="login-kicker">Administrator</span>
                <h2>Property, Plant, Equipment and Inventory</h2>
                <p>Secure inventory records for GMNHS property, offices, and accountable personnel.</p>
            </div>

            <div class="login-identity-footer">
                <span><i class="fas fa-user-shield"></i> Admin Access</span>
                <span><i class="fas fa-school"></i> Stand-alone School System</span>
            </div>
        </section>

        <section class="login-card-panel">
            <div class="login-card">
                <div class="login-card-header">
                    <span class="login-kicker">Secure Area</span>
                    <h2>Admin Sign In</h2>
                    <p>Access your GMNHS administrator workspace.</p>
                </div>

                <form action="{{ route('postLogin') }}" method="post">
                    @csrf

                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check"></i> {{ session('success') }}
                        </div>
                    @endif

                    <label for="username">Username</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="{{ old('username') }}" autofocus required autocomplete="username">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    @error('username')
                        <span class="form-text text-danger">{{ $message }}</span>
                    @enderror

                    <label for="myInput">Password</label>
                    <div class="input-group mb-2">
                        <input type="password" class="form-control" name="password" id="myInput" placeholder="Password" required autocomplete="current-password">
                        <div class="input-group-append">
                            <button type="button" class="input-group-text login-password-toggle" onclick="togglePassword()" aria-label="Show or hide password">
                                <span class="fas fa-eye" id="passwordToggleIcon"></span>
                            </button>
                        </div>
                    </div>
                    @error('password')
                        <span class="form-text text-danger">{{ $message }}</span>
                    @enderror

                    <div class="login-form-options">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember" value="1">
                            <label for="remember">Remember me</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block login-submit">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>

    <script>
        function togglePassword() {
            var input = document.getElementById('myInput');
            var icon = document.getElementById('passwordToggleIcon');
            var isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        }
    </script>
    <script>
        @if(auth()->check())
            window.location.href = "{{ route('dashboard') }}";
        @endif
    </script>
</body>
</html>
