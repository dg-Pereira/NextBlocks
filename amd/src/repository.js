import {call as fetchMany} from 'core/ajax';

export const saveWorkspace = (nextblocksid, saved_workspace) => fetchMany([{
    methodname: 'mod_nextblocks_save_workspace',
    args: {
        nextblocksid: nextblocksid,
        saved_workspace: saved_workspace,
    },
}])[0];
