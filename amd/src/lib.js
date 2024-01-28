/**
 *
 * @module      mod_nextblocks/lib
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* globals Blockly */

/* globals javascript */

define(['mod_nextblocks/codestring'], function(CodeString) {
    /**
     * @param {BlockSvg} block the block whose code is to be generated
     * @returns {string} the code generated by Blockly for the given block
     */
    function generateFunctionCode(block) {
        Blockly.JavaScript.blockToCode(block);
        // Get function name
        let functionName = '%' + javascript.javascriptGenerator.nameDB_.getName(block.getFieldValue('NAME'),
            Blockly.Procedures.NAME_TYPE);
        functionName = functionName.substring(0, functionName.length - 1);
        return javascript.javascriptGenerator.definitions_[functionName] + '\n';
    }

    /**
     * @param {BlockSvg} block the block whose descendants are to have their code generated
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
     * @param {String} input the code to extract the function names from
     * @returns {String[]} the names of the functions declared in the given code
     */
    function extractFunctionNames(input) {
        const regex = /function\s+(\w+)\s*\(/g;
        const functionNames = [];
        let match;

        while ((match = regex.exec(input)) !== null) {
            functionNames.push(match[1]);
        }

        return functionNames;
    }

    /**
     * Formats the code with correct HTML structure to be displayed in the code div
     * TODO: implement this function
     * @param {CodeString} code the code text to be formatted (string literal)
     * @param {boolean} debugMode whether to display the code as is, or with the wrapper function
     * @returns {string} the formatted code
     */
    const formatCodeHTML = (code, debugMode = true) => {
        if (!debugMode) {
            return '<pre>' + code.getPrintableCodeString() + '</pre>';
        } else {
            return '<pre>' + code.getCompleteCodeString() + '</pre>';
        }
    };

    return {
        /**
         * @param {String} code The Javascript code to be run
         * @returns {any} The output of the code
         * Runs the code and returns the output, does not display it
         * TODO: do something other than use eval
         */
        silentRunCode: function(code) {
            // eslint-disable-next-line no-eval
            return eval(code);
        },

        /**
         * Inserts the new program code in the code div below the blockly area, replacing the old one if it exists
         * @param {CodeString} code the new program code to be displayed
         */
        replaceCode: function(code) {
            const codeDiv = document.getElementById('codeDiv');
            codeDiv.innerHTML = formatCodeHTML(code);
        },

        /**
         * Inserts the test results accordion in the area above the Run and Tests buttons
         * @param {any[]|null} results the results of the tests (pass/fail)
         * @param {{}} testsJSON the tests that were run (for displaying the inputs and outputs)
         * @param {String[]} uncalledInputFuncs the input functions that were not called.
         * Note: if this is not empty, results is null
         * @returns {string} the HTML for the accordion
         */
        testsAccordion: function(results, testsJSON, uncalledInputFuncs) {

            const testCaseCount = testsJSON.length;

            let accordion = '<div style="max-height: 100%; overflow-y: auto;">';
            if (results === null) {
                accordion += '<div class="alert alert-warning" role="alert">';
                accordion += 'Not all input functions were called. No tests will be run.';
                // Show which input functions were not called
                accordion += '<br>Input functions not called: ' + uncalledInputFuncs.join(', ');
                accordion += '</div>';
            }

            for (let i = 0; i < testCaseCount; i++) {
                accordion += '<details class="card">';
                accordion += '<summary class="card-header">';
                accordion += 'Test ' + (i + 1);
                // Show if test passed or failed
                if (results === null || results[i] === undefined) {
                    accordion += '<span class="badge badge-warning float-right">Not run</span>';
                } else if (results[i] === testsJSON[i].output) {
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
                        accordion += '<pre class="mt-1 mb-0 test-input">' + Object.values(input[key])[0][0] + '</pre>';
                    }
                });
                accordion += '<p class="pt-2 border-top mt-2 mb-0">Test output: </p>';
                accordion += '<pre class="mt-1 mb-0 mr-0 ml-0 test-output">' + testsJSON[i].output + '</pre>';
                accordion += '<div class="p-0">';
                accordion += '<p class="pt-2 m-0">Your output: </p>';
                if (results === null) {
                    accordion += '<pre class="mt-1 mb-0 mr-0 ml-0 test-output">Not run</pre>';
                } else {
                    accordion += '<pre class="pb-2 mt-1 mb-0 ml-0 mr-0 test-output">' + results[i] + '</pre>';
                }
                accordion += '</div>';
                accordion += '</details>';
            }

            accordion += '</div>';
            return accordion;
        },

        /**
         * Runs the tests on the given workspace and returns an array of booleans, one for each test, indicating whether
         * the test passed or not
         * @param {String} code the workspace to run the tests on
         * @param {{}} tests the tests to run
         * @returns {String[]} the output of each test
         */
        runTests: function(code, tests) {
            let results = [];
            tests.forEach((test) => {
                let thisTestCode = code; // Need to copy, so that the code is not modified for the next test
                const inputs = test.inputs;
                inputs.forEach((input) => {
                    const prompt = Object.keys(input)[0];
                    const values = Object.values(input[prompt])[0];

                    const inputIndex = thisTestCode.lastIndexOf(prompt);
                    // Get the indexes of the first and second parentheses of the input function call
                    const inputParentheses1 = thisTestCode.indexOf('(', inputIndex);
                    const inputParentheses2 = thisTestCode.indexOf(')', inputParentheses1 + 1);

                    const preStr = thisTestCode.substring(0, inputParentheses1 + 1);
                    const postStr = thisTestCode.substring(inputParentheses2);

                    thisTestCode = preStr + values[0] + postStr;
                });
                let codeOutput = this.silentRunCode(thisTestCode);
                codeOutput = codeOutput.replace(/\s/g, '');
                const result = codeOutput;
                results.push(result);
            });
            return results;
        },

        /**
         * @param {String} code the code to check for input function calls
         * @param {string} inputFuncDecs the function declarations for the forced input functions
         * @returns {String[]} whether the code has all input function calls
         */
        getMissingInputCalls: function(code, inputFuncDecs) {

            // Regex to match input function calls outside of comments
            const regex = /((?!\/\/ ).{3}|^.{0,2})\binput\w+\s*\([^)]*\)(?=\s*;|\s*\)|\s*[,)])/g;
            const functionDecNames = extractFunctionNames(inputFuncDecs);
            const matches = code.match(regex);

            if (matches === null) {
                return functionDecNames;
            }
            const functionCallNames = matches.map((match) => match.match(/\b(\w+)(?=\s*\()/g)).flat();

            // Return all function declarations that are not called
            return functionDecNames.filter((name) => !functionCallNames.includes(name));
        },

        /**
         * @param {WorkspaceSvg} workspace the workspace to get the code from
         * @param {string} inputFuncDecs
         * @returns {CodeString} the code generated by Blockly for the current workspace
         *
         * Returns the Javascript code string generated by Blockly, with the necessary wrapping code
         */
        getWorkspaceCode: function(workspace, inputFuncDecs) {
            javascript.javascriptGenerator.addReservedWords('print, input');
            let codeString = new CodeString();

            codeString.addAuxFunctions(inputFuncDecs);
            codeString.addVariable('outputString', '""');

            const functionOpen = '(function () {';
            codeString.addLine(functionOpen);

            let blocks = workspace.getTopBlocks(true);
            for (let b = 0; b < blocks.length; b++) {
                const block = blocks[b];
                if (block.type === 'start') {
                    codeString.addMainCode(generateDescendantsCode(block));
                }
                if (block.type === 'procedures_defnoreturn' || block.type === 'procedures_defreturn') {
                    codeString.addFunction(generateFunctionCode(block));
                }
            }
            codeString.addEnding();
            return codeString;
        },
    };
});
