/**
 *
 * @module      mod_nextblocks/chat
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        runChat: function(userName, activityId, repository) {
            const socket = new WebSocket('ws://localhost:8060');
            socket.addEventListener("open", () => chatSetup(socket, userName, activityId, repository));
            socket.addEventListener("message", (event) => appendMessage(event.data, activityId));
        },

        populateChat: function(repository, activityId) {
            // Get last 100 messages from database
            const messagesPromise = repository.getMessages(100, activityId);

            messagesPromise.then((messages) => {
                // Add messages to chat box
                messages.forEach((dbMessage) => {
                    const message = {type: "dbMessage", sender: dbMessage.username, text: dbMessage.message, activity: activityId,
                        timestamp: dbMessage.timestamp};
                    appendMessage(message, activityId, true);
                });
            });
        },
    };
});

const sendMessage = function(message, socket) {
    socket.send(message);
};

const appendMessage = function(message, activityId, isParsed = false) {
    if (!isParsed) {
        message = parseMessage(message);
    }
    if (activityId === message.activity) {
        const chatDiv = document.getElementById('messages');
        chatDiv.innerHTML += `<p>(${new Date(message.timestamp).getHours()}:${new Date(message.timestamp).getMinutes()}) 
            ${message.sender}: ${message.text}</p>`;
    }
};

const parseMessage = function(message) {
    let msg = {type: "", sender: "", text: "", activity: ""};
    try {
        msg = JSON.parse(message);
    } catch (e) {
        return false;
    }
    return msg;
};

const chatSetup = function(socket, userName, activityId, repository) {
    const msgForm = document.querySelector('form.msg-form');

    const msgFormSubmit = (event) => {
        event.preventDefault();
        const msgField = document.getElementById('msg');
        const msgText = msgField.value;
        const timestamp = Date.now();

        // Store message in database. Ajax is asynchronous, so it might be faster to execute this before sending the message
        // eslint-disable-next-line no-console
        repository.saveMessage(msgText, userName, activityId, timestamp);

        // Prepare and send message to websocket
        let msg = {
            type: "normal",
            sender: userName,
            text: msgText,
            activity: activityId,
            timestamp: timestamp
        };
        msg = JSON.stringify(msg);
        sendMessage(msg, socket);
        msgField.value = '';
    };

    msgForm.addEventListener('submit', (event) => msgFormSubmit(event, socket));
};