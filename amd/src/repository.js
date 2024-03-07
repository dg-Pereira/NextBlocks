/**
 *
 * @module      mod_nextblocks/repository
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax'], function(ajax) {
    return {
        saveWorkspace: function(nextblocksid, saved_workspace, userid = null) {
            return ajax.call([{
                methodname: 'mod_nextblocks_save_workspace',
                args: {
                    nextblocksid: nextblocksid,
                    saved_workspace: saved_workspace,
                    userid: userid,
                },
            }])[0];
        },

        submitWorkspace: function(nextblocksid, submitted_workspace, codeString) {
            return ajax.call([{
                methodname: 'mod_nextblocks_submit_workspace',
                args: {
                    nextblocksid: nextblocksid,
                    submitted_workspace: submitted_workspace,
                    codeString: codeString,
                },
            }])[0];
        },

        submitReaction: function(nextblocksid, reaction) {
            return ajax.call([{
                methodname: 'mod_nextblocks_submit_reaction',
                args: {
                    nextblocksid: nextblocksid,
                    reaction: reaction,
                },
            }])[0];
        },

        saveMessage: function(message, userName, nextblocksId, timestamp) {
            return ajax.call([{
                methodname: 'mod_nextblocks_save_message',
                args: {
                    message: message,
                    userName: userName,
                    nextblocksId: nextblocksId,
                    timestamp: timestamp,
                },
            }])[0];
        },

        getMessages: function(messageCount, nextblocksId) {
            return ajax.call([{
                methodname: 'mod_nextblocks_get_messages',
                args: {
                    messageCount: messageCount,
                    nextblocksId: nextblocksId,
                },
            }])[0];
        }
    };
});
