@if($audioFiles->isNotEmpty())
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-music-note-beamed"></i> Audio Files
        </h5>
    </div>
    <div class="card-body">
        @foreach($audioFiles as $audio)
            <div class="audio-player-wrapper mb-4 pb-4 @if(!$loop->last) border-bottom @endif">
                <div class="d-flex align-items-center mb-3">
                    <div class="audio-icon me-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-music-note-beamed fs-3 text-primary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">{{ $audio->filename }}</h6>
                        <small class="text-muted">
                            {{ $audio->mime_type }} • {{ number_format($audio->size / 1024, 1) }} KB
                        </small>
                    </div>
                </div>

                <audio id="audio-{{ $audio->id }}" src="{{ Storage::url($audio->path) }}" preload="metadata"></audio>

                <div class="audio-controls">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <button class="btn btn-primary btn-lg rounded-circle play-pause-btn" 
                                data-audio-id="{{ $audio->id }}"
                                style="width: 50px; height: 50px; padding: 0;">
                            <i class="bi bi-play-fill fs-4"></i>
                        </button>

                        <div class="flex-grow-1">
                            <div class="progress" style="height: 8px; cursor: pointer;" data-audio-id="{{ $audio->id }}">
                                <div class="progress-bar bg-primary progress-bar-{{ $audio->id }}" 
                                     role="progressbar" 
                                     style="width: 0%"
                                     aria-valuenow="0" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted time-current-{{ $audio->id }}">0:00</small>
                                <small class="text-muted time-duration-{{ $audio->id }}">0:00</small>
                            </div>
                        </div>

                        <div class="volume-control d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary mute-btn" data-audio-id="{{ $audio->id }}">
                                <i class="bi bi-volume-up-fill"></i>
                            </button>
                            <input type="range" 
                                   class="form-range volume-slider" 
                                   data-audio-id="{{ $audio->id }}"
                                   min="0" 
                                   max="100" 
                                   value="100" 
                                   style="width: 100px;">
                        </div>

                        <button class="btn btn-sm btn-outline-secondary download-btn" 
                                data-audio-id="{{ $audio->id }}"
                                onclick="window.open('{{ Storage::url($audio->path) }}', '_blank')">
                            <i class="bi bi-download"></i>
                        </button>
                    </div>

                    @if($audio->alt_text)
                        <div class="alert alert-info alert-sm mb-0 mt-2">
                            <i class="bi bi-info-circle"></i> {{ $audio->alt_text }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
    .audio-player-wrapper {
        transition: all 0.3s ease;
    }
    
    .progress {
        transition: height 0.2s ease;
    }
    
    .progress:hover {
        height: 12px !important;
    }
    
    .play-pause-btn {
        transition: all 0.2s ease;
    }
    
    .play-pause-btn:hover {
        transform: scale(1.1);
    }
    
    .volume-slider {
        cursor: pointer;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initAudioPlayers();
});

function initAudioPlayers() {
    const audioElements = document.querySelectorAll('audio');
    
    audioElements.forEach(audio => {
        const audioId = audio.id.replace('audio-', '');
        const playPauseBtn = document.querySelector(`button[data-audio-id="${audioId}"].play-pause-btn`);
        const progress = document.querySelector(`.progress[data-audio-id="${audioId}"]`);
        const progressBar = document.querySelector(`.progress-bar-${audioId}`);
        const currentTimeEl = document.querySelector(`.time-current-${audioId}`);
        const durationEl = document.querySelector(`.time-duration-${audioId}`);
        const volumeSlider = document.querySelector(`input[data-audio-id="${audioId}"].volume-slider`);
        const muteBtn = document.querySelector(`button[data-audio-id="${audioId}"].mute-btn`);
        
        let wasMuted = false;
        
        // Load metadata
        audio.addEventListener('loadedmetadata', () => {
            durationEl.textContent = formatTime(audio.duration);
        });
        
        // Play/Pause
        playPauseBtn.addEventListener('click', () => {
            // Pause all other audio elements
            audioElements.forEach(otherAudio => {
                if (otherAudio !== audio && !otherAudio.paused) {
                    otherAudio.pause();
                }
            });
            
            if (audio.paused) {
                audio.play();
            } else {
                audio.pause();
            }
        });
        
        // Update button icon
        audio.addEventListener('play', () => {
            playPauseBtn.innerHTML = '<i class="bi bi-pause-fill fs-4"></i>';
        });
        
        audio.addEventListener('pause', () => {
            playPauseBtn.innerHTML = '<i class="bi bi-play-fill fs-4"></i>';
        });
        
        // Update progress
        audio.addEventListener('timeupdate', () => {
            const percent = (audio.currentTime / audio.duration) * 100;
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', percent);
            currentTimeEl.textContent = formatTime(audio.currentTime);
        });
        
        // Seek
        progress.addEventListener('click', (e) => {
            const rect = progress.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audio.currentTime = percent * audio.duration;
        });
        
        // Volume
        volumeSlider.addEventListener('input', (e) => {
            audio.volume = e.target.value / 100;
            updateVolumeIcon(muteBtn, audio.volume);
        });
        
        // Mute/Unmute
        muteBtn.addEventListener('click', () => {
            if (audio.volume > 0) {
                wasMuted = audio.volume;
                audio.volume = 0;
                volumeSlider.value = 0;
            } else {
                audio.volume = wasMuted || 1;
                volumeSlider.value = (wasMuted || 1) * 100;
            }
            updateVolumeIcon(muteBtn, audio.volume);
        });
        
        // Reset on end
        audio.addEventListener('ended', () => {
            audio.currentTime = 0;
            playPauseBtn.innerHTML = '<i class="bi bi-play-fill fs-4"></i>';
        });
    });
}

function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function updateVolumeIcon(btn, volume) {
    let icon = 'bi-volume-up-fill';
    if (volume === 0) {
        icon = 'bi-volume-mute-fill';
    } else if (volume < 0.5) {
        icon = 'bi-volume-down-fill';
    }
    btn.innerHTML = `<i class="bi ${icon}"></i>`;
}
</script>
@endif
