{* AI Assistant Chat Widget — Fun animated robot with chat panel *}

<div id="ai-assistant-widget">
    {* === INTRO CHARACTER (shows on first load) === *}
    <div id="aia-intro" class="aia-intro">
        <div class="aia-speech-bubble">
            <span id="aia-greeting-text"></span>
            <span class="aia-cursor">|</span>
        </div>
        <div class="aia-robot">
            <div class="aia-antenna">
                <div class="aia-antenna-ball"></div>
            </div>
            <div class="aia-head">
                <div class="aia-eye aia-eye-l">
                    <div class="aia-pupil"></div>
                </div>
                <div class="aia-eye aia-eye-r">
                    <div class="aia-pupil"></div>
                </div>
                <div class="aia-mouth"></div>
                <div class="aia-cheek aia-cheek-l"></div>
                <div class="aia-cheek aia-cheek-r"></div>
            </div>
            <div class="aia-body">
                <div class="aia-arm aia-arm-l"></div>
                <div class="aia-arm aia-arm-r aia-wave"></div>
                <div class="aia-belly-light"></div>
            </div>
        </div>
    </div>

    {* === FLOATING BUBBLE BUTTON === *}
    <button id="aia-toggle" class="aia-toggle" style="display:none;" title="AI Assistant">
        <div class="aia-toggle-face">
            <div class="aia-mini-eye"></div>
            <div class="aia-mini-eye"></div>
        </div>
        <div class="aia-toggle-pulse"></div>
    </button>

    {* === CHAT PANEL === *}
    <div id="aia-panel" class="aia-panel" style="display:none;">
        <div class="aia-panel-header">
            <div class="aia-header-bot">
                <div class="aia-header-avatar">
                    <div class="aia-ha-eye"></div>
                    <div class="aia-ha-eye"></div>
                </div>
                <div class="aia-header-info">
                    <span class="aia-header-name">CRM Assistant</span>
                    <span class="aia-header-status"><span class="aia-status-dot"></span> Online</span>
                </div>
            </div>
            <button id="aia-close" class="aia-close">&times;</button>
        </div>

        <div id="aia-messages" class="aia-messages">
            <div class="aia-msg aia-msg-bot">
                <div class="aia-msg-content">Hey there! I can help you manage leads, create workflows, and more. What do you need?</div>
            </div>
        </div>

        <div class="aia-input-wrap">
            <textarea id="aia-input" class="aia-input" placeholder="Type something fun..." rows="1"></textarea>
            <button id="aia-send" class="aia-send">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<style>
/* === RESET & SCOPE — everything under #ai-assistant-widget === */
#ai-assistant-widget,
#ai-assistant-widget *,
#ai-assistant-widget *::before,
#ai-assistant-widget *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
}

#ai-assistant-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    font-size: 14px;
    line-height: 1.5;
}

/* === COLOR TOKENS === */
#ai-assistant-widget {
    --aia-coral: #FF6B6B;
    --aia-coral-dark: #E85D5D;
    --aia-peach: #FFEAA7;
    --aia-navy: #2D3436;
    --aia-navy-light: #636E72;
    --aia-mint: #00CEC9;
    --aia-mint-dark: #00B3AE;
    --aia-white: #FFFFFF;
    --aia-ghost: #F8F9FA;
    --aia-gray: #DFE6E9;
    --aia-blue: #74B9FF;
    --aia-blue-dark: #0984E3;
    --aia-shadow: 0 12px 40px rgba(0,0,0,0.18);
    --aia-shadow-sm: 0 4px 16px rgba(0,0,0,0.12);
}

/* ============================
   INTRO CHARACTER
   ============================ */
#ai-assistant-widget .aia-intro {
    position: absolute;
    bottom: 0;
    right: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    animation: aia-pop-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes aia-pop-in {
    0% { transform: scale(0) translateY(40px); opacity: 0; }
    100% { transform: scale(1) translateY(0); opacity: 1; }
}

/* Speech bubble */
#ai-assistant-widget .aia-speech-bubble {
    background: var(--aia-white);
    border: 2.5px solid var(--aia-navy);
    border-radius: 18px 18px 18px 4px;
    padding: 12px 18px;
    margin-bottom: 8px;
    font-size: 15px;
    font-weight: 600;
    color: var(--aia-navy);
    white-space: nowrap;
    box-shadow: var(--aia-shadow-sm);
    min-height: 46px;
    min-width: 40px;
    position: relative;
    animation: aia-bubble-in 0.4s 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes aia-bubble-in {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

#ai-assistant-widget .aia-cursor {
    animation: aia-blink 0.7s step-end infinite;
    font-weight: 300;
    color: var(--aia-coral);
}

