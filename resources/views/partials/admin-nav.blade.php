@auth
    @php
        $isStaff = Auth::user()->isContributor() || Auth::user()->isCurator() || Auth::user()->isAdmin();
    @endphp

    @if($isStaff)
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
            <div class="container">
                @if(Route::has('admin.dashboard'))
                    <a class="navbar-brand me-3" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-shield-lock"></i>
                        Admin
                    </a>
                @else
                    <span class="navbar-brand me-3">
                        <i class="bi bi-shield-lock"></i>
                        Admin
                    </span>
                @endif

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="adminNav">
                    <ul class="navbar-nav me-auto">
                        @if(Auth::user()->isContributor())
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.items.workspace') }}">
                                    <i class="bi bi-briefcase"></i> Items Workspace
                                    @php
                                        $draftCount = \App\Models\Item::where('status', 'draft')->count();
                                    @endphp
                                    @if($draftCount > 0)
                                        <span class="badge bg-warning text-dark ms-1">{{ $draftCount }}</span>
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

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('export.index') }}">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </li>
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-globe"></i> View Site
                            </a>
                        </li>
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
