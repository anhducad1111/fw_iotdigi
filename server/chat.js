
// Chatbot Logic

const chatWidget = document.getElementById('chat-widget');
const chatMessages = document.getElementById('chat-messages');
const chatInput = document.getElementById('chat-input');
const chatToggleBtn = document.getElementById('chat-toggle-btn');
const quickChips = document.querySelectorAll('.quick-chip');

// Toggle Chat Window
if (chatToggleBtn) {
    chatToggleBtn.addEventListener('click', () => {
        chatWidget.classList.toggle('hidden');
        if (!chatWidget.classList.contains('hidden')) {
            scrollToBottom();
            chatInput.focus();
        }
    });
}

// Quick Chips
quickChips.forEach(chip => {
    chip.addEventListener('click', () => {
        const text = chip.textContent;
        sendMessage(text);
    });
});

// Input Enter Key
if (chatInput) {
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const text = chatInput.value.trim();
            if (text) {
                sendMessage(text);
                chatInput.value = '';
            }
        }
    });
}

// Send Message
async function sendMessage(text) {
    if (!text) return;

    // Add User Message
    appendMessage('user', text);

    // Show Loading
    const loadingId = appendLoading();

    try {
        const response = await fetch('chatbot/chat_interface.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: text })
        });

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            // Read text to debug
            const errText = await response.text();
            console.error("Server HTML Error:", errText);
            throw new Error("Server Error (Not JSON). See Console.");
        }

        const data = await response.json();

        // Remove Loading
        removeLoading(loadingId);

        if (data.status === 'success') {
            if (data.debug_log && Array.isArray(data.debug_log)) {
                console.groupCollapsed("🤖 Chatbot Debug Logs");
                data.debug_log.forEach(log => console.log(log));
                console.groupEnd();
            }
            appendMessage('bot', data.response);
        } else {
            appendMessage('bot', 'Error: ' + (data.message || 'Unknown error'));
        }

    } catch (error) {
        removeLoading(loadingId);
        appendMessage('bot', 'Error: Failed to connect to chatbot server. check console.');
        console.error(error);
    }
}

function appendMessage(sender, text) {
    const div = document.createElement('div');
    const isUser = sender === 'user';

    div.className = `flex w-full mt-2 space-x-3 max-w-xs ${isUser ? 'ml-auto justify-end' : ''}`;

    const bubbleColor = isUser ? 'bg-primary text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200';
    const rounded = isUser ? 'rounded-l-lg rounded-br-lg' : 'rounded-r-lg rounded-bl-lg';

    div.innerHTML = `
        <div class="${isUser ? 'hidden' : ''} flex-shrink-0 h-8 w-8 rounded-full bg-neutral-200 flex items-center justify-center">
            <span class="material-symbols-outlined text-sm">smart_toy</span>
        </div>
        <div class="${bubbleColor} p-3 ${rounded}">
            <p class="text-sm markdown-body">${formatText(text)}</p>
        </div>
    `;

    chatMessages.appendChild(div);
    scrollToBottom();
}

function appendLoading() {
    const id = 'loading-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = "flex w-full mt-2 space-x-3 max-w-xs";
    div.innerHTML = `
        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-neutral-200 flex items-center justify-center">
             <span class="material-symbols-outlined text-sm">smart_toy</span>
        </div>
        <div class="bg-neutral-100 dark:bg-neutral-800 p-3 rounded-r-lg rounded-bl-lg">
             <div class="flex space-x-1">
                <div class="w-2 h-2 bg-neutral-400 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-neutral-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                <div class="w-2 h-2 bg-neutral-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
            </div>
        </div>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
    return id;
}

function removeLoading(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Simple text formatter (convert newlines to br)
function formatText(text) {
    return text.replace(/\n/g, '<br>');
}
