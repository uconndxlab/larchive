@php
/**
 * OHMS Viewer Component
 * 
 * Displays oral history with indexed segments, transcript, and media player.
 * Supports YouTube embeds and direct video files with timestamp seeking.
 */

// Helper: Format seconds to HH:MM:SS
function format_seconds(int $seconds): string {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

// Helper: Detect if URL is YouTube
function is_youtube(string $url): bool {
    return str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
}

// Helper: Extract YouTube video ID
function youtube_id(string $url): ?string {
    if (preg_match('/(?:youtube\.com\/(?:embed\/|watch\?v=)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}

$ohms = $item->ohms_json;
$mediaUrl = $ohms['media_url'] ?? null;
$isYouTube = $mediaUrl ? is_youtube($mediaUrl) : false;
$youtubeId = $isYouTube ? youtube_id($mediaUrl) : null;
$segments = $ohms['segments'] ?? [];
$transcript = $ohms['transcript'] ?? null;
$title = $ohms['title'] ?? $item->title;
$duration = $ohms['duration_seconds'] ?? null;

// Get initial timestamp from query param ?t=
$initialTime = (int)request()->get('t', 0);
@endphp

<div class="card my-4 shadow-sm">
    <div class="card-header bg-primary text-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-mic-fill"></i>
                Oral History Viewer
            </h5>
            @if($duration)
                <span class="badge bg-light text-primary">{{ format_seconds($duration) }}</span>
            @endif
        </div>
        @if($title && $title !== $item->title)
            <div class="mt-2">
                <small class="opacity-75">{{ $title }}</small>
            </div>
        @endif
    </div>

    {{-- Media Player --}}
    @if($mediaUrl)
        <div class="position-relative" style="background: #000;">
            @if($isYouTube && $youtubeId)
                <iframe 
                    id="ohms-player"
                    width="100%" 
                    height="500"
                    src="https://www.youtube.com/embed/{{ $youtubeId }}?start={{ $initialTime }}&enablejsapi=1"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    style="display: block;"
                ></iframe>
            @else
                <video 
                    id="ohms-player"
                    controls 
                    controlsList="nodownload"
                    class="w-100"
                    style="max-height: 500px; background: #000;"
                >
                    <source src="{{ $mediaUrl }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            @endif
        </div>
    @endif

    {{-- Transcript & Index Columns --}}
    <div class="card-body p-4">
        <div class="row g-4">
            {{-- Transcript Column --}}
            @if($transcript)
                <div class="col-md-7">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light border-bottom">
                            <h6 class="mb-0">
                                <i class="bi bi-file-text"></i>
                                Full Transcript
                            </h6>
                        </div>
                        <div class="card-body p-4" style="max-height: 600px; overflow-y: auto;">
                            <div style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.8; color: #333;">{{ $transcript }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Index/Segments Column --}}
            @if(!empty($segments))
                <div class="col-md-{{ $transcript ? '5' : '12' }}">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light border-bottom">
                            <h6 class="mb-0">
                                <i class="bi bi-list-ol"></i>
                                Index
                                <span class="badge bg-secondary ms-2">{{ count($segments) }}</span>
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                                @foreach($segments as $index => $segment)
                                    <a 
                                        href="javascript:void(0)" 
                                        class="list-group-item list-group-item-action ohms-segment border-0"
                                        data-time="{{ $segment['time'] }}"
                                        onclick="seekToSegment({{ $segment['time'] }})"
                                    >
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-dark font-monospace px-2 py-1">
                                                        {{ format_seconds($segment['time']) }}
                                                    </span>
                                                    @if($segment['title'])
                                                        <strong class="text-dark">{{ $segment['title'] }}</strong>
                                                    @endif
                                                </div>

                                                @if($segment['synopsis'])
                                                    <p class="mb-2 small text-muted" style="line-height: 1.5;">{{ $segment['synopsis'] }}</p>
                                                @endif

                                                @if($segment['partial_transcript'])
                                                    <p class="mb-2 small fst-italic text-secondary" style="line-height: 1.5;">
                                                        "{{ Str::limit($segment['partial_transcript'], 120) }}"
                                                    </p>
                                                @endif

                                                @if(!empty($segment['keywords']))
                                                    <div class="mt-2 mb-1">
                                                        @foreach($segment['keywords'] as $keyword)
                                                            <span class="badge bg-light text-dark border me-1 mb-1">{{ $keyword }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if(!empty($segment['subjects']))
                                                    <div class="mb-1">
                                                        @foreach($segment['subjects'] as $subject)
                                                            <span class="badge bg-info bg-opacity-25 text-dark me-1 mb-1">{{ $subject }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if($segment['hyperlink'])
                                                    <div class="mt-2">
                                                        <a 
                                                            href="{{ $segment['hyperlink']['url'] }}" 
                                                            target="_blank" 
                                                            class="small text-decoration-none"
                                                            onclick="event.stopPropagation()"
                                                        >
                                                            <i class="bi bi-link-45deg"></i>
                                                            {{ $segment['hyperlink']['text'] }}
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>

                                            <button 
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                                onclick="event.stopPropagation(); copyTimestampLink({{ $segment['time'] }})"
                                                title="Copy link to this timestamp"
                                            >
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Inline JavaScript for seeking --}}
<script>
    const isYouTube = {{ $isYouTube ? 'true' : 'false' }};
    const youtubeId = '{{ $youtubeId }}';
    const initialTime = {{ $initialTime }};

    // Seek to a specific time
    function seekToSegment(seconds) {
        if (isYouTube) {
            // YouTube: reload iframe with new start time
            const iframe = document.getElementById('ohms-player');
            const newSrc = `https://www.youtube.com/embed/${youtubeId}?start=${seconds}&autoplay=1&enablejsapi=1`;
            iframe.src = newSrc;
        } else {
            // Direct video: seek to time
            const video = document.getElementById('ohms-player');
            if (video) {
                video.currentTime = seconds;
                video.play();
            }
        }

        // Scroll segment into view
        const activeSegment = document.querySelector('.ohms-segment.active');
        if (activeSegment) {
            activeSegment.classList.remove('active');
        }
        event.target.closest('.ohms-segment').classList.add('active');
    }

    // Copy timestamp link to clipboard
    function copyTimestampLink(seconds) {
        const url = new URL(window.location.href);
        url.searchParams.set('t', seconds);
        
        navigator.clipboard.writeText(url.toString()).then(() => {
            // Show brief success feedback
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check"></i>';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 1500);
        });
    }

    // Auto-seek on page load if ?t= parameter exists
    window.addEventListener('DOMContentLoaded', () => {
        if (initialTime > 0 && !isYouTube) {
            // For direct video, seek after metadata is loaded
            const video = document.getElementById('ohms-player');
            if (video) {
                video.addEventListener('loadedmetadata', () => {
                    video.currentTime = initialTime;
                }, { once: true });
            }
        }
        // YouTube auto-seeks via start param in iframe src
    });
</script>

<style>
    .ohms-segment.active {
        background-color: #e7f3ff !important;
        border-left: 4px solid #0d6efd !important;
        padding-left: calc(1rem - 4px) !important;
    }
    
    .ohms-segment:hover {
        background-color: #f8f9fa;
    }
    
    .ohms-segment {
        padding: 1rem;
        transition: all 0.2s ease;
        border-bottom: 1px solid #e9ecef;
    }
    
    .ohms-segment:last-child {
        border-bottom: none;
    }
</style>