@keyframes aia-blink {
    50% { opacity: 0; }
}

/* === ROBOT CHARACTER === */
#ai-assistant-widget .aia-robot {
    position: relative;
    width: 80px;
    height: 110px;
}

/* Antenna */
#ai-assistant-widget .aia-antenna {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 3px;
    height: 14px;
    background: var(--aia-navy);
    border-radius: 2px;
    animation: aia-antenna-bob 2s ease-in-out infinite;
}

@keyframes aia-antenna-bob {
    0%, 100% { transform: translateX(-50%) rotate(-5deg); }
    50% { transform: translateX(-50%) rotate(5deg); }
}

#ai-assistant-widget .aia-antenna-ball {
    position: absolute;
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 10px;
    height: 10px;
    background: var(--aia-coral);
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(255, 107, 107, 0.6);
    animation: aia-glow 1.5s ease-in-out infinite alternate;
}

@keyframes aia-glow {
    0% { box-shadow: 0 0 4px rgba(255, 107, 107, 0.4); }
    100% { box-shadow: 0 0 14px rgba(255, 107, 107, 0.9); }
}

/* Head */
#ai-assistant-widget .aia-head {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    width: 56px;
    height: 46px;
    background: var(--aia-coral);
    border-radius: 16px 16px 20px 20px;
    border: 2.5px solid var(--aia-coral-dark);
    overflow: visible;
}

/* Eyes */
#ai-assistant-widget .aia-eye {
    position: absolute;
    top: 12px;
    width: 16px;
    height: 16px;
    background: var(--aia-white);
    border-radius: 50%;
    border: 2px solid var(--aia-navy);
}

#ai-assistant-widget .aia-eye-l { left: 8px; }
#ai-assistant-widget .aia-eye-r { right: 8px; }

#ai-assistant-widget .aia-pupil {
    position: absolute;
    top: 4px;
    left: 5px;
    width: 6px;
    height: 6px;
    background: var(--aia-navy);
    border-radius: 50%;
    animation: aia-look 4s ease-in-out infinite;
}

@keyframes aia-look {
    0%, 30% { transform: translateX(0); }
    35%, 65% { transform: translateX(3px); }
    70%, 100% { transform: translateX(0); }
}

/* Mouth — happy smile */
#ai-assistant-widget .aia-mouth {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    width: 18px;
    height: 8px;
    border: 2.5px solid var(--aia-navy);
    border-top: none;
    border-radius: 0 0 12px 12px;
}

/* Cheeks */
#ai-assistant-widget .aia-cheek {
    position: absolute;
    bottom: 10px;
    width: 10px;
    height: 6px;
    background: rgba(255, 200, 150, 0.5);
    border-radius: 50%;
}

#ai-assistant-widget .aia-cheek-l { left: 2px; }
#ai-assistant-widget .aia-cheek-r { right: 2px; }

/* Body */
#ai-assistant-widget .aia-body {
    position: absolute;
    top: 60px;
    left: 50%;
    transform: translateX(-50%);
    width: 48px;
    height: 40px;
    background: var(--aia-mint);
    border-radius: 8px 8px 16px 16px;
    border: 2.5px solid var(--aia-mint-dark);
}

#ai-assistant-widget .aia-belly-light {
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    width: 12px;
    height: 12px;
    background: var(--aia-peach);
    border-radius: 50%;
    border: 2px solid rgba(0,0,0,0.1);
    animation: aia-belly-pulse 2s ease-in-out infinite;
}

@keyframes aia-belly-pulse {
    0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.8; }
    50% { transform: translateX(-50%) scale(1.15); opacity: 1; }
}

/* Arms */
#ai-assistant-widget .aia-arm {
    position: absolute;
    top: 4px;
    width: 10px;
    height: 28px;
    background: var(--aia-mint);
    border: 2px solid var(--aia-mint-dark);
    border-radius: 6px;
}

#ai-assistant-widget .aia-arm-l {
    left: -12px;
    transform: rotate(8deg);
}

#ai-assistant-widget .aia-arm-r {
    right: -12px;
    transform-origin: top center;
}

/* Wave animation */
#ai-assistant-widget .aia-wave {
    animation: aia-wave-hand 0.6s ease-in-out 6 alternate;
    animation-delay: 0.8s;
}

