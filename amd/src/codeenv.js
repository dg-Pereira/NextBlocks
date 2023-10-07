/**
 *
 * @module      mod_nextblocks/env
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* globals Blockly */

/* globals javascript */

import {injectInputBox, replaceCode} from "./lib";

const toolbox = {
    'kind': 'categoryToolbox',
    'readOnly': true,
    'contents': [
        {
            'kind': 'category',
            'name': 'Control',
            'contents': [
                {
                    'kind': 'block',
                    'type': 'controls_if',
                },
                {
                    'kind': 'block',
                    'type': 'controls_repeat_ext',
                },

            ],
        },
        {
            'kind': 'category',
            'name': 'Logic',
            'contents': [
                {
                    'kind': 'block',
                    'type': 'logic_compare',
                },
                {
                    'kind': 'block',
                    'type': 'logic_operation',
                },
                {
                    'kind': 'block',
                    'type': 'logic_boolean',
                },
            ],
        },
        {
            'kind': 'category',
            'name': 'Math',
            'contents': [
                {
                    'kind': 'block',
                    'type': 'math_number',
                },
                {
                    'kind': 'block',
                    'type': 'math_arithmetic',
                },
            ],
        },
        {
            'kind': 'category',
            'name': 'Text',
            'contents': [
                {
                    'kind': 'block',
                    'type': 'text',
                },
                {
                    'kind': 'block',
                    'type': 'text_print',
                },
            ],
        },
        {
            'kind': 'category',
            'name': 'Variables',
            'custom': 'VARIABLE',
        },
        {
            'kind': 'category',
            'name': 'Functions',
            'custom': 'PROCEDURE',
        },
        {
            'kind': 'category',
            'name': 'Input',
            'contents': [
                {
                    'kind': 'block',
                    'type': 'text_input',
                },
            ],
        },
    ],
};
let workspace;

export const init = () => {
    workspace = Blockly.inject('blocklyDiv', {toolbox: toolbox});

    var runButton = document.getElementById('runButton');
    runButton.addEventListener('click', runCode);
};

/**
 *
 */
function runCode() {
    const code = javascript.javascriptGenerator.workspaceToCode(workspace);
    replaceCode(code);
    // eslint-disable-next-line no-eval
    eval(code);
}

Blockly.Blocks.text_input = {
    init: function() {
        this.appendDummyInput()
            .appendField(new Blockly.FieldLabelSerializable("text input"), "text_input");
        this.setOutput(true, "String");
        this.setColour(285);
        this.setTooltip("");
        this.setHelpUrl("");
    }
};

// eslint-disable-next-line no-unused-vars
javascript.javascriptGenerator.forBlock.text_input = function(block, generator) {
    // Spawn text input box
    injectInputBox(1);

    // TODO: find way to wait for input from text box
    const code =
        `(function() {
            let inputString = '';
            const button = document.getElementById('programInputButton1');
            clicked = false;
            button.addEventListener('click', () => {
                inputString = document.getElementById('programInputBox1').value;
                clicked = true;
            });
            while (!clicked) {
                // Wait for input
            }
            return inputString;
        })()`;
    return [code, Blockly.JavaScript.ORDER_NONE];
};
