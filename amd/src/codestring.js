/**
 *
 * @module      mod_nextblocks/codestring
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

    class CodeString {
        #codeString;
        #userFunctionLinesCount;

        static #auxFunctions = `function print(string) {
    outputString += string + '\\n';
}
function input(prompt) {
    return prompt;
}
`;
        static #codeEnding = `return outputString;
})();
`;

        constructor(codeString) {
            if (arguments.length > 0) {
                this.#codeString = codeString;
            } else {
                this.#codeString = '';
            }
            this.#userFunctionLinesCount = 0;
        }

        getCompleteCodeString() {
            return this.#codeString;
        }

        getPrintableCodeString() {
            // Split code by unescaped line breaks (code might have escaped line breaks)
            const codeLines = this.#codeString.split(/(?<!\\)\n/);

            // Add lines from user functions
            const functionLines = codeLines.slice(0, this.#userFunctionLinesCount);

            // Add lines from start block
            const startIndex = codeLines.findIndex(line => line.includes('(function () {')) + 1;
            const endIndex = codeLines.findIndex(line => line.includes('return outputString;'));
            const startCodeLines = codeLines.slice(startIndex, endIndex);

            return functionLines.concat(startCodeLines).join('\n');
        }

        addVariable(variableName, variableValue) {
            // Check if variableName is a valid variable name
            const regex = /^[a-zA-Z_][a-zA-Z0-9_]*$/;
            if (!regex.test(variableName)) {
                throw new Error('Invalid variable name');
            }
            this.#codeString += 'let ' + variableName + ' = ' + variableValue + ';\n';
            return this.#codeString;
        }

        addLine(line) {
            // Check if line does not have line break
            if (line.includes('\n')) {
                throw new Error('Invalid line');
            }
            this.#codeString += line + '\n';
            return this.#codeString;
        }

        addEnding() {
            this.#codeString += CodeString.#codeEnding;
            return this.#codeString;
        }

        addAuxFunctions(inputFuncDecs) {
            const auxFunctions = inputFuncDecs + CodeString.#auxFunctions;
            this.#codeString += auxFunctions;
            return this.#codeString;
        }

        addMainCode(codeString) {
            this.#codeString += codeString;
            return this.#codeString;
        }

        addFunction(functionCode) {
            // Update user function lines count
            const regex = /(?<!\\)\n/g;
            const functionLinesCount = (functionCode.match(regex) || []).length;
            this.#userFunctionLinesCount += functionLinesCount;

            this.#codeString = functionCode + this.#codeString;
            return this.#codeString;
        }
    }
    return CodeString;
});
