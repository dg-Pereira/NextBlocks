/**
 *
 * @module      mod_nextblocks/env
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* globals Blockly */

export const injectInputBox = () => {
    const inputBox = document.createElement('input');
    inputBox.setAttribute('type', 'text');
    inputBox.setAttribute('id', 'programInputBox');

    const blocklyDiv = document.getElementById('blocklyDiv');
    blocklyDiv.insertBefore(inputBox, Blockly.getMainWorkspace().firstChild);
};