@keyframes aia-wave-hand {
    0% { transform: rotate(-10deg); }
    100% { transform: rotate(-45deg); }
}

/* Shrink out */
#ai-assistant-widget .aia-intro.aia-shrink {
    animation: aia-shrink-out 0.5s cubic-bezier(0.55, 0.06, 0.68, 0.19) forwards;
}

@keyframes aia-shrink-out {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(0); opacity: 0; }
}

/* ============================
   TOGGLE BUBBLE
   ============================ */
#ai-assistant-widget .aia-toggle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, var(--aia-coral) 0%, #FF8E8E 100%);
    cursor: pointer;
    position: relative;
    box-shadow: var(--aia-shadow);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s;
    animation: aia-toggle-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    outline: none;
}

@keyframes aia-toggle-in {
    0% { transform: scale(0); }
    100% { transform: scale(1); }
}

#ai-assistant-widget .aia-toggle:hover {
    transform: scale(1.12) rotate(-5deg);
    box-shadow: 0 14px 44px rgba(255, 107, 107, 0.35);
}

#ai-assistant-widget .aia-toggle:active {
    transform: scale(0.95);
}

#ai-assistant-widget .aia-toggle-face {
    display: flex;
    gap: 8px;
    align-items: center;
    justify-content: center;
    height: 100%;
}

#ai-assistant-widget .aia-mini-eye {
    width: 10px;
    height: 12px;
    background: var(--aia-white);
    border-radius: 50%;
    position: relative;
    animation: aia-mini-blink 3s ease-in-out infinite;
}

@keyframes aia-mini-blink {
    0%, 42%, 48%, 100% { transform: scaleY(1); }
    45% { transform: scaleY(0.1); }
}

#ai-assistant-widget .aia-toggle-pulse {
    position: absolute;
    top: -4px;
    left: -4px;
    right: -4px;
    bottom: -4px;
    border-radius: 50%;
    border: 3px solid var(--aia-coral);
    opacity: 0;
    animation: aia-pulse-ring 3s ease-out infinite;
}

@keyframes aia-pulse-ring {
    0% { transform: scale(1); opacity: 0.5; }
    100% { transform: scale(1.5); opacity: 0; }
}

/* ============================
   CHAT PANEL
   ============================ */
#ai-assistant-widget .aia-panel {
    position: absolute;
    bottom: 72px;
    right: 0;
    width: 380px;
    height: 520px;
    background: var(--aia-white);
    border-radius: 20px;
    box-shadow: var(--aia-shadow);
    flex-direction: column;
    overflow: hidden;
    animation: aia-panel-open 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    border: 1px solid rgba(0,0,0,0.06);
}

@keyframes aia-panel-open {
    0% { transform: scale(0.8) translateY(20px); opacity: 0; }
    100% { transform: scale(1) translateY(0); opacity: 1; }
}

/* Header */
#ai-assistant-widget .aia-panel-header {
    background: linear-gradient(135deg, var(--aia-navy) 0%, #3D4447 100%);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

#ai-assistant-widget .aia-header-bot {
    display: flex;
    align-items: center;
    gap: 10px;
}

#ai-assistant-widget .aia-header-avatar {
    width: 36px;
    height: 36px;
    background: var(--aia-coral);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

#ai-assistant-widget .aia-ha-eye {
    width: 6px;
    height: 8px;
    background: var(--aia-white);
    border-radius: 50%;
}

#ai-assistant-widget .aia-header-info {
    display: flex;
    flex-direction: column;
}

#ai-assistant-widget .aia-header-name {
    color: var(--aia-white);
    font-weight: 700;
    font-size: 14px;
    letter-spacing: -0.2px;
}

#ai-assistant-widget .aia-header-status {
    color: rgba(255,255,255,0.6);
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 4px;
}

#ai-assistant-widget .aia-status-dot {
    width: 6px;
    height: 6px;
    background: #00E676;
    border-radius: 50%;
    display: inline-block;
    animation: aia-status-blink 2s ease-in-out infinite;
}

@keyframes aia-status-blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

#ai-assistant-widget .aia-close {
    background: rgba(255,255,255,0.1);
    border: none;
    color: rgba(255,255,255,0.7);
    width: 28px;
    height: 28px;
    border-radius: 8px;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    line-height: 1;
}

#ai-assistant-widget .aia-close:hover {
    background: rgba(255,255,255,0.2);
    color: var(--aia-white);
}

