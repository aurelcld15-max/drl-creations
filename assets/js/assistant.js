/**
 * DRL Assistant — Fenêtre de conversation intelligente connectée à api/assistant.php via Fetch API
 */
document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    const toggleBtn = document.getElementById('aiToggleBtn');
    const closeBtn = document.getElementById('aiCloseBtn');
    const chatWindow = document.getElementById('aiChatWindow');
    const chatForm = document.getElementById('aiChatForm');
    const userInput = document.getElementById('aiUserInput');
    const messagesBox = document.getElementById('aiMessagesContainer');

    if (!toggleBtn || !chatWindow) return;

    toggleBtn.addEventListener('click', () => {
        chatWindow.classList.toggle('open');
        if (chatWindow.classList.contains('open')) {
            userInput?.focus();
        }
    });

    closeBtn?.addEventListener('click', () => {
        chatWindow.classList.remove('open');
    });

    function appendMessage(text, sender = 'bot', items = [], whatsappUrl = '') {
        const wrap = document.createElement('div');
        wrap.className = `drl-ai-msg drl-ai-msg-${sender}`;

        const bubble = document.createElement('div');
        bubble.className = 'drl-ai-bubble';
        bubble.innerHTML = text.replace(/\n/g, '<br>');

        if (Array.isArray(items) && items.length > 0) {
            const list = document.createElement('div');
            list.style.marginTop = '10px';
            list.style.display = 'flex';
            list.style.flexDirection = 'column';
            list.style.gap = '6px';

            items.forEach(it => {
                const card = document.createElement('div');
                card.style.padding = '8px';
                card.style.borderRadius = '8px';
                card.style.background = 'rgba(184, 154, 98, 0.14)';
                card.innerHTML = `
                    <div style="font-weight:600;"><a href="${it.url}" style="text-decoration:underline;">${it.title}</a></div>
                    <div style="font-size:0.76rem;opacity:0.85;">Réf: ${it.ref} • ${it.meta}</div>
                `;
                list.appendChild(card);
            });
            bubble.appendChild(list);
        }

        if (whatsappUrl && sender === 'bot') {
            const waBtn = document.createElement('a');
            waBtn.href = whatsappUrl;
            waBtn.target = '_blank';
            waBtn.rel = 'noopener';
            waBtn.className = 'drl-btn drl-btn-sm drl-btn-wa';
            waBtn.style.marginTop = '10px';
            waBtn.innerHTML = '<i class="fa-brands fa-whatsapp"></i> Poursuivre sur WhatsApp';
            bubble.appendChild(waBtn);
        }

        wrap.appendChild(bubble);
        messagesBox.appendChild(wrap);
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    async function sendPrompt(messageText) {
        if (!messageText.trim()) return;
        appendMessage(messageText, 'user');
        if (userInput) userInput.value = '';

        try {
            const res = await fetch(`${baseUrl}/api/assistant.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: messageText })
            });
            const data = await res.json();
            if (data.success) {
                appendMessage(data.reply, 'bot', data.items || [], data.whatsapp_url || '');
            } else {
                appendMessage(data.error || 'Une erreur est survenue.', 'bot');
            }
        } catch (err) {
            appendMessage('Connexion momentanément indisponible.', 'bot');
        }
    }

    chatForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        sendPrompt(userInput.value);
    });

    document.querySelectorAll('.drl-ai-chip[data-prompt]').forEach(chip => {
        chip.addEventListener('click', () => {
            sendPrompt(chip.getAttribute('data-prompt') || '');
        });
    });
});
