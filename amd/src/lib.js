/**
 *
 * @module      mod_nextblocks/env
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
 * @param {String} code the new program code to be displayed
 */
export const replaceCode = (code) => {
    const codeDiv = document.getElementById('codeDiv');
    codeDiv.innerHTML = formatCodeHTML(code);
};

/**
 * Formats the code with correct html structure to be displayed in the code div
 * TODO: implement this function
 * @param {String} code the code text to be formatted (string literal)
 * @returns {String} the formatted code
 */
const formatCodeHTML = (code) => {
    // Code is string literal
    return "<pre>" + code + "</pre>";
};

// Maybe in the future write regular expression to validate the tests file
// Consider doing parsing on the server side, when the file is submitted
// TODO a more formal file format description
/**
 * Parses the tests file and returns a json object with the tests data
 * @param {String} fileString the string containing the contents of the tests file
 * @returns {{}} A JSON object with the tests data
 */
export const parseTestsFile = (fileString) => {
    try {
        // The returned object has a list of test cases
        let jsonReturn = [];

        // Different test cases are separated by |
        let testCases = fileString.split("|");

        testCases.forEach((testCase) => {
            // Each test case contains a list of inputs (and an output)
            let thisTestCaseJson = {};
            thisTestCaseJson.inputs = [];

            // The input and output of the test are separated by -
            let inputOutput = testCase.split("-");
            let inputs = inputOutput[0];
            thisTestCaseJson.output = inputOutput[1].trim(); // Remove newlines and add output of test to JSON

            inputs.split("_").forEach((input) => {
                if (input.length < 3) { // Skip junk elements
                    return;
                }
                // Each input has multiple lines. The first line is the input name, the prompt, and the rest are
                // the input values for that input
                let inputLines = input.split(/\n/).map((line) => line.trim()); // Remove junk line breaks from every line
                inputLines = inputLines.slice(1, inputLines.length - 1); // First and last lines are junk
                // Contains the input prompt and a list of input values
                let thisInputJson = {};
                thisInputJson[inputLines[0]] = inputLines.slice(1);
                thisTestCaseJson.inputs.push(thisInputJson); // Add this input to the list of inputs of this test case
            });
            jsonReturn.push(thisTestCaseJson); // Add this test case to the list of test cases
        });
        return jsonReturn;
    } catch (e) {
        throw new Error("Error parsing tests file: " + e);
    }
};

/**
 * Runs the tests on the given workspace and returns an array of booleans, one for each test, indicating whether
 * the test passed or not
 * @param {WorkspaceSvg} workspace the workspace to run the tests on
 * @param {{}} tests the tests to run
 * @returns {Boolean[]} an array of booleans, one for each test, indicating whether the test passed or not
 */
export const runTests = (workspace, tests) => {
    // eslint-disable-next-line no-unused-vars
    const code = getWorkspaceCode(workspace);
    let results = [];
    tests.forEach((test) => {
        let thisTestCode = code; // Need to copy, so that the code is not modified for the next test
        const inputs = test.inputs;
        inputs.forEach((input) => {
            // eslint-disable-next-line no-console
            const prompt = Object.keys(input)[0];
            const values = input[prompt];

            const inputIndex = thisTestCode.indexOf(prompt);
            // Get index of first string literal after prompt
            let inputQuote1 = thisTestCode.indexOf('"', inputIndex);
            const inputQuote2 = thisTestCode.indexOf('"', inputQuote1 + 1);

            const preStr = thisTestCode.substring(0, inputQuote1 + 1);
            const postStr = thisTestCode.substring(inputQuote2);

            thisTestCode = preStr + values[0] + postStr;

        });
        // eslint-disable-next-line no-unused-vars
        const testOutput = test.output;
        // eslint-disable-next-line no-eval
        const codeOutput = eval(thisTestCode);
        results.push(testOutput === codeOutput);
    });
    return results;
};

/**
 * @param {WorkspaceSvg} workspace the workspace to get the code from
 * @returns {String} the code generated by Blockly for the current workspace
 *
 * Returns the Javascript code string generated by Blockly, with the necessary wrapping code
 */
export const getWorkspaceCode = (workspace) => {
    let code = javascript.javascriptGenerator.workspaceToCode(workspace);
    const preamble = `(function () {
    let outputString = \`\`;\n`;
    const postscript = `return outputString;
})();\n`;
    // Add a preamble and a postscript to the code.
    code = preamble + code + postscript;
    return code;
};
