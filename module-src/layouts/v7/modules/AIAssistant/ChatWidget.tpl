{* AI Assistant Chat Widget - injected into all vtiger pages *}

<div id="ai-assistant-widget" class="ai-assistant-widget" style="display:none;">
    {* Toggle Button *}
    <button id="ai-assistant-toggle" class="ai-toggle-btn" title="AI Assistant">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
    </button>

    {* Chat Panel *}
    <div id="ai-assistant-panel" class="ai-panel" style="display:none;">
        <div class="ai-panel-header">
            <span class="ai-panel-title">AI Assistant</span>
            <button id="ai-assistant-close" class="ai-panel-close">&times;</button>
        </div>

        <div id="ai-assistant-messages" class="ai-messages">
            <div class="ai-message ai-message-assistant">
                <div class="ai-message-content">
                    Hi! I'm your CRM assistant. I can help you import leads, create workflows, set up automations, and more. What would you like to do?
                </div>
            </div>
        </div>

        <div class="ai-input-area">
            <textarea id="ai-assistant-input" class="ai-input" placeholder="Ask me anything..." rows="1"></textarea>
            <button id="ai-assistant-send" class="ai-send-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<style>
.ai-assistant-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 10000;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.ai-toggle-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    background: #1a73e8;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, background 0.2s;
}

.ai-toggle-btn:hover {
    transform: scale(1.1);
    background: #1557b0;
}

.ai-panel {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.ai-panel-header {
    background: #1a73e8;
    color: white;
    padding: 14px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-panel-title {
    font-weight: 600;
    font-size: 15px;
}

.ai-panel-close {
    background: none;
    border: none;
    color: white;
    font-size: 22px;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
}

.ai-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ai-message {
    max-width: 85%;
}

.ai-message-user {
    align-self: flex-end;
}

.ai-message-assistant {
    align-self: flex-start;
}

.ai-message-content {
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.4;
    word-wrap: break-word;
}

.ai-message-user .ai-message-content {
    background: #1a73e8;
    color: white;
    border-bottom-right-radius: 4px;
}

.ai-message-assistant .ai-message-content {
    background: #f1f3f4;
    color: #202124;
    border-bottom-left-radius: 4px;
}

.ai-input-area {
    padding: 12px;
    border-top: 1px solid #e8eaed;
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

.ai-input {
    flex: 1;
    border: 1px solid #dadce0;
    border-radius: 20px;
    padding: 10px 16px;
    font-size: 14px;
    resize: none;
    outline: none;
    max-height: 100px;
    font-family: inherit;
}

.ai-input:focus {
    border-color: #1a73e8;
}

.ai-send-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: #1a73e8;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ai-send-btn:hover {
    background: #1557b0;
}

.ai-send-btn:disabled {
    background: #dadce0;
    cursor: not-allowed;
}

.ai-typing {
    display: flex;
    gap: 4px;
    padding: 10px 14px;
}

.ai-typing-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #80868b;
    animation: ai-typing-bounce 1.4s infinite ease-in-out;
}

.ai-typing-dot:nth-child(1) { animation-delay: -0.32s; }
.ai-typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes ai-typing-bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* RTL Support */
[dir="rtl"] .ai-assistant-widget {
    right: auto;
    left: 20px;
}

[dir="rtl"] .ai-panel {
    right: auto;
    left: 0;
}

[dir="rtl"] .ai-message-user {
    align-self: flex-start;
}

[dir="rtl"] .ai-message-assistant {
    align-self: flex-end;
}
</style>

<script>
(function() {
    'use strict';

    var widget = document.getElementById('ai-assistant-widget');
    var toggle = document.getElementById('ai-assistant-toggle');
    var panel = document.getElementById('ai-assistant-panel');
    var closeBtn = document.getElementById('ai-assistant-close');
    var messagesDiv = document.getElementById('ai-assistant-messages');
    var input = document.getElementById('ai-assistant-input');
    var sendBtn = document.getElementById('ai-assistant-send');
    var isOpen = false;
    var isSending = false;

    // Show widget
    widget.style.display = 'block';

    // Toggle panel
    toggle.addEventListener('click', function() {
        isOpen = !isOpen;
        panel.style.display = isOpen ? 'flex' : 'none';
        if (isOpen) input.focus();
    });

    closeBtn.addEventListener('click', function() {
        isOpen = false;
        panel.style.display = 'none';
    });

    // Send message
    function sendMessage() {
        var text = input.value.trim();
        if (!text || isSending) return;

        // Add user message
        appendMessage('user', text);
        input.value = '';
        input.style.height = 'auto';
        isSending = true;
        sendBtn.disabled = true;

        // Show typing indicator
        var typingEl = showTyping();

        // Call API
        fetch('index.php?module=AIAssistant&action=Chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            removeTyping(typingEl);
            appendMessage('assistant', data.content || 'Something went wrong.');
        })
        .catch(function(err) {
            removeTyping(typingEl);
            appendMessage('assistant', 'Connection error. Please try again.');
        })
        .finally(function() {
            isSending = false;
            sendBtn.disabled = false;
        });
    }

    sendBtn.addEventListener('click', sendMessage);

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize textarea
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });

    function appendMessage(role, text) {
        var div = document.createElement('div');
        div.className = 'ai-message ai-message-' + role;
        var content = document.createElement('div');
        content.className = 'ai-message-content';
        content.textContent = text;
        div.appendChild(content);
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function showTyping() {
        var div = document.createElement('div');
        div.className = 'ai-message ai-message-assistant';
        div.innerHTML = '<div class="ai-typing"><div class="ai-typing-dot"></div><div class="ai-typing-dot"></div><div class="ai-typing-dot"></div></div>';
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
        return div;
    }

    function removeTyping(el) {
        if (el && el.parentNode) el.parentNode.removeChild(el);
    }
})();
</script>
