/**
 *
 * @module      mod_nextblocks/chat
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        /**
         *
         * @param {string} userName username of the user
         * @param {number} activityId id of the activity
         * @param {function(string, string, number, number): void} saveMessage function to save the message in the database
         * @param {string} serverUrl url of the chat server to connect to
         * */
        run: function(userName, activityId, saveMessage, serverUrl = 'ws://localhost:8060') {
            const socket = new WebSocket(serverUrl);
            socket.addEventListener("open", () => chatSetup(socket, userName, activityId, saveMessage));
            socket.addEventListener("message", (event) => appendMessage(event.data, activityId));
            socket.addEventListener("close", () => socketError(activityId, "Connection closed by server"));
            socket.addEventListener("error", () => socketError(activityId));
        },

        /**
         * Populates the chat box with the last 100 messages from the database
         * @param {function(number, number): Promise} getMessages function to fetch the messages from the database
         * @param {number} activityId id of the activity
         */
        populate: function(getMessages, activityId) {
            // Get last 100 messages from database
            const messagesPromise = getMessages(100, activityId);

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

const socketError = function(activityId, errorMessage = "Connection error") {
    const errorJSON = {type: "error", sender: "System", text: errorMessage, activity: activityId, timestamp: Date.now()};
    appendMessage(errorJSON, activityId, true);
};

/**
 * Adds a message to the chat box
 * @param {string | {type: string, sender: string, text: string, activity: number, timestamp: number}} message message to append
 * @param {number} activityId id of the activity. If the message is not for this activity, it is not appended
 * @param {boolean} isParsed true if the message is already in JSON format, false otherwise
 * @throws {Error} if the message is not in a valid JSON format
 */
const appendMessage = function(message, activityId, isParsed = false) {
    if (!isParsed) {
        message = parseMessage(message);
    }
    if (activityId === message.activity) {
        const chatDiv = document.getElementById('messages');
        const timestampDate = new Date(message.activity);
        chatDiv.innerHTML += `<p>(${timestampDate.getHours()}:${timestampDate.getMinutes()}) 
            ${message.sender}: ${message.text}</p>`;
    }
};

/**
 * Parses a message from a string to a JSON object
 * @param {string} message string to be parsed
 * @returns {{type: string, sender: string, text: string, activity: number, timestamp: number}}
 * @throws {Error} if the message is not in a valid JSON format
 */
const parseMessage = function(message) {
    let msg;
    try {
        msg = JSON.parse(message);
    } catch (e) {
        throw new Error("Invalid message format");
    }
    return msg;
};

/**
 * Sets up the listener for sending messages. Also stores the message in the database, using the saveMessage function.
 * @param {WebSocket} socket websocket object to send and receive messages
 * @param {string} userName username of the user
 * @param {number} activityId id of the activity
 * @param {function(string, string, number, number): void} saveMessage function to save the message in the database
 */
const chatSetup = function(socket, userName, activityId, saveMessage) {
    const msgForm = document.querySelector('form.msg-form');

    const msgFormSubmit = (event) => {
        event.preventDefault();

        const msgField = document.getElementById('msg');
        const msgText = msgField.value;
        const timestamp = Date.now();

        // Store message in database. Ajax is asynchronous, so it might be faster to execute this before sending the message
        saveMessage(msgText, userName, activityId, timestamp);

        // Prepare and send message to websocket
        let msg = {
            type: "normal",
            sender: userName,
            text: msgText,
            activity: activityId,
            timestamp: timestamp
        };
        msg = JSON.stringify(msg);
        socket.send(msg);

        msgField.value = ''; // Clear message field in the form
    };

    msgForm.addEventListener('submit', (event) => msgFormSubmit(event, socket));
};