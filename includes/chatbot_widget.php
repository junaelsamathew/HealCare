<!-- HealCare Chatbot Widget -->
<style>
    /* 3D Robot Animation */
    .chatbot-toggler {
        position: fixed;
        bottom: 170px;
        right: 30px;
        height: 70px;
        width: 70px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 9999;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    .chatbot-toggler:hover {
        transform: scale(1.1) translateY(-5px);
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
    }
    
    .robot-icon {
        position: relative;
        width: 40px;
        height: 40px;
        transition: all 0.3s ease;
    }
    
    body.show-chatbot .robot-icon {
        opacity: 0;
        transform: scale(0);
    }
    
    /* Robot Head */
    .robot-head {
        width: 30px;
        height: 25px;
        background: #fff;
        border-radius: 8px;
        position: absolute;
        top: 5px;
        left: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        animation: headBob 2s ease-in-out infinite;
    }
    
    @keyframes headBob {
        0%, 100% { transform: rotate(-2deg); }
        50% { transform: rotate(2deg); }
    }
    
    /* Robot Eyes */
    .robot-eye {
        width: 6px;
        height: 6px;
        background: #667eea;
        border-radius: 50%;
        position: absolute;
        top: 8px;
        animation: blink 3s infinite;
    }
    
    .robot-eye.left { left: 7px; }
    .robot-eye.right { right: 7px; }
    
    @keyframes blink {
        0%, 48%, 52%, 100% { height: 6px; }
        50% { height: 1px; }
    }
    
    /* Robot Antenna */
    .robot-antenna {
        width: 2px;
        height: 8px;
        background: #fff;
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
    }
    
    .robot-antenna::after {
        content: '';
        width: 5px;
        height: 5px;
        background: #fbbf24;
        border-radius: 50%;
        position: absolute;
        top: -5px;
        left: 50%;
        transform: translateX(-50%);
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { 
            box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.7);
            transform: translateX(-50%) scale(1);
        }
        50% { 
            box-shadow: 0 0 0 5px rgba(251, 191, 36, 0);
            transform: translateX(-50%) scale(1.2);
        }
    }
    
    /* Robot Mouth */
    .robot-mouth {
        width: 12px;
        height: 2px;
        background: #667eea;
        border-radius: 2px;
        position: absolute;
        bottom: 6px;
        left: 50%;
        transform: translateX(-50%);
    }
    
    /* Robot Body */
    .robot-body {
        width: 20px;
        height: 15px;
        background: #fff;
        border-radius: 4px;
        position: absolute;
        bottom: 0;
        left: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    /* Robot Arms */
    .robot-arm {
        width: 3px;
        height: 10px;
        background: #fff;
        border-radius: 2px;
        position: absolute;
        top: 32px;
        animation: wave 1.5s ease-in-out infinite;
    }
    
    .robot-arm.left { 
        left: 2px;
        transform-origin: top;
    }
    .robot-arm.right { 
        right: 2px;
        transform-origin: top;
        animation-delay: 0.75s;
    }
    
    @keyframes wave {
        0%, 100% { transform: rotate(0deg); }
        50% { transform: rotate(20deg); }
    }
    
    /* Close Icon */
    .close-icon {
        position: absolute;
        color: #fff;
        font-size: 1.8rem;
        opacity: 0;
        transform: scale(0) rotate(180deg);
        transition: all 0.3s ease;
    }
    
    body.show-chatbot .close-icon {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }

    /* Chatbot Floating Button */
    .chatbot-toggler span {
        color: #fff;
        position: absolute;
        font-size: 1.5rem;
    }
    .chatbot-toggler span:last-child,
    body.show-chatbot .chatbot-toggler span:first-child {
        opacity: 0;
    }
    body.show-chatbot .chatbot-toggler span:last-child {
        opacity: 1;
    }

    /* Chatbot Window */
    .chatbot {
        position: fixed;
        right: 30px;
        bottom: 250px;
        width: 380px;
        background: #1e293b;
        border-radius: 15px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
        transform: scale(0.5);
        transform-origin: bottom right;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
        transition: all 0.1s ease;
        z-index: 9999;
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        flex-direction: column;
    }
    body.show-chatbot .chatbot {
        opacity: 1;
        pointer-events: auto;
        transform: scale(1);
    }

    /* Header */
    .chatbot header {
        padding: 16px 0;
        position: relative;
        text-align: center;
        color: #fff;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .chatbot header h2 {
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0;
    }
    .chatbot header p {
        font-size: 0.8rem;
        margin: 0;
        opacity: 0.9;
    }
    .chatbot header span {
        position: absolute;
        right: 15px;
        top: 50%;
        display: none;
        cursor: pointer;
        transform: translateY(-50%);
    }

    /* Chat Box */
    .chatbot .chatbox {
        overflow-y: auto;
        height: 350px;
        padding: 20px 20px 70px;
        background: #0f172a;
    }
    .chatbot .chatbox::-webkit-scrollbar {
        width: 6px;
    }
    .chatbot .chatbox::-webkit-scrollbar-track {
        background: #1e293b;
    }
    .chatbot .chatbox::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 3px;
    }

    .chatbox .chat {
        display: flex;
        list-style: none;
        margin-bottom: 15px;
    }
    .chatbox .outgoing {
        justify-content: flex-end;
    }
    .chatbox .incoming span {
        width: 32px;
        height: 32px;
        color: #fff;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        text-align: center;
        line-height: 32px;
        border-radius: 50%;
        margin: 0 10px 7px 0;
        align-self: flex-end;
        font-size: 14px;
        flex-shrink: 0;
    }
    .chatbox .chat p {
        white-space: pre-wrap;
        padding: 12px 16px;
        border-radius: 10px 10px 0 10px;
        max-width: 75%;
        color: #fff;
        font-size: 0.95rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        line-height: 1.4;
    }
    .chatbox .incoming p {
        border-radius: 10px 10px 10px 0;
        color: #e2e8f0;
        background: #334155;
    }
    .chatbox .chat p.error {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
    }

    /* Input Area */
    .chatbot .chat-input {
        position: absolute;
        bottom: 0;
        width: 100%;
        background: #1e293b;
        padding: 10px 20px;
        border-top: 1px solid rgba(255,255,255,0.1);
        display: flex;
        gap: 5px;
        align-items: center;
    }
    .chat-input textarea {
        height: 45px;
        width: 100%;
        border: none;
        outline: none;
        resize: none;
        max-height: 180px;
        padding: 12px 15px 12px 0;
        font-size: 0.95rem;
        background: transparent;
        color: #fff;
    }
    .chat-input span {
        align-self: flex-end;
        color: #667eea;
        cursor: pointer;
        height: 45px;
        display: flex;
        align-items: center;
        font-size: 1.2rem;
        transition: 0.2s;
    }
    .chat-input span:hover {
        color: #764ba2;
    }
    .chat-input textarea:valid ~ span {
        visibility: visible;
    }
    
    .chat-link {
        color: #60a5fa;
        text-decoration: underline;
        font-weight: 600;
    }
    .chat-link:hover {
        color: #93c5fd;
    }

    @media (max-width: 490px) {
        .chatbot-toggler {
            right: 20px;
            bottom: 150px;
        }
        .chatbot {
            right: 0;
            bottom: 0;
            height: 100%;
            border-radius: 0;
            width: 100%;
        }
        .chatbot .chatbox {
            height: 90%;
            padding: 25px 15px 100px;
        }
        .chatbot .chat-input {
            padding: 5px 15px;
        }
        .chatbot header span {
            display: block;
        }
    }
