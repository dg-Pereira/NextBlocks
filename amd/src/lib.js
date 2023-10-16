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

// eslint-disable-next-line no-unused-vars
export const runTests = (contents) => {
    // eslint-disable-next-line no-unused-vars
    const tests = parseTestsFile(contents);
    // eslint-disable-next-line no-console
    console.log(tests);
};