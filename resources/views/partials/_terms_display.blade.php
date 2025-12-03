{{--
    Display terms/tags for a resource
    Usage: @include('partials._terms_display', ['resource' => $item])
--}}

@if($resource->terms->isNotEmpty())
    <div class="mb-3">
        <h6 class="text-muted small text-uppercase mb-2">Categorization</h6>
        
        @php
            $termsByTaxonomy = $resource->terms->groupBy('taxonomy.name');
        @endphp
        
        @foreach($termsByTaxonomy as $taxonomyName => $terms)
            <div class="mb-2">
                <strong class="small">{{ $taxonomyName }}:</strong>
                <div class="d-inline">
                    @foreach($terms as $term)
                        <a href="{{ route('terms.show', [$term->taxonomy, $term]) }}" 
                           class="badge bg-secondary text-decoration-none me-1">
                            {{ $term->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
