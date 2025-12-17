<!-- Chat Widget Component -->
<div id="chat-widget" class="fixed bottom-20 right-4 w-80 md:w-96 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl shadow-2xl z-50 flex flex-col hidden transition-all duration-300 transform scale-95 opacity-0" style="height: 500px; max-height: 80vh;">
    <!-- Header -->
    <div class="p-4 border-b border-neutral-200 dark:border-neutral-800 bg-primary rounded-t-xl flex justify-between items-center text-white">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined">smart_toy</span>
            <span class="font-bold">IoT Assistant</span>
        </div>
        <button id="chat-close-btn" class="hover:text-neutral-200 focus:outline-none">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    
    <!-- Messages -->
    <div id="chat-messages" class="flex-1 p-4 overflow-y-auto flex flex-col gap-2 scroll-smooth">
        <!-- Intro Message -->
        <div class="flex w-full mt-2 space-x-3 max-w-xs">
            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-neutral-200 flex items-center justify-center">
                <span class="material-symbols-outlined text-sm">smart_toy</span>
            </div>
            <div class="bg-neutral-100 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 p-3 rounded-r-lg rounded-bl-lg shadow-sm">
                <p class="text-sm">Hi! I can help you check your water usage, costs, and explain alerts.</p>
            </div>
        </div>
    </div>

    <!-- Quick Chips -->
    <div class="p-2 border-t border-neutral-100 dark:border-neutral-800 flex gap-2 overflow-x-auto whitespace-nowrap scrollbar-hide">
        <button class="quick-chip px-3 py-1 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 rounded-full text-xs text-primary font-medium transition border border-transparent hover:border-primary/20">
            Why is my bill high?
        </button>
            <button class="quick-chip px-3 py-1 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 rounded-full text-xs text-primary font-medium transition border border-transparent hover:border-primary/20">
            Usage today?
        </button>
            <button class="quick-chip px-3 py-1 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 rounded-full text-xs text-primary font-medium transition border border-transparent hover:border-primary/20">
            Any alerts?
        </button>
    </div>
    
    <!-- Input -->
    <div class="p-4 border-t border-neutral-200 dark:border-neutral-800">
        <div class="flex items-center gap-2">
            <input id="chat-input" type="text" placeholder="Ask something..." class="flex-1 px-3 py-2 text-sm border border-neutral-300 dark:border-neutral-700 rounded-lg dark:bg-neutral-800 dark:text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
            <button id="chat-send-btn" class="p-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition shadow-sm">
                <span class="material-symbols-outlined text-sm">send</span>
            </button>
        </div>
    </div>
</div>

<!-- Toggle Button -->
<button id="chat-toggle-btn" class="fixed bottom-4 right-4 w-14 h-14 bg-primary text-white rounded-full shadow-lg flex items-center justify-center hover:bg-blue-700 transition z-50 hover:scale-110 active:scale-95 group">
    <span class="material-symbols-outlined text-2xl group-hover:rotate-12 transition-transform">chat</span>
</button>

<style>
    /* Custom Scrollbar for chat */
    #chat-messages::-webkit-scrollbar {
        width: 6px;
    }
    #chat-messages::-webkit-scrollbar-track {
        background: transparent;
    }
    #chat-messages::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.5);
        border-radius: 20px;
    }
    /* Simple Animation Utility classes not in Tailwind by default */
    #chat-widget:not(.hidden) {
        opacity: 1;
        transform: scale(1);
    }
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<script src="chat.js"></script>
<script>
    // Extra glue code if needed to handle close button since I moved it out of inline onclick
    document.getElementById('chat-close-btn').addEventListener('click', () => {
        const w = document.getElementById('chat-widget');
        w.classList.add('hidden');
        w.classList.remove('opacity-100', 'scale-100');
    });
    
    document.getElementById('chat-send-btn').addEventListener('click', () => {
        const input = document.getElementById('chat-input');
        sendMessage(input.value);
        input.value = '';
    });
</script>
