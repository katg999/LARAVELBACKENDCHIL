@extends('layouts.app')

@section('title', 'Video Conference')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-video"></i> Video Conference
                        <small class="text-muted ml-2">Room: {{ $conference->room_name }}</small>
                    </h4>
                    <div id="conference-controls">
                        @if($conference->isHost(auth()->user()))
                        <button id="end-conference-btn" class="btn btn-danger btn-sm">
                            <i class="fas fa-stop"></i> End Conference
                        </button>
                        @else
                        <button id="leave-conference-btn" class="btn btn-warning btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Leave Conference
                        </button>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <!-- Conference Status -->
                    <div id="conference-status" class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span id="status-text">Initializing conference...</span>
                    </div>

                    <!-- Video Container -->
                    <div id="video-container" class="row">
                        <!-- Local Video -->
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user"></i> You
                                        <div class="float-right">
                                            <button id="toggle-video-btn" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-video"></i>
                                            </button>
                                            <button id="toggle-audio-btn" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-microphone"></i>
                                            </button>
                                        </div>
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    <div id="local-video" class="video-stream border rounded" style="height: 300px; background: #000;">
                                        <div class="d-flex align-items-center justify-content-center h-100 text-white">
                                            <div class="text-center">
                                                <i class="fas fa-video fa-3x mb-3"></i>
                                                <p>Initializing camera...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remote Videos -->
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-users"></i> Participants
                                        <span id="participant-count" class="badge badge-primary ml-2">0</span>
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    <div id="remote-videos" class="video-grid">
                                        <div class="remote-video-placeholder text-center text-muted py-5">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <p>Waiting for participants to join...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Participants List -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-list"></i> Participants
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="participants-list" class="list-group">
                                        <!-- Participants will be populated via JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Panel (Optional) -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-comments"></i> Chat
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="chat-messages" class="chat-messages mb-3" style="height: 200px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px;">
                                        <!-- Chat messages will appear here -->
                                    </div>
                                    <div class="input-group">
                                        <input type="text" id="chat-input" class="form-control" placeholder="Type a message...">
                                        <div class="input-group-append">
                                            <button id="send-chat-btn" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.video-stream {
    position: relative;
    background: #000;
    border-radius: 8px;
    overflow: hidden;
}

.video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
    min-height: 300px;
}

.remote-video {
    background: #000;
    border-radius: 8px;
    position: relative;
    overflow: hidden;
}

.participant-controls {
    position: absolute;
    bottom: 10px;
    left: 10px;
    right: 10px;
    opacity: 0;
    transition: opacity 0.3s;
}

.video-stream:hover .participant-controls {
    opacity: 1;
}

.chat-messages {
    background: #f8f9fa;
    border-radius: 4px;
}

.participant-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-bottom: 1px solid #dee2e6;
}

.participant-info {
    display: flex;
    align-items: center;
}

.participant-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-weight: bold;
}

.participant-status {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-left: 8px;
}

.status-joined {
    background: #28a745;
}

.status-left {
    background: #6c757d;
}

