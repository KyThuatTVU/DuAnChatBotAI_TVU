# Voice Search Guide

## Overview

CELRAS TVU Chatbot has been integrated with voice search functionality, allowing users to ask questions by speaking instead of typing.

## Features

### 1. Automatic Speech Recognition
- Supports Vietnamese and English
- Automatically switches recognition language based on interface language settings
- Displays real-time recognition results

### 2. Auto-send Messages
- After recognition is complete, the message is automatically sent to the chatbot
- No need to manually press the send button

### 3. Intuitive Interface
- Microphone button with animation effect while listening
- Clear error notifications if issues occur

## How to Use

### Step 1: Click the Microphone Button
- Find the microphone button (🎤) next to the send message button
- Click the button to start voice recognition

### Step 2: Speak Your Question
- When the button turns red with a pulse effect, you can start speaking
- Speak clearly and at a moderate pace
- Example: "What time does the library open?"

### Step 3: Wait for Results
- The system will automatically recognize and fill your question into the input box
- The message will be automatically sent after recognition is complete

## Technical Requirements

### Supported Browsers
- ✅ Google Chrome (recommended)
- ✅ Microsoft Edge
- ✅ Safari (iOS 14.5+)
- ✅ Opera
- ❌ Firefox (not fully supported)

### Access Permissions
- Requires microphone access permission for the browser
- On first use, the browser will request microphone access permission

## Error Handling

### "No speech detected" Error
**Causes:**
- Microphone not working
- Speaking too softly or environment too noisy
- Not speaking within the waiting time

**Solutions:**
- Check that the microphone is working
- Speak louder and more clearly
- Try again immediately after clicking the button

### "Microphone access denied" Error
**Causes:**
- Microphone access permission not granted to the browser

**Solutions:**
1. Click the lock/info icon to the left of the address bar
2. Find "Microphone" and select "Allow"
3. Reload the page and try again

### "Browser does not support speech recognition" Error
**Causes:**
- Browser does not support Web Speech API

**Solutions:**
- Switch to using Google Chrome or Microsoft Edge
- Update browser to the latest version

## Supported Languages

### Vietnamese (vi-VN)
- Recognizes standard Vietnamese speech
- Supports Northern, Central, and Southern accents
- High accuracy with short, concise questions

### English (en-US)
- Recognizes American English speech
- Automatically switches when user selects EN language

## Tips for Effective Use

1. **Speak clearly and slowly**: Helps the system recognize more accurately
2. **Quiet environment**: Reduces noise to increase accuracy
3. **Short questions**: Long questions may be cut off or misrecognized
4. **Check results**: Review the recognized text before sending (if editing is needed, you can stop and type manually)

## Technical Implementation

### Web Speech API
The feature uses the browser's Web Speech API:
- `SpeechRecognition` or `webkitSpeechRecognition`
- No need to install additional plugins or extensions
- Fully processed on the browser (client-side)

### Configuration
```javascript
recognition.continuous = false;      // Stop after receiving result
recognition.interimResults = true;   // Display interim results
recognition.lang = 'en-US';          // Recognition language
```

### Security
- Does not record or store voice
- Only processes recognized text
- Complies with browser privacy policies

## Support Contact

If you encounter issues using the voice feature, please contact:

📧 Email: trungtamhoclieu@tvu.edu.vn  
📞 Phone: 0294 3855 246 (ext. 142)  
🏢 Address: Learning Resource Center & Academic Support, Tra Vinh University

---

**Version:** 1.0  
**Last Updated:** March 10, 2026
