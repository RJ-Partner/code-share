@extends('layouts.app')

@section('content')
    <div id="editor-screen" class="flex flex-col h-screen">
        <!-- Header -->
        <header class="glass-effect p-4 flex justify-between items-center border-b border-slate-700 z-10">
            <div class="flex items-center">
                <a href="{{ url('/') }}"
                    class="mr-4 text-gray-300 hover:text-white p-2 rounded-lg hover:bg-slate-800 transition">
                    <i class="fas fa-home"></i>
                </a>
                <div>
                    <h1 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-code mr-2 text-blue-400"></i>
                        DevSync
                    </h1>
                    <div class="flex items-center text-sm text-gray-400 mt-1">
                        <span class="terminal-prompt mr-2">room:</span>
                        <span id="room-id-display" class="terminal-text font-mono">{{ $room->room_id }}</span>
                        <button id="copy-room-btn"
                            class="ml-2 text-gray-400 hover:text-white p-1 rounded hover:bg-slate-800 transition">
                            <i class="fas fa-copy"></i>
                        </button>
                        @if ($isAdmin)
                            <span class="ml-2 text-xs bg-blue-600 text-blue-100 px-2 py-1 rounded">ADMIN</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <div class="flex -space-x-2" id="active-users">
                        @foreach ($activeUsers as $user)
                            <div class="relative group">
                                <div
                                    class="w-8 h-8 rounded-full bg-{{ $user['isAdmin'] ? 'blue' : 'green' }}-500 flex items-center justify-center text-white text-xs font-bold border-2 border-gray-800">
                                    {{ $user['isAdmin'] ? 'A' : 'G' }}
                                </div>
                                <div
                                    class="absolute top-full left-1/2 transform -translate-x-1/2 mt-2 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                    <div class="font-semibold">{{ $user['isAdmin'] ? 'Admin' : 'Guest' }}</div>
                                    <div>{{ $user['ip'] }}</div>
                                    <div class="text-gray-400 text-xs">
                                        {{ \Carbon\Carbon::createFromTimestamp($user['lastActivity'])->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <span id="user-count" class="text-sm text-gray-400">{{ count($activeUsers) }}
                        user{{ count($activeUsers) != 1 ? 's' : '' }}</span>
                </div>
                @if ($isAdmin)
                    <button id="download-btn"
                        class="bg-slate-800 hover:bg-slate-700 text-white py-2 px-4 rounded-lg flex items-center transition duration-200 border border-slate-700">
                        <i class="fas fa-download mr-2"></i> Download
                    </button>
                @endif
                <button id="share-btn"
                    class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white py-2 px-4 rounded-lg flex items-center transition duration-200 shadow-lg">
                    <i class="fas fa-share-alt mr-2"></i> Share
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">
            <main class="h-full p-4">
                <div class="h-full">
                    <textarea id="code" {{ $room->read_only && !$isAdmin ? 'readonly' : '' }}>{{ $room->code }}</textarea>
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer class="glass-effect p-4 flex justify-between items-center border-t border-slate-700 z-10">
            <div class="flex items-center space-x-4 text-sm text-gray-400">
                <div class="flex items-center">
                    <i class="fas fa-code-branch mr-2"></i>
                    <span id="line-count">{{ substr_count($room->code, "\n") + 1 }}</span> lines
                </div>
                <div class="flex items-center">
                    <i class="fas fa-font mr-2"></i>
                    <span id="char-count">{{ strlen($room->code) }}</span> chars
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-green-500 mr-2 pulse-animation"></div>
                    <span>{{ $room->read_only && !$isAdmin ? 'Viewing' : 'Editing' }}</span>
                </div>
            </div>
        </footer>
    </div>

    <!-- Share Modal -->
    <div id="share-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="glass-effect rounded-xl p-6 max-w-md w-full mx-4 border border-slate-700">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Share Room</h3>
                <button id="close-share-modal" class="text-gray-400 hover:text-white p-1 rounded-full hover:bg-slate-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Room Link</label>
                <div class="flex">
                    <input id="share-link" type="text" readonly value="{{ route('room.show', $room->room_id) }}"
                        class="flex-1 bg-slate-800 text-white py-2 px-4 rounded-l-lg focus:outline-none border border-slate-700">
                    <button id="copy-link-btn"
                        class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white py-2 px-4 rounded-r-lg transition duration-200">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            @if ($room->password)
                <div class="mb-4 p-3 bg-yellow-900 bg-opacity-30 rounded-lg">
                    <p class="text-sm text-yellow-300">
                        <i class="fas fa-lock mr-2"></i> This room is password protected. Share the password with
                        collaborators.
                    </p>
                </div>
            @endif
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">QR Code</label>
                <div id="qrcode-container" class="flex justify-center p-4 bg-white rounded-lg">
                    <!-- QR Code will be generated here -->
                </div>
            </div>
            <div class="text-center text-sm text-gray-400">
                <p>Scan the QR code or share the link to invite collaborators</p>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        window.ReverbConfig = {
            key: "{{ env('VITE_REVERB_APP_KEY') }}",
            host: "{{ env('VITE_REVERB_HOST', request()->getHost()) }}",
            port: {{ env('VITE_REVERB_PORT', 6001) }},
            scheme: "{{ env('VITE_REVERB_SCHEME', 'http') }}"
        };
    </script>

    <script>
        // Initialize the app
        document.addEventListener("DOMContentLoaded", () => {
            if (document.getElementById("code")) {
                initEditor();
            }

            try {
                if (!window.Echo) {
                    throw new Error("window.Echo is not defined. Make sure Laravel Echo is initialized.");
                }

                const currentRoom = document.getElementById("room-id-display").textContent.trim();

                window.Echo.channel(`room.${currentRoom}`)
                    .listen('.code.updated', (e) => {
                        if (e.userId !== sessionUserId) {
                            console.log(
                                "%c🔄 Code update detected from another user",
                                "color: #3498db; font-weight: bold;"
                            );

                            // Fetch the latest code from the server
                            fetch(`/room/${currentRoomId}/code-fetch`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        console.error(data.error);
                                        return;
                                    }

                                    // Save cursor position
                                    const cursor = editor.getCursor();

                                    // Update editor content
                                    editor.setValue(data.code || '');

                                    // Restore cursor position
                                    editor.setCursor(cursor);

                                    console.log(
                                        "%c✅ Code updated successfully",
                                        "color: #2ecc71; font-weight: bold;"
                                    );

                                })
                                .catch(error => console.error('Error fetching code:', error));
                        }
                    })
                    .listen('.user.activity', (e) => {
                        updateActiveUsers(e.activeUsers);
                    })
                    .error((error) => {
                        console.error('WebSocket error:', error);
                        showNotification('Connection lost. Please refresh.', 'error');
                    });

                console.log("✅ Echo successfully initialized for room:", currentRoom);

                // Add this after the existing Echo channel listeners
                window.Echo.channel(`room.${currentRoom}`)
                    .listen('.room.inactive', (e) => {
                        console.log('Room is now inactive');

                        // Show a notification
                        showNotification('This room is now inactive. Redirecting...', 'warning');

                        // Disable the editor
                        if (editor) {
                            editor.setOption('readOnly', true);
                        }

                        // Show an overlay
                        const overlay = document.createElement('div');
                        overlay.className =
                            'fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50';
                        overlay.innerHTML = `
                                <div class="bg-gray-800 p-8 rounded-xl text-center max-w-md">
                                    <i class="fas fa-power-off text-5xl text-red-500 mb-4"></i>
                                    <h2 class="text-2xl font-bold mb-2">Room Inactive</h2>
                                    <p class="text-gray-300 mb-6">This room has been marked as inactive because all users have left.</p>
                                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                        <a href="/" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                                            <i class="fas fa-home mr-2"></i> Go to Home
                                        </a>
                                        <a href="/room/${currentRoom}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition">
                                            <i class="fas fa-redo mr-2"></i> Reactivate Room
                                        </a>
                                    </div>
                                </div>
                            `;
                        document.body.appendChild(overlay);

                        // Redirect after 10 seconds if user doesn't take action
                        setTimeout(() => {
                            window.location.href = `/room/${currentRoom}`;
                        }, 10000);
                    });

            } catch (error) {
                console.error('❌ Echo initialization error:', error.message);
                showNotification('❌ Failed to initialize real-time connection', 'error');
            }

        });

        // Initialize CodeMirror
        function initEditor() {
            editor = CodeMirror.fromTextArea(document.getElementById("code"), {
                lineNumbers: true,
                mode: "htmlmixed",
                theme: "dracula",
                readOnly: {{ $room->read_only && !($isAdmin ?? false) ? 'true' : 'false' }},
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 4,
                lineWrapping: true,
                extraKeys: {
                    "Ctrl-Space": "autocomplete",
                },
            });

            // Update stats with debounce
            editor.on("keyup", () => {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(updateStats, 300);
            });

            // Update code with debounce
            editor.on("keyup", () => {
                clearTimeout(codeUpdateTimer);
                codeUpdateTimer = setTimeout(() => {
                    const codeContent = editor.getValue();
                    if (codeContent !== lastSentCode) {
                        lastSentCode = codeContent;
                        broadcastCodeUpdate(currentRoomId, codeContent);
                    }
                }, 500);
            });
            updateStats();

        }

        // Global variables
        let editor;
        let lastSentCode = "";
        let typingTimer;
        let codeUpdateTimer;
        const sessionUserId = '{{ $userId }}';
        const currentRoom = document.getElementById("room-id-display")?.textContent.trim() || '';

        // Update line and character count
        function updateStats() {
            if (!editor) return;

            const code = editor.getValue();
            document.getElementById("line-count").textContent = code.split("\n").length;
            document.getElementById("char-count").textContent = code.length;
        }

        // Broadcast code update to Laravel API
        function broadcastCodeUpdate(roomId, codeContent) {
            if (!roomId) return;

            const formData = new FormData();
            formData.append('code', codeContent);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            fetch(`/room/${roomId}/code-update`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        lastSentCode = codeContent;
                        updateActiveUsers(data.activeUsers);
                    }
                })
                .catch(error => {
                    showNotification('Failed to sync code', 'error');
                });
        }

        // Add this to your JavaScript
        function checkRoomStatus() {
            fetch(`/room/${currentRoom}/status`)
                .then(response => response.json())
                .then(data => {
                    if (!data.is_active) {
                        // Trigger the same handling as the WebSocket event
                        const event = new CustomEvent('room-inactive', {
                            detail: {
                                roomId: currentRoom
                            }
                        });
                        document.dispatchEvent(event);
                    }
                })
                .catch(error => {
                    console.error('Error checking room status:', error);
                });
        }

        // Check room status every 30 seconds
        setInterval(checkRoomStatus, 30000);

        // Function to update active users display
        function updateActiveUsers(users) {
            const activeUsersContainer = document.getElementById('active-users');
            const userCountElement = document.getElementById('user-count');

            if (!activeUsersContainer || !userCountElement) return;

            // Clear current users
            activeUsersContainer.innerHTML = '';

            // Ensure users is an object
            const usersObj = typeof users === 'object' && users !== null ? users : {};

            // Update user count
            const userCount = Object.keys(usersObj).length;
            userCountElement.textContent = `${userCount} user${userCount !== 1 ? 's' : ''}`;

            Object.values(usersObj).forEach(user => {
                const userElement = document.createElement('div');
                userElement.className = 'relative group';

                const avatarElement = document.createElement('div');
                avatarElement.className =
                    `w-8 h-8 rounded-full bg-${user.isAdmin ? 'blue' : 'green'}-500 flex items-center justify-center text-white text-xs font-bold border-2 border-gray-800`;
                avatarElement.textContent = user.isAdmin ? 'A' : 'G';

                const tooltipElement = document.createElement('div');
                tooltipElement.className =
                    'absolute top-full left-1/2 transform -translate-x-1/2 mt-2 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10';
                tooltipElement.innerHTML = `
        <div class="font-semibold">${user.isAdmin ? 'Admin' : 'Guest'}</div>
        <div>${user.ip || 'Unknown IP'}</div>
        <div class="text-gray-400 text-xs">
            ${user.lastActivity ? new Date(user.lastActivity * 1000).toLocaleTimeString() : 'Unknown time'}
        </div>
    `;

                userElement.appendChild(avatarElement);
                userElement.appendChild(tooltipElement);
                activeUsersContainer.appendChild(userElement);
            });
        }

        // Send heartbeat to keep user active
        // each 20 second
        setInterval(() => {
            if (!currentRoomId || !sessionUserId) return;

            fetch(`/room/${currentRoomId}/heartbeat`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        userId: sessionUserId
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.activeUsers) {
                        updateActiveUsers(data.activeUsers);
                    }
                })
                .catch(error => {
                    console.error('Heartbeat error:', error);
                });
        }, 20000);

        // Clean up when user leaves the page
        window.addEventListener('beforeunload', () => {
            if (!currentRoomId || !sessionUserId) return;

            // Use sendBeacon for reliable delivery
            const data = new FormData();
            data.append('userId', sessionUserId);
            data.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            navigator.sendBeacon(`/room/${currentRoomId}/leave`, data);
        });

        // Show notification
        function showNotification(message, type = "success") {
            const notification = document.createElement("div");
            const bgColor = type === "success" ? "bg-green-600" :
                type === "error" ? "bg-red-600" : "bg-blue-600";

            notification.className = `fixed top-4 right-4 p-4 mb-2 rounded-lg flex items-center z-50 ${bgColor}`;

            const icon = type === "success" ? "check-circle" :
                type === "error" ? "exclamation-circle" : "info-circle";

            notification.innerHTML = `
                <i class="fas fa-${icon} mr-2"></i>
                <span>${message}</span>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        // Copy room ID
        function copyRoomId() {
            const roomUrl = window.location.href;
            navigator.clipboard.writeText(roomUrl)
                .then(() => showNotification("Room link copied to clipboard!"))
                .catch(() => showNotification("Failed to copy link", "error"));
        }

        // Download code
        function downloadCode() {
            if (!editor) return;

            const code = editor.getValue();
            const blob = new Blob([code], {
                type: "text/plain"
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `devsync-${currentRoomId}.html`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showNotification("Code downloaded successfully!");
        }

        // Show share modal
        function showShareModal() {
            const shareModal = document.getElementById("share-modal");
            if (!shareModal) return;

            shareModal.classList.remove("hidden");

            // Generate QR code
            const qrcodeContainer = document.getElementById("qrcode-container");
            if (qrcodeContainer) {
                qrcodeContainer.innerHTML = "";
                new QRCode(qrcodeContainer, {
                    text: window.location.href,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H,
                });
            }
        }

        // Copy share link
        function copyShareLink() {
            const shareLink = document.getElementById("share-link");
            if (!shareLink) return;

            shareLink.select();
            document.execCommand("copy");
            showNotification("Link copied to clipboard!");
        }

        // Event listeners
        document.addEventListener("DOMContentLoaded", () => {
            const copyRoomBtn = document.getElementById("copy-room-btn");
            const downloadBtn = document.getElementById("download-btn");
            const shareBtn = document.getElementById("share-btn");
            const shareModal = document.getElementById("share-modal");
            const closeShareModal = document.getElementById("close-share-modal");
            const copyLinkBtn = document.getElementById("copy-link-btn");

            if (copyRoomBtn) copyRoomBtn.addEventListener("click", copyRoomId);
            if (downloadBtn) downloadBtn.addEventListener("click", downloadCode);
            if (shareBtn) shareBtn.addEventListener("click", showShareModal);
            if (closeShareModal) closeShareModal.addEventListener("click", () => {
                shareModal?.classList.add("hidden");
            });
            if (copyLinkBtn) copyLinkBtn.addEventListener("click", copyShareLink);
        });
    </script>
@endpush
