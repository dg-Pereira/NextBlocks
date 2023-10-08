/**
 *
 * @module      mod_nextblocks/env
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* globals Blockly */

/* globals javascript */

import {replaceCode} from "./lib";

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
                {
                    'kind': 'block',
                    'type': 'text_multiline_input',
                },
            ],
        },
    ],
};

const options = {
    toolbox: toolbox,
    collapse: true,
    comments: true,
    disable: true,
    maxBlocks: Infinity,
    trashcan: true,
    horizontalLayout: false,
    toolboxPosition: 'start',
    css: true,
    media: 'https://blockly-demo.appspot.com/static/media/',
    rtl: false,
    scrollbars: true,
    sounds: true,
    oneBasedIndex: false,
    grid: {
        spacing: 20,
        length: 1,
        colour: '#888',
        snap: false,
    },
    zoom: {
        controls: true,
        wheel: true,
        startScale: 1,
        maxScale: 3,
        minScale: 0.3,
        scaleSpeed: 1.2,
    },
};

let workspace;

export const init = () => {
    workspace = Blockly.inject('blocklyDiv', options);

    const runButton = document.getElementById('runButton');
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
            .appendField("text input:")
            .appendField(new Blockly.FieldTextInput('text'),
                'text_input');
        this.setOutput(true, "String");
        this.setColour(285);
        this.setTooltip("");
        this.setHelpUrl("");
    }
};

Blockly.Blocks.text_multiline_input = {
    init: function() {
        this.appendDummyInput()
            .appendField("multiline text input:")
            .appendField(new Blockly.FieldMultilineInput('multiline \n text'),
                'text_input');
        this.setOutput(true, "String");
        this.setColour(285);
        this.setTooltip("");
        this.setHelpUrl("");
    }
};

// eslint-disable-next-line no-unused-vars
javascript.javascriptGenerator.forBlock.text_input = function(block, generator) {
    const text = block.getFieldValue('text_input');
    let code = '"' + text + '"';
    return [code, Blockly.JavaScript.ORDER_NONE];
};

// eslint-disable-next-line no-unused-vars
javascript.javascriptGenerator.forBlock.text_multiline_input = function(block, generator) {
    const text = block.getFieldValue('text_input');
    let code = "`" + text + "`";
    return [code, Blockly.JavaScript.ORDER_NONE];
};
