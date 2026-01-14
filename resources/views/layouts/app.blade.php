<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Larchive') }}</title>
    
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
    @auth
        @if(Auth::user()->isContributor())
            {{-- Admin/Contributor Top Bar --}}
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle"></i>
                        {{ Auth::user()->name }}
                        @if(Auth::user()->isAdmin())
                            <span class="badge bg-danger">Admin

                            </span>
                        @endif
                    </span>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="adminNav">
                        <ul class="navbar-nav mx-auto">
                            @if(Auth::user()->isContributor())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.items.workspace') }}">
                                        <i class="bi bi-briefcase"></i> Items Workspace
                                        @php
                                            $draftCount = \App\Models\Item::where('status', 'draft')->count();
                                        @endphp
                                        @if($draftCount > 0)
                                            <span class="badge bg-warning text-dark">{{ $draftCount }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endif

                            @if(Auth::user()->isAdmin())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.users.index') }}">
                                        <i class="bi bi-people"></i> Users
                                    </a>
                                </li>
                            @endif
 
                            @if(Auth::user()->isAdmin())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.taxonomies.index') }}">
                                        <i class="bi bi-tags"></i> Taxonomies
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.site-notice.edit') }}">
                                        <i class="bi bi-megaphone"></i> Site Notice
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.settings.theme') }}">
                                        <i class="bi bi-palette"></i> Theme Settings
                                    </a>
                                </li>
                            @endif
                        </ul>
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('profile.show') }}">
                                    <i class="bi bi-person-badge"></i> My Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link nav-link">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        @endif
    @endauth

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/">{{ config('app.name', 'Larchive') }}</a>
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
                        @if(!Auth::user()->isContributor())
                            {{-- Regular users see simple dropdown --}}
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle"></i>
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <span class="dropdown-item-text">
                                            <small class="text-muted">{{ Auth::user()->email }}</small>
                                        </span>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a href="{{ route('profile.show') }}" class="dropdown-item">
                                            <i class="bi bi-person-badge"></i> My Profile
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
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
                        @endif
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

    <!-- Site Notice Modal -->
    @include('components.clickwrap-modal')

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SortableJS for drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
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
