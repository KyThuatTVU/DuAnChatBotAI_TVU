const chatIcon = document.getElementById("chat-icon");
const chatContainer = document.getElementById("chat-container");
const closeChat = document.getElementById("close-chat");

let isDragging = false;
let hasMoved = false;
let offsetX, offsetY;

// MOUSE DOWN
chatIcon.addEventListener("mousedown", function(e) {
    isDragging = true;
    hasMoved = false;

    offsetX = e.clientX - chatIcon.getBoundingClientRect().left;
    offsetY = e.clientY - chatIcon.getBoundingClientRect().top;
});

// MOUSE MOVE
document.addEventListener("mousemove", function(e) {
    if (isDragging) {
        hasMoved = true;

        chatIcon.style.left = e.clientX - offsetX + "px";
        chatIcon.style.top = e.clientY - offsetY + "px";
        chatIcon.style.right = "auto";
        chatIcon.style.bottom = "auto";
    }
});

// MOUSE UP

document.addEventListener("mouseup", function() {
    if (!hasMoved) {
        chatContainer.style.display = "flex";
        chatIcon.style.display = "none"; // ẨN icon
    }

    isDragging = false;

    setTimeout(() => {
        hasMoved = false;
    }, 50);
});

// Đóng chat
closeChat.onclick = () => {
    chatContainer.style.display = "none";
    chatIcon.style.display = "flex"; // HIỆN lại icon
};