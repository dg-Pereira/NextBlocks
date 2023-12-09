/**
 *
 * @module      mod_nextblocks/repository
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// eslint-disable-next-line no-unused-vars
define(['core/ajax'], function(ajax) {
    return {
        saveWorkspace: function(nextblocksid, saved_workspace) {
            return ajax.call([{
                methodname: 'mod_nextblocks_save_workspace',
                args: {
                    nextblocksid: nextblocksid,
                    saved_workspace: saved_workspace,
                },
            }])[0];
        },

        submitWorkspace: function(nextblocksid, submitted_workspace) {
            return ajax.call([{
                methodname: 'mod_nextblocks_submit_workspace',
                args: {
                    nextblocksid: nextblocksid,
                    submitted_workspace: submitted_workspace,
                },
            }])[0];
        },
    };
});
