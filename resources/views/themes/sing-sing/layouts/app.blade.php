<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Larchive') }} - Sing Sing Prison Museum</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Theme CSS -->
    @if(file_exists(public_path('themes/' . $activeTheme . '/theme.css')))
        <link rel="stylesheet" href="{{ asset('themes/' . $activeTheme . '/theme.css') }}">
    @endif
</head>
<body>
    {{-- Sing Sing Themed Header --}}
    <nav class="navbar navbar-expand-lg navbar-dark sing-sing-header mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                @if(file_exists(public_path('themes/sing-sing/logo.png')))
                    <img src="{{ asset('themes/sing-sing/logo.png') }}" alt="Sing Sing" height="40" class="me-2">
                @else
                    <i class="bi bi-building me-2" style="font-size: 1.5rem;"></i>
                @endif
                <span>{{ config('app.name', 'Sing Sing Prison Museum') }}</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('collections.index') }}">Collections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('items.index') }}">Items</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('exhibits.index') }}">Exhibits</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('export.index') }}">
                            <i class="bi bi-download"></i> Export
                        </a>
                    </li>
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i>
                                {{ Auth::user()->name }}
                                @if(Auth::user()->isAdmin())
                                    <span class="badge bg-danger">Admin</span>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <span class="dropdown-item-text">
                                        <small class="text-muted">{{ Auth::user()->email }}</small>
                                    </span>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                @if(Auth::user()->isAdmin())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.taxonomies.index') }}">
                                            <i class="bi bi-tags"></i> Taxonomies
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.settings.theme') }}">
                                            <i class="bi bi-palette"></i> Theme Settings
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.site-notice.edit') }}">
                                            <i class="bi bi-megaphone"></i> Site Notice
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-box-arrow-right"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        @include('partials.flash')

        @yield('content')
    </div>

    {{-- Sing Sing Themed Footer --}}
    <footer class="sing-sing-footer mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">
                        <strong>Sing Sing Prison Museum</strong><br>
                        <small>Preserving and sharing the history of one of America's most famous prisons.</small>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <small>&copy; {{ date('Y') }} Sing Sing Prison Museum. All rights reserved.</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Site Notice Modal -->
    @include('components.clickwrap-modal')

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <!-- HTMX CSRF Token Config -->
    <script>
        document.body.addEventListener('htmx:configRequest', (event) => {
            event.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        });
    </script>
</body>
</html>
