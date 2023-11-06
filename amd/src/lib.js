/**
 *
 * @module      mod_nextblocks/env
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* globals Blockly */
/* globals javascript */

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
 * @param {string} code the new program code to be displayed
 * @param {number} inputFuncDecsN the number of forced input function declarations
 */
export const replaceCode = (code, inputFuncDecsN) => {
    const codeDiv = document.getElementById('codeDiv');
    codeDiv.innerHTML = formatCodeHTML(code, inputFuncDecsN);
};

/**
 * Formats the code with correct HTML structure to be displayed in the code div
 * TODO: implement this function
 * @param {string} code the code text to be formatted (string literal)
 * @param {number} inputFuncDecsN the number of forced input function declarations
 * @param {boolean} debugMode whether to display the code as is, or with the wrapper function
 * @returns {string} the formatted code
 */
const formatCodeHTML = (code, inputFuncDecsN, debugMode = false) => {
    if (!debugMode) {
        code = removeForcedInputFunctions(code, inputFuncDecsN);
        code = removeOutputString(code);
        code = removeCustomFunctions(code);
        code = removeWrapperFunction(code);
        return "<pre>" + code + "</pre>";
    } else {
        // Code is string literal
        return "<pre>" + code + "</pre>";
    }
};

/**
 * @param {string} code the code to be modified
 * @param {number} inputFuncDecsN the number of forced input function declarations
 * @returns {string} the code without the forced input function declarations
 */
function removeForcedInputFunctions(code, inputFuncDecsN) {
    const lines = code.split('\n');
    lines.splice(0, inputFuncDecsN * 3);
    return lines.join('\n');
}

/**
 * Removes the wrapper function from the code for presentation purposes
 * Removes first two and last three lines
 * @param {String} code the code to be modified
 * @returns {String} the code without the wrapper function
 */
// eslint-disable-next-line no-unused-vars
const removeWrapperFunction = (code) => {
    const lines = code.split('\n');
    lines.splice(0, 2);
    lines.splice(-3);
    return lines.join('\n');
};

const removeCustomFunctions = (code) => {
    const lines = code.split('\n');
    lines.splice(0, 6);
    return lines.join('\n');
};

const removeOutputString = (code) => {
    const lines = code.split('\n');
    lines.splice(0, 1);
    return lines.join('\n');
};

/**
 * Runs the tests on the given workspace and returns an array of booleans, one for each test, indicating whether
 * the test passed or not
 * @param {WorkspaceSvg} workspace the workspace to run the tests on
 * @param {{}} tests the tests to run
 * @param {string} inputFuncDecs the function declarations for the forced input functions
 * @returns {any[]} an array of booleans, one for each test, indicating whether the test passed or not
 */
export const runTests = (workspace, tests, inputFuncDecs) => {
    const code = getWorkspaceCode(workspace, inputFuncDecs);
    let results = [];
    tests.forEach((test) => {
        let thisTestCode = code; // Need to copy, so that the code is not modified for the next test
        const inputs = test.inputs;
        inputs.forEach((input) => {
            const prompt = Object.keys(input)[0];
            const values = input[prompt];

            const inputIndex = thisTestCode.lastIndexOf(prompt);
            // Get index of first string literal after prompt
            let inputQuote1 = thisTestCode.indexOf('(', inputIndex);
            const inputQuote2 = thisTestCode.indexOf(')', inputQuote1 + 1);

            const preStr = thisTestCode.substring(0, inputQuote1 + 1);
            const postStr = thisTestCode.substring(inputQuote2);

            thisTestCode = preStr + values[0] + postStr;

        });
        // eslint-disable-next-line no-eval
        let codeOutput = silentRunCode(thisTestCode);
        codeOutput = codeOutput.replace(/\s/g, '');
        const result = codeOutput;
        results.push(result);
    });
    return results;
};

/**
 * @param {String} code The Javascript code to be run
 * @returns {any} The output of the code
 * Runs the code and returns the output, does not display it
 * TODO: do something other than use eval
 */
export function silentRunCode(code) {
    // eslint-disable-next-line no-eval
    return eval(code);
}

/**
 * @param {WorkspaceSvg} workspace the workspace to get the code from
 * @param {string} inputFuncDecs
 * @returns {string} the code generated by Blockly for the current workspace
 *
 * Returns the Javascript code string generated by Blockly, with the necessary wrapping code
 */
export const getWorkspaceCode = (workspace, inputFuncDecs) => {
    let code = javascript.javascriptGenerator.workspaceToCode(workspace);
    javascript.javascriptGenerator.addReservedWords("print, input");
    const preamble = inputFuncDecs + `\nlet outputString = \`\`;\n
function print(string) {
    outputString += string + '\\n';
}
function input(prompt) {
    return prompt;
}
(function () {
    `;
    const postscript = `return outputString;
})();\n`;

    code = preamble;
    let blocks = workspace.getTopBlocks(true);
    for (var b = 0; b < blocks.length; b++) {
        var block = blocks[b];

        if (block.type === 'start') {
            code += generateDescendantsCode(block);
            break;
        }
    }
    code += postscript;
    return code;
};

/**
 * @param {BlockSVG} block the block whose descendants are to have their code generated
 * @returns {string} the code generated by Blockly for the descendants of the given block
 */
function generateDescendantsCode(block) {
    let descendants = block.getChildren(true);
    let descendantsCode = '';
    for (let i = 0; i < descendants.length; i++) {
        let descendant = descendants[i];
        descendantsCode += Blockly.JavaScript.blockToCode(descendant);
    }
    return descendantsCode;
}

/**
 * Inserts the test results accordion in the area above the Run and Tests buttons
 * @param {any[]} results the results of the tests (pass/fail)
 * @param {{}} testsJSON the tests that were run (for displaying the inputs and outputs)
 * @returns {string} the HTML for the accordion
 */
export const testsAccordion = (results, testsJSON) => {
    const testCaseCount = testsJSON.length;

    let accordion = '<div style="max-height: 100%; overflow-y: auto;">';

    for (let i = 0; i < testCaseCount; i++) {
        accordion += '<details class="card">';
        accordion += '<summary class="card-header">';
        accordion += 'Test ' + (i + 1);
        // Show if test passed or failed
        if (results[i] === testsJSON[i].output) {
            accordion += '<span class="badge badge-success float-right">Passed</span>';
        } else {
            accordion += '<span class="badge badge-danger float-right">Failed</span>';
        }
        accordion += '</summary>';
        accordion += '<div class="card-body pt-0 pb-0 pl-2 pr-2">';
        // eslint-disable-next-line no-loop-func
        testsJSON[i].inputs.forEach((input) => {
            for (const key in input) {
                accordion += '<p class="pt-2 m-0">' + key + ': </p>';
                accordion += '<pre class="mt-1 mb-0 test-input">' + input[key][0] + '</pre>';
            }
        });
        accordion += '<p class="pt-2 border-top mt-2 mb-0">Test output: </p>';
        accordion += '<pre class="mt-1 mb-0 mr-0 ml-0 test-output">' + testsJSON[i].output + '</pre>';
        accordion += '<div class="p-0">';
        accordion += '<p class="pt-2 m-0">Your output: </p>';
        accordion += '<pre class="pb-2 mt-1 mb-0 ml-0 mr-0 test-output">' + results[i] + '</pre>';
        accordion += '</div>';
        accordion += '</details>';
    }

    accordion += '</div>';
    return accordion;
};
