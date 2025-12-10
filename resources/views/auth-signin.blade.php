@extends('partials.Layouts.master-auth')

@section('title', 'Sign In | Herozi - The Worlds Best Selling Bootstrap Admin & Dashboard Template by SRBThemes')

@section('css')
    @include('partials.head-css', ['auth' => 'layout-auth'])
@endsection

@section('content')

    <!-- START -->
    <div class="account-pages">
        <img src="{{ asset('assets/images/auth/auth_bg.jpeg') }}" alt="auth_bg" class="auth-bg light">
        <img src="{{ asset('assets/images/auth/auth_bg_dark.jpg') }}" alt="auth_bg_dark" class="auth-bg dark">
        <div class="container">
            <div class="justify-content-center row gy-0">

                <div class="col-lg-6 auth-banners">
                    <div class="bg-login card card-body m-0 h-100 border-0">
                        <img src="{{ asset('assets/images/auth/bg-img-2.png') }}" class="img-fluid auth-banner"
                            alt="auth-banner">
                        <div class="auth-contain">
                            <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0"
                                        class="active" aria-current="true" aria-label="Slide 1"></button>
                                    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1"
                                        aria-label="Slide 2"></button>
                                    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2"
                                        aria-label="Slide 3"></button>
                                </div>
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <div class="text-center text-white my-4 p-4">
                                            <h3 class="text-white">Learn and Practice</h3>
                                            <p class="mt-3">Manage your application seamlessly. Log in to access your
                                                dashboard and configure settings.</p>
                                        </div>
                                    </div>
                                    <div class="carousel-item">
                                        <div class="text-center text-white my-4 p-4">
                                            <h3 class="text-white">Secure Your Data</h3>
                                            <p class="mt-3">Ensure your application remains secure. Utilize our tools to
                                                monitor and protect your data effectively.</p>
                                        </div>
                                    </div>
                                    <div class="carousel-item">
                                        <div class="text-center text-white my-4 p-4">
                                            <h3 class="text-white">User Management</h3>
                                            <p class="mt-3">Easily manage users, roles, and permissions. Streamline your
                                                admin tasks with our intuitive interface.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="auth-box card card-body m-0 h-100 border-0 justify-content-center">
                        <div class="mb-5 text-center">
                            <h4 class="fw-normal">مرحباً بك في <span class="fw-bold text-primary">لوحة التحكم</span></h4>
                            <p class="text-muted mb-0">يرجى إدخال معلوماتك للوصول إلى حسابك.</p>
                        </div>
                        <form class="form-custom mt-10" method="POST" action="{{ route('login') }}" id="login-form">
                            @csrf

                            @if ($errors->any())
                                <div class="alert alert-danger mb-4">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger mb-4">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <div class="mb-5">
                                <label class="form-label" for="login-phone">رقم الهاتف<span class="text-danger ms-1">*</span>
                                </label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="login-phone" name="phone" value="{{ old('phone') }}" placeholder="أدخل رقم الهاتف" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-5">
                                <label class="form-label" for="LoginPassword">كلمة المرور<span
                                        class="text-danger ms-1">*</span></label>
                                <div class="input-group">
                                    <input type="password" id="LoginPassword" class="form-control @error('password') is-invalid @enderror" name="password"
                                        placeholder="أدخل كلمة المرور" data-visible="false" required>
                                    <a class="input-group-text bg-transparent toggle-password" href="javascript:;"
                                        data-target="password">
                                        <i class="ri-eye-off-line text-muted toggle-icon"></i>
                                    </a>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-5">
                                <div class="col-sm-6">
                                    <div class="form-check form-check-sm d-flex align-items-center gap-2 mb-0">
                                        <input class="form-check-input" type="checkbox" name="remember" value="1"
                                            id="remember-me">
                                        <label class="form-check-label" for="remember-me">
                                            تذكرني
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary rounded-2 w-100" id="login-btn">
                                <span class="indicator-label">
                                    تسجيل الدخول
                                </span>
                                <span class="indicator-progress flex gap-2 justify-content-center w-100" style="display: none;">
                                    <span>جاري التحقق...</span>
                                    <i class="ri-loader-2-fill"></i>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            const loginBtn = document.getElementById('login-btn');

            if (loginForm && loginBtn) {
                loginForm.addEventListener('submit', function(e) {
                    // Don't prevent default - let form submit normally
                    const indicatorLabel = loginBtn.querySelector('.indicator-label');
                    const indicatorProgress = loginBtn.querySelector('.indicator-progress');

                    if (indicatorLabel && indicatorProgress) {
                        // Show loading state
                        indicatorLabel.style.display = 'none';
                        indicatorProgress.style.display = 'flex';
                        loginBtn.disabled = true;
                    }
                });
            }
        });
    </script>
    <!-- App js -->
    <script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@endsection