</style>

<!-- Toggler with 3D Robot -->
<button class="chatbot-toggler">
    <div class="robot-icon">
        <div class="robot-head">
            <div class="robot-antenna"></div>
            <div class="robot-eye left"></div>
            <div class="robot-eye right"></div>
            <div class="robot-mouth"></div>
        </div>
        <div class="robot-body"></div>
        <div class="robot-arm left"></div>
        <div class="robot-arm right"></div>
    </div>
    <span class="close-icon fas fa-times"></span>
</button>

<!-- Chat Window -->
<div class="chatbot">
    <header>
        <h2>HealCare Assistant</h2>
        <p>Expert Guidance • 24/7 Support</p>
        <span class="close-btn fas fa-times"></span>
    </header>
    <ul class="chatbox">
        <li class="chat incoming">
            <span class="fas fa-robot"></span>
            <p>Hello! I am HealCare Assistant. 👋<br><br>I can help you schedule appointments, check lab reports, or guide you through our services.<br><br>How can I assist you today?</p>
        </li>
    </ul>
    <div class="chat-input">
        <textarea placeholder="Type a message..." spellcheck="false" required></textarea>
        <span id="send-btn" class="fas fa-paper-plane"></span>
    </div>
</div>

<script>
    const chatbotToggler = document.querySelector(".chatbot-toggler");
    const closeBtn = document.querySelector(".close-btn");
    const chatbox = document.querySelector(".chatbox");
    const chatInput = document.querySelector(".chat-input textarea");
    const sendChatBtn = document.querySelector(".chat-input span");

    let userMessage = null; // Variable to store user's message

    const createChatLi = (message, className) => {
        // Create a chat <li> element with passed message and className
        const chatLi = document.createElement("li");
        chatLi.classList.add("chat", className);
        let chatContent = className === "outgoing" ? `<p></p>` : `<span class="fas fa-robot"></span><p></p>`;
        chatLi.innerHTML = chatContent;
        chatLi.querySelector("p").innerHTML = message; // Use innerHTML to parse links
        return chatLi;
    }

    const generateResponse = (chatElement) => {
        const API_URL = "includes/chatbot_backend.php";
        const messageElement = chatElement.querySelector("p");

        const requestOptions = {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                message: userMessage
            })
        }

        // Send POST request to PHP backend
        fetch(API_URL, requestOptions).then(res => res.json()).then(data => {
            messageElement.innerHTML = data.reply; // Parse HTML response
        }).catch(() => {
            messageElement.classList.add("error");
            messageElement.textContent = "Oops! Something went wrong. Please try again.";
        }).finally(() => chatbox.scrollTo(0, chatbox.scrollHeight));
    }

    const handleChat = () => {
        userMessage = chatInput.value.trim(); 
        if(!userMessage) return;

        // Clear input
        chatInput.value = "";
        chatInput.style.height = "auto";

        // Append user message
        chatbox.appendChild(createChatLi(userMessage, "outgoing"));
        chatbox.scrollTo(0, chatbox.scrollHeight);

        // Display "Thinking..."
        setTimeout(() => {
            const incomingChatLi = createChatLi("...", "incoming");
            chatbox.appendChild(incomingChatLi);
            chatbox.scrollTo(0, chatbox.scrollHeight);
            generateResponse(incomingChatLi);
        }, 600);
    }

    chatInput.addEventListener("input", () => {
        chatInput.style.height = "auto";
        chatInput.style.height = `${chatInput.scrollHeight}px`;
    });

    chatInput.addEventListener("keydown", (e) => {
        if(e.key === "Enter" && !e.shiftKey && window.innerWidth > 800) {
            e.preventDefault();
            handleChat();
        }
    });

    sendChatBtn.addEventListener("click", handleChat);
    closeBtn.addEventListener("click", () => document.body.classList.remove("show-chatbot"));
    chatbotToggler.addEventListener("click", () => document.body.classList.toggle("show-chatbot"));
</script>
