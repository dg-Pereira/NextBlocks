import {call as fetchMany} from 'core/ajax';

export const submitGradingForm = (userid, nextblocksid, saved_workspace) => fetchMany([{
    methodname: 'mod_nextblocks_save_workspace',
    args: {
        userid: userid,
        nextblocksid: nextblocksid,
        saved_workspace: saved_workspace,
    },
}])[0];
