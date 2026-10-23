@extends('admin.layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-palette"></i> Site Theme Settings
                </h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('admin.settings.theme.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="theme" class="form-label">
                            Active Theme <span class="text-danger">*</span>
                        </label>
                        <select 
                            class="form-select @error('theme') is-invalid @enderror" 
                            id="theme" 
                            name="theme" 
                            required
                        >
                            @foreach($availableThemes as $key => $themeInfo)
                                <option value="{{ $key }}" {{ $activeTheme === $key ? 'selected' : '' }}>
                                    {{ $themeInfo['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('theme')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-light border">
                        <h6 class="alert-heading">Available Themes</h6>
                        <div class="list-group list-group-flush">
                            @foreach($availableThemes as $key => $themeInfo)
                                <div class="list-group-item px-0">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                {{ $themeInfo['name'] }}
                                                @if($activeTheme === $key)
                                                    <span class="badge bg-success">Active</span>
                                                @endif
                                            </h6>
                                            <p class="mb-1 text-muted small">{{ $themeInfo['description'] }}</p>
                                            <small class="text-muted">
                                                <code>{{ $key }}</code>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    {{-- Show file paths for developers --}}
                                    <div class="mt-2">
                                        @php
                                            $viewPaths = \App\Support\Theme::getViewPaths($key);
                                            $cssPath = \App\Support\Theme::cssPath($key);
                                        @endphp

                                        <small class="text-muted d-block">
                                            <i class="bi bi-folder"></i> 
                                            Views: 
                                            @if(count($viewPaths))
                                                @foreach($viewPaths as $p)
                                                    <code>{{ str_replace(base_path() . '/', '', $p) }}/</code>@if(!$loop->last), @endif
                                                @endforeach
                                            @else
                                                <code>resources/views/themes/{{ $key }}/</code>
                                            @endif
                                        </small>

                                        <small class="text-muted d-block">
                                            <i class="bi bi-file-earmark-code"></i> 
                                            CSS: <code>{{ $cssPath ?? 'public/themes/'.$key.'/theme.css' }}</code>
                                            @if($cssPath && file_exists(public_path($cssPath)))
                                                <span class="badge bg-success">✓</span>
                                            @else
                                                <span class="badge bg-warning">Not found</span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle"></i> Theme Development
                        </h6>
                        <p class="mb-2 small">The active theme is selected by its key and resolved from the app's theme registry:</p>
                        <ol class="mb-0 small">
                            <li>Theme keys are stored in the database via the <code>active_theme</code> setting.</li>
                            <li>The app auto-discovers themes from <code>resources/views/themes/*</code> and <code>public/themes/*</code>. A theme directory may include a <code>theme.json</code> manifest with <code>key</code>, <code>name</code>, and optional <code>views</code> paths.</li>
                            <li>Theme views can live under <code>resources/views/themes/{theme-key}/</code> or in a discovered theme package such as <code>public/themes/{folder}/views</code>, <code>public/themes/{folder}/resources/views</code>, or <code>public/themes/{folder}/front-ends</code>.</li>
                            <li>Theme CSS is resolved via <code>Theme::cssPath()</code>, which prefers the manifest folder and falls back to <code>public/themes/{folder}/theme.css</code>.</li>
                            <li>Assets such as logos or images should be placed inside the selected theme folder; the app will look for them relative to that theme's base path.</li>
                        </ol>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Theme
                        </button>
                        <form action="{{ route('admin.settings.theme.reset') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset to Default
                            </button>
                        </form>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