.status-kicked {
    background: #dc3545;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.agora.io/sdk/release/AgoraRTC_N-4.17.0.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const conferenceId = {{ $conference->id }};
    const roomName = '{{ $conference->room_name }}';
    const token = '{{ $token }}';
    const isHost = {{ $conference->isHost(auth()->user()) ? 'true' : 'false' }};
    const userId = {{ auth()->id() }};
    const userName = '{{ auth()->user()->name }}';

    // Agora client
    let client = null;
    let localTracks = { video: null, audio: null };
    let remoteUsers = {};

    // DOM elements
    const localVideoContainer = document.getElementById('local-video');
    const remoteVideosContainer = document.getElementById('remote-videos');
    const participantsList = document.getElementById('participants-list');
    const statusText = document.getElementById('status-text');
    const participantCount = document.getElementById('participant-count');
    const toggleVideoBtn = document.getElementById('toggle-video-btn');
    const toggleAudioBtn = document.getElementById('toggle-audio-btn');
    const leaveBtn = document.getElementById('leave-conference-btn');
    const endBtn = document.getElementById('end-conference-btn');

    // Initialize conference
    async function initializeConference() {
        try {
            updateStatus('Initializing Agora client...');

            // Create Agora client
            client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });

            // Set up event handlers
            setupEventHandlers();

            updateStatus('Joining conference room...');

            // Join channel
            await client.join('{{ config("services.agora.app_id", "demo_app_id") }}', roomName, token, userId);

            updateStatus('Initializing media devices...');

            // Create and publish local tracks
            await createLocalTracks();
            await publishLocalTracks();

            updateStatus('Conference active - you can now communicate');

        } catch (error) {
            console.error('Failed to initialize conference:', error);
            updateStatus('Failed to join conference: ' + error.message, 'danger');
        }
    }

    // Set up Agora event handlers
    function setupEventHandlers() {
        // User joined
        client.on('user-joined', async (user) => {
            console.log('User joined:', user);
            remoteUsers[user.uid] = user;
            updateParticipantsList();
            updateParticipantCount();
        });

        // User left
        client.on('user-left', (user) => {
            console.log('User left:', user);
            delete remoteUsers[user.uid];
            removeRemoteVideo(user.uid);
            updateParticipantsList();
            updateParticipantCount();
        });

        // User published
        client.on('user-published', async (user, mediaType) => {
            console.log('User published:', user, mediaType);
            await client.subscribe(user, mediaType);

            if (mediaType === 'video') {
                addRemoteVideo(user);
            }

            if (mediaType === 'audio') {
                user.audioTrack.play();
            }
        });

        // User unpublished
        client.on('user-unpublished', (user, mediaType) => {
            console.log('User unpublished:', user, mediaType);

            if (mediaType === 'video') {
                removeRemoteVideo(user.uid);
            }
        });
    }

    // Create local video and audio tracks
    async function createLocalTracks() {
        try {
            [localTracks.audio, localTracks.video] = await AgoraRTC.createMicrophoneAndCameraTracks();

            // Play local video
            localTracks.video.play('local-video');

            // Clear placeholder content
            localVideoContainer.innerHTML = '';
            localVideoContainer.appendChild(localTracks.video._player.videoElement);

        } catch (error) {
            console.error('Failed to create local tracks:', error);
            updateStatus('Failed to access camera/microphone', 'warning');
        }
    }

    // Publish local tracks
    async function publishLocalTracks() {
        try {
            if (localTracks.audio) {
                await client.publish(localTracks.audio);
            }
            if (localTracks.video) {
                await client.publish(localTracks.video);
            }
        } catch (error) {
            console.error('Failed to publish local tracks:', error);
        }
    }

    // Add remote video
    function addRemoteVideo(user) {
        const remoteVideoDiv = document.createElement('div');
        remoteVideoDiv.id = `remote-video-${user.uid}`;
        remoteVideoDiv.className = 'remote-video';

        const videoElement = document.createElement('div');
        videoElement.style.width = '100%';
        videoElement.style.height = '200px';

        remoteVideoDiv.appendChild(videoElement);
        remoteVideosContainer.appendChild(remoteVideoDiv);

        user.videoTrack.play(`remote-video-${user.uid}`);

        // Remove placeholder if it exists
        const placeholder = remoteVideosContainer.querySelector('.remote-video-placeholder');
        if (placeholder) {
            placeholder.remove();
        }
    }

    // Remove remote video
    function removeRemoteVideo(uid) {
        const remoteVideoDiv = document.getElementById(`remote-video-${uid}`);
        if (remoteVideoDiv) {
            remoteVideoDiv.remove();
        }

        // Add placeholder back if no remote videos
        if (remoteVideosContainer.children.length === 0) {
            const placeholder = document.createElement('div');
            placeholder.className = 'remote-video-placeholder text-center text-muted py-5';
            placeholder.innerHTML = `
                <i class="fas fa-users fa-3x mb-3"></i>
                <p>Waiting for participants to join...</p>
            `;
            remoteVideosContainer.appendChild(placeholder);
        }
    }

    // Update participants list
    function updateParticipantsList() {
        fetch(`/conferences/${conferenceId}/status`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderParticipantsList(data.conference.participants);
                }
            })
            .catch(error => console.error('Failed to update participants:', error));
    }

    // Render participants list
    function renderParticipantsList(participants) {
        participantsList.innerHTML = '';

        participants.forEach(participant => {
            const item = document.createElement('div');
            item.className = 'participant-item';

            const statusClass = `status-${participant.status}`;

            item.innerHTML = `
                <div class="participant-info">
                    <div class="participant-avatar">
                        ${participant.name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <strong>${participant.name}</strong>
                        <span class="badge badge-${participant.role === 'host' ? 'primary' : 'secondary'} ml-1">${participant.role}</span>
                        <span class="participant-status ${statusClass}"></span>
                    </div>
                </div>
                ${isHost && participant.role !== 'host' ? `
                    <button class="btn btn-sm btn-outline-danger kick-btn" data-user-id="${participant.user_id}">
                        <i class="fas fa-user-times"></i> Kick
                    </button>
                ` : ''}
            `;

            participantsList.appendChild(item);
        });

        // Add kick button event listeners
        document.querySelectorAll('.kick-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetUserId = this.getAttribute('data-user-id');
                kickParticipant(targetUserId);
            });
        });
    }

    // Update participant count
    function updateParticipantCount() {
        const count = Object.keys(remoteUsers).length + 1; // +1 for local user
        participantCount.textContent = count;
    }

    // Update status
    function updateStatus(message, type = 'info') {
        statusText.textContent = message;
        const alert = document.getElementById('conference-status');
        alert.className = `alert alert-${type}`;
    }

    // Toggle video
    toggleVideoBtn.addEventListener('click', async function() {
        if (localTracks.video) {
            if (localTracks.video.enabled) {
                await localTracks.video.setEnabled(false);
                this.innerHTML = '<i class="fas fa-video-slash"></i>';
                this.classList.add('btn-danger');
                this.classList.remove('btn-outline-secondary');
            } else {
                await localTracks.video.setEnabled(true);
                this.innerHTML = '<i class="fas fa-video"></i>';
                this.classList.remove('btn-danger');
                this.classList.add('btn-outline-secondary');
            }
        }
    });

    // Toggle audio
    toggleAudioBtn.addEventListener('click', async function() {
        if (localTracks.audio) {
            if (localTracks.audio.enabled) {
                await localTracks.audio.setEnabled(false);
                this.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                this.classList.add('btn-danger');
                this.classList.remove('btn-outline-secondary');
            } else {
                await localTracks.audio.setEnabled(true);
                this.innerHTML = '<i class="fas fa-microphone"></i>';
                this.classList.remove('btn-danger');
                this.classList.add('btn-outline-secondary');
            }
        }
    });

    // Leave conference
    if (leaveBtn) {
        leaveBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to leave the conference?')) {
                leaveConference();
            }
        });
    }

    // End conference (host only)
    if (endBtn) {
        endBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to end the conference for all participants?')) {
                endConference();
            }
        });
    }

    // Leave conference function
    async function leaveConference() {
        try {
            // Leave Agora channel
            if (client) {
                await client.leave();
            }

            // Stop local tracks
            Object.values(localTracks).forEach(track => {
                if (track) track.stop();
            });

            // Send leave request to server
            await fetch(`/conferences/${conferenceId}/leave`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            // Redirect back
            window.location.href = '/';

        } catch (error) {
            console.error('Error leaving conference:', error);
        }
    }

    // End conference function
    async function endConference() {
        try {
            await fetch(`/conferences/${conferenceId}/end`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            // Redirect back
            window.location.href = '/';

        } catch (error) {
            console.error('Error ending conference:', error);
        }
    }

    // Kick participant
    async function kickParticipant(userId) {
        try {
            await fetch(`/conferences/${conferenceId}/kick/${userId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
        } catch (error) {
            console.error('Error kicking participant:', error);
        }
    }

    // Initialize when page loads
    initializeConference();

    // Update participants list periodically
    setInterval(updateParticipantsList, 5000);
});
</script>
@endpush