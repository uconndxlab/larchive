@extends('layouts.app')

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
                                        <small class="text-muted d-block">
                                            <i class="bi bi-folder"></i> 
                                            Views: <code>resources/views/themes/{{ $key }}/</code>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-file-earmark-code"></i> 
                                            CSS: <code>public/themes/{{ $key }}/theme.css</code>
                                            @if(file_exists(public_path("themes/{$key}/theme.css")))
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
                        <p class="mb-2 small">To create a custom theme:</p>
                        <ol class="mb-0 small">
                            <li>Add theme configuration to <code>config/larchive.php</code></li>
                            <li>Create theme views in <code>resources/views/themes/{'{theme-key}'}/</code></li>
                            <li>Add theme CSS to <code>public/themes/{'{theme-key}'}/theme.css</code></li>
                            <li>Optionally add assets like logos to <code>public/themes/{'{theme-key}'}/</code></li>
                        </ol>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Theme
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