/* Messages */
#ai-assistant-widget .aia-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: var(--aia-ghost);
    background-image:
        radial-gradient(circle at 20% 50%, rgba(255,107,107,0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(0,206,201,0.03) 0%, transparent 50%);
}

#ai-assistant-widget .aia-messages::-webkit-scrollbar {
    width: 4px;
}
#ai-assistant-widget .aia-messages::-webkit-scrollbar-thumb {
    background: var(--aia-gray);
    border-radius: 4px;
}

#ai-assistant-widget .aia-msg {
    max-width: 82%;
    animation: aia-msg-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes aia-msg-in {
    0% { transform: translateY(8px) scale(0.95); opacity: 0; }
    100% { transform: translateY(0) scale(1); opacity: 1; }
}

#ai-assistant-widget .aia-msg-user {
    align-self: flex-end;
}

#ai-assistant-widget .aia-msg-bot {
    align-self: flex-start;
}

#ai-assistant-widget .aia-msg-content {
    padding: 10px 16px;
    font-size: 13.5px;
    line-height: 1.5;
    word-wrap: break-word;
    word-break: break-word;
}

#ai-assistant-widget .aia-msg-user .aia-msg-content {
    background: linear-gradient(135deg, var(--aia-blue-dark) 0%, #2E86DE 100%);
    color: var(--aia-white);
    border-radius: 16px 16px 4px 16px;
    box-shadow: 0 2px 8px rgba(9, 132, 227, 0.25);
}

#ai-assistant-widget .aia-msg-bot .aia-msg-content {
    background: var(--aia-white);
    color: var(--aia-navy);
    border-radius: 16px 16px 16px 4px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
}

/* Typing indicator */
#ai-assistant-widget .aia-typing-wrap {
    display: flex;
    gap: 5px;
    padding: 12px 16px;
    align-items: center;
}

#ai-assistant-widget .aia-typing-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--aia-coral);
    animation: aia-bounce-dot 1.4s ease-in-out infinite;
}

#ai-assistant-widget .aia-typing-dot:nth-child(1) { animation-delay: 0s; }
#ai-assistant-widget .aia-typing-dot:nth-child(2) { animation-delay: 0.2s; }
#ai-assistant-widget .aia-typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes aia-bounce-dot {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
}

/* Input area */
#ai-assistant-widget .aia-input-wrap {
    padding: 12px 14px;
    display: flex;
    gap: 8px;
    align-items: flex-end;
    border-top: 1px solid var(--aia-gray);
    background: var(--aia-white);
}

#ai-assistant-widget .aia-input {
    flex: 1;
    border: 2px solid var(--aia-gray);
    border-radius: 14px;
    padding: 10px 16px;
    font-size: 13.5px;
    resize: none;
    outline: none;
    max-height: 90px;
    font-family: inherit;
    color: var(--aia-navy);
    transition: border-color 0.2s;
    background: var(--aia-ghost);
    line-height: 1.4;
}

#ai-assistant-widget .aia-input::placeholder {
    color: var(--aia-navy-light);
}

#ai-assistant-widget .aia-input:focus {
    border-color: var(--aia-coral);
    background: var(--aia-white);
}

#ai-assistant-widget .aia-send {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, var(--aia-coral) 0%, #FF8E8E 100%);
    color: var(--aia-white);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}

#ai-assistant-widget .aia-send:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4);
}

#ai-assistant-widget .aia-send:active {
    transform: scale(0.95);
}

#ai-assistant-widget .aia-send:disabled {
    background: var(--aia-gray);
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* ============================
   RTL SUPPORT
   ============================ */
[dir="rtl"] #ai-assistant-widget {
    right: auto;
    left: 24px;
}

[dir="rtl"] #ai-assistant-widget .aia-panel {
    right: auto;
    left: 0;
}

[dir="rtl"] #ai-assistant-widget .aia-speech-bubble {
    border-radius: 18px 18px 4px 18px;
}

[dir="rtl"] #ai-assistant-widget .aia-msg-user {
    align-self: flex-start;
}

[dir="rtl"] #ai-assistant-widget .aia-msg-bot {
    align-self: flex-end;
}

[dir="rtl"] #ai-assistant-widget .aia-msg-user .aia-msg-content {
    border-radius: 16px 16px 16px 4px;
}

[dir="rtl"] #ai-assistant-widget .aia-msg-bot .aia-msg-content {
    border-radius: 16px 16px 4px 16px;
}

