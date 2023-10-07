/**
 *
 * @module      mod_nextblocks/env
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Injects the box for inserting the program input below the blockly area
 * @param {Number} i the index of the input box, to avoid repetition of ids
 */
export const injectInputBox = (i) => {
    // Create input box for program input
    const inputBox = document.createElement('input');
    inputBox.setAttribute('type', 'text');
    inputBox.setAttribute('id', 'programInputBox' + i);

    // Create button to submit input
    const submitButton = document.createElement('button');
    submitButton.setAttribute('id', 'programInputButton' + i);
    submitButton.setAttribute('type', 'button');
    submitButton.innerHTML = 'Submit';

    // Insert input box and button below blockly area
    const blocklyDiv = document.getElementById('blocklyDiv');
    blocklyDiv.insertAdjacentElement('afterend', inputBox);
    inputBox.insertAdjacentElement('afterend', submitButton);
};

/**
 * Inserts the new program code in the code div below the blockly area, replacing the old one if it exists
 * @param {String} code the new program code to be displayed
 */
export const replaceCode = (code) => {
    const codeDiv = document.getElementById('codeDiv');
    codeDiv.innerHTML = formatCodeHTML(code);
};

/**
 * Formats the code with correct html structure to be displayed in the code div
 * TODO: implement this function
 * @param {String} code the code text to be formatted
 * @returns {String} the formatted code
 */
const formatCodeHTML = (code) => {
    return '<pre>' + code + ';</pre>';
};
