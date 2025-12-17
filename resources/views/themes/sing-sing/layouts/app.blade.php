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
    <nav class="navbar navbar-light sing-sing-header mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                @if(file_exists(public_path('themes/sing-sing/logo.png')))
                    <img src="{{ asset('themes/sing-sing/logo.png') }}" alt="Sing Sing" height="80" class="me-2">
                @else
                    <i class="bi bi-building me-2" style="font-size: 1.5rem;"></i>
                @endif
            </a>
            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="offcanvas" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    {{-- Full Page Overlay Nav --}}
    <div class="offcanvas offcanvas-end w-100" tabindex="-1" id="navMenu" aria-labelledby="navMenuLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="navMenuLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav fs-4">
                <li class="nav-item py-2">
                    <a class="nav-link" href="{{ route('collections.index') }}" data-bs-dismiss="offcanvas">Collections</a>
                </li>
                <li class="nav-item py-2">
                    <a class="nav-link" href="{{ route('items.index') }}" data-bs-dismiss="offcanvas">Items</a>
                </li>
                <li class="nav-item py-2">
                    <a class="nav-link" href="{{ route('exhibits.index') }}" data-bs-dismiss="offcanvas">Exhibits</a>
                </li>
                <li class="nav-item py-2">
                    <a class="nav-link" href="{{ route('export.index') }}" data-bs-dismiss="offcanvas">
                        <i class="bi bi-download"></i> Export
                    </a>
                </li>
                @auth
                    <li class="nav-item py-2">
                        <hr class="my-3">
                        <div class="text-muted small mb-2">
                            <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                            @if(Auth::user()->isAdmin())
                                <span class="badge bg-danger">Admin</span>
                            @endif
                            <div class="small text-muted">{{ Auth::user()->email }}</div>
                        </div>
                    </li>
                    @if(Auth::user()->isAdmin())
                        <li class="nav-item py-2">
                            <a class="nav-link" href="{{ route('admin.taxonomies.index') }}" data-bs-dismiss="offcanvas">
                                <i class="bi bi-tags"></i> Taxonomies
                            </a>
                        </li>
                        <li class="nav-item py-2">
                            <a class="nav-link" href="{{ route('admin.settings.theme') }}" data-bs-dismiss="offcanvas">
                                <i class="bi bi-palette"></i> Theme Settings
                            </a>
                        </li>
                        <li class="nav-item py-2">
                            <a class="nav-link" href="{{ route('admin.site-notice.edit') }}" data-bs-dismiss="offcanvas">
                                <i class="bi bi-megaphone"></i> Site Notice
                            </a>
                        </li>
                    @endif
                    <li class="nav-item py-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link text-start p-0">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item py-2">
                        <a class="nav-link" href="{{ route('login') }}" data-bs-dismiss="offcanvas">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>

    <div class="container-fluid">
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