[dir="rtl"] #ai-assistant-widget .aia-send svg {
    transform: scaleX(-1);
}

/* ============================
   RESPONSIVE
   ============================ */
@media (max-width: 420px) {
    #ai-assistant-widget .aia-panel {
        width: calc(100vw - 20px);
        height: calc(100vh - 100px);
        bottom: 68px;
        right: -14px;
        border-radius: 16px;
    }
}
</style>

<script>
(function() {
    'use strict';

    /* --- DOM refs --- */
    var intro    = document.getElementById('aia-intro');
    var toggle   = document.getElementById('aia-toggle');
    var panel    = document.getElementById('aia-panel');
    var closeBtn = document.getElementById('aia-close');
    var messages = document.getElementById('aia-messages');
    var input    = document.getElementById('aia-input');
    var sendBtn  = document.getElementById('aia-send');
    var greeting = document.getElementById('aia-greeting-text');

    var isOpen = false;
    var isSending = false;
    var introShown = sessionStorage.getItem('aia_intro_done');

    /* --- Intro sequence --- */
    if (!introShown) {
        runIntro();
    } else {
        intro.style.display = 'none';
        toggle.style.display = 'block';
    }

    function runIntro() {
        var text = "Hi! I'm your CRM assistant!";
        var i = 0;
        var cursor = intro.querySelector('.aia-cursor');

        setTimeout(function typeChar() {
            if (i < text.length) {
                greeting.textContent += text[i];
                i++;
                setTimeout(typeChar, 50 + Math.random() * 40);
            } else {
                cursor.style.display = 'none';
                // After greeting, shrink into bubble
                setTimeout(function() {
                    intro.classList.add('aia-shrink');
                    setTimeout(function() {
                        intro.style.display = 'none';
                        toggle.style.display = 'block';
                        sessionStorage.setItem('aia_intro_done', '1');
                    }, 500);
                }, 2200);
            }
        }, 800);
    }

    /* --- Toggle panel --- */
    toggle.addEventListener('click', function() {
        if (isOpen) {
            panel.style.display = 'none';
            isOpen = false;
        } else {
            panel.style.display = 'flex';
            isOpen = true;
            input.focus();
        }
    });

    closeBtn.addEventListener('click', function() {
        panel.style.display = 'none';
        isOpen = false;
    });

    /* --- Send message --- */
    function sendMessage() {
        var text = input.value.trim();
        if (!text || isSending) return;

        appendMsg('user', text);
        input.value = '';
        input.style.height = 'auto';
        isSending = true;
        sendBtn.disabled = true;

        var typing = showTyping();

        fetch('index.php?module=AIAssistant&action=Chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            removeEl(typing);
            appendMsg('bot', data.content || 'Hmm, something went sideways.');

            if (data.ui_action) {
                handleUiAction(data.ui_action);
            }
        })
        .catch(function() {
            removeEl(typing);
            appendMsg('bot', 'Oops! Connection hiccup. Try again?');
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

    /* Auto-resize input */
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 90) + 'px';
    });

    /* --- Message helpers --- */
    function appendMsg(role, text) {
        var div = document.createElement('div');
        div.className = 'aia-msg aia-msg-' + role;
        var content = document.createElement('div');
        content.className = 'aia-msg-content';
        content.textContent = text;
        div.appendChild(content);
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function showTyping() {
        var div = document.createElement('div');
        div.className = 'aia-msg aia-msg-bot';
        div.innerHTML = '<div class="aia-typing-wrap">' +
            '<div class="aia-typing-dot"></div>' +
            '<div class="aia-typing-dot"></div>' +
            '<div class="aia-typing-dot"></div></div>';
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return div;
    }

    function removeEl(el) {
        if (el && el.parentNode) el.parentNode.removeChild(el);
    }

    /* --- UI Actions --- */
    function handleUiAction(action) {
        switch (action.type) {
            case 'navigate':
                setTimeout(function() {
                    window.location.href = action.url;
                }, 1500);
                break;
            case 'highlight':
                if (action.selector) {
                    var el = document.querySelector(action.selector);
                    if (el) {
                        el.style.outline = '3px solid var(--aia-coral, #FF6B6B)';
                        el.style.outlineOffset = '3px';
                        setTimeout(function() {
                            el.style.outline = '';
                            el.style.outlineOffset = '';
                        }, 5000);
                    }
                }
                break;
        }
    }
})();
</script>
