function toggleChat() {
    const widget = document.getElementById('chatbot-widget');
    const toggler = document.getElementById('chatbot-toggler');
    if (widget.style.display === 'none' || widget.style.display === '') {
        widget.style.display = 'block';
        toggler.style.display = 'none';
        document.getElementById('chatbot-input').focus();
    } else {
        widget.style.display = 'none';
        toggler.style.display = 'block';
    }
}

function handleChatEnter(e) {
    if (e.key === 'Enter') sendChatMessage();
}

function sendChatMessage() {
    const input = document.getElementById('chatbot-input');
    const message = input.value.trim();
    if (!message) return;

    // 1. Hiển thị tin nhắn User
    appendMessage(message, 'user-msg');
    input.value = '';

    // 2. Hiển thị trạng thái "Đang gõ..."
    const msgContainer = document.getElementById('chatbot-messages');
    const typingBubble = document.createElement('div');
    typingBubble.className = 'chat-bubble bot-msg typing-indicator';
    typingBubble.innerHTML = '<i>Đang gõ...</i>';
    msgContainer.appendChild(typingBubble);
    msgContainer.scrollTop = msgContainer.scrollHeight;

    // 3. Gửi lên Bot API
    // Lưu ý: Cần khớp với tên thư mục XAMPP của bạn
    const botUrl = window.location.origin + '/DoAn_LapTrinhWeb_DTH235712_DTH235713/modules/chatbot/bot.php';

    fetch(botUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        typingBubble.remove();
        appendMessage(data.reply, 'bot-msg');
    })
    .catch(error => {
        typingBubble.remove();
        console.error('Chatbot error:', error);
        appendMessage("Lỗi kết nối mạng. Vui lòng thử lại.", 'bot-msg');
    });
}

function appendMessage(htmlContent, className) {
    const msgContainer = document.getElementById('chatbot-messages');
    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${className}`;
    bubble.innerHTML = htmlContent;
    msgContainer.appendChild(bubble);
    // Tự động cuộn xuống cuối
    msgContainer.scrollTop = msgContainer.scrollHeight;
}
