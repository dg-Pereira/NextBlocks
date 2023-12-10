/**
 *
 * @module      mod_nextblocks/codeenv
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* globals Blockly */

/* globals javascript */

let toolbox = {
    'kind': 'categoryToolbox',
    'readOnly': true,
    'contents': [
        {
            'kind': 'toolboxlabel',
            'name': 'NextBlocks',
            'colour': 'darkslategrey'
        },
        {
            'kind': 'category',
            'name': 'Logic',
            'colour': '5b80a5',
            "cssConfig": {
                'icon': 'customIcon fa fa-cog',
            },
            'contents': [
                {
                    'kind': 'block',
                    'type': 'controls_if',
                },
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
            'colour': '5b67a5',
            "cssConfig": {
                'icon': 'customIcon fa-solid fa-plus-minus',
            },
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
            'colour': '5ba58c',
            "cssConfig": {
                'icon': 'customIcon fa-solid fa-font',
            },
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
            'colour': 'a55b80',
            "cssConfig": {
                'icon': 'customIcon fa-solid fa-clipboard-list',
            },
            'custom': 'VARIABLE',
        },
        {
            'kind': 'category',
            'name': 'Functions',
            'colour': '995ba5',
            "cssConfig": {
                'icon': 'customIcon fa-solid fa-code',
            },
            'custom': 'PROCEDURE',
        },
        {
            'kind': 'category',
            'name': 'Input',
            'colour': '180',
            "cssConfig": {
                'icon': 'customIcon fa-solid fa-keyboard',
            },
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

// GetMainWorkspace might remove need for global variable
let nextblocksWorkspace;

define(['mod_nextblocks/lib', 'mod_nextblocks/repository'], function(lib, repository) {
    /**
     * @param {CodeString} code The Javascript code to be run
     * Runs the code and displays the output in the output div
     */
    function runCode(code) {
        const output = lib.silentRunCode(code.getCompleteCodeString());
        // Replace newlines with <br /> so that they are displayed correctly
        const outputHTML = output.replace(/\n/g, "<br />");
        const outputDiv = document.getElementById('output-div');
        // Wrap the output in a div with max-height and overflow-y: auto to make it scrollable if too long (multiline input)
        outputDiv.innerHTML = `<div style="max-height: 100%; overflow-y: auto;"><pre>${outputHTML}</pre></div>`;
    }

    /**
     * Saves the current state of the workspace to the database, for later retrieval and display
     */
    const saveState = () => {
        const state = Blockly.serialization.workspaces.save(nextblocksWorkspace);
        // eslint-disable-next-line no-unused-vars
        const stateB64 = btoa(JSON.stringify(state));
        // eslint-disable-next-line no-unused-vars
        const cmid = getCMID();
        repository.saveWorkspace(cmid, stateB64);
    };

    const submitWorkspace = () => {
        const codeString = lib.getWorkspaceCode(nextblocksWorkspace, "").getSubmittableCodeString();
        const state = Blockly.serialization.workspaces.save(nextblocksWorkspace);
        const stateB64 = btoa(JSON.stringify(state));
        const cmid = getCMID();
        repository.submitWorkspace(cmid, stateB64, codeString);
    };

    /**
     * @param {any[]} results The results of the tests
     * @param {{}} tests The tests that were run
     * @param {String[]} uncalledInputFuncs The names of the input functions that were not called in the code, if any
     * Displays the results of the tests in the output div
     */
    function displayTestResults(results, tests, uncalledInputFuncs) {
        const testResultsDiv = document.getElementById('output-div');
        testResultsDiv.innerHTML = lib.testsAccordion(results, tests, uncalledInputFuncs);
    }

    /**
     * @param {{}} tests The tests to be run
     * @param {WorkspaceSvg} workspace The workspace to get the code from
     * @param {string} inputFuncDecs
     */
    function setupButtons(tests, workspace, inputFuncDecs) {
        // Listen for clicks on the run button
        const runButton = document.getElementById('runButton');
        runButton.addEventListener('click', function() {
            // eslint-disable-next-line no-unused-vars
            const code = lib.getWorkspaceCode(workspace, inputFuncDecs);
            // Each function has 3 lines, so we divide by 3 to get the number of functions
            // eslint-disable-next-line no-unused-vars
            const inputFuncDecsCount = inputFuncDecs.split('\n').length / 3;
            lib.replaceCode(code, inputFuncDecsCount);
            runCode(code);
        });

        if (tests !== null) {
            // Listen for clicks on the run tests button
            const runTestsButton = document.getElementById('runTestsButton');
            runTestsButton.addEventListener('click', () => { // Needs anonymous function wrap to pass argument
                const code = lib.getWorkspaceCode(workspace, inputFuncDecs).getCompleteCodeString();
                const uncalledInputFuncs = lib.getMissingInputCalls(code, inputFuncDecs);
                let results;
                // If not all input functions are called, automatically fails all tests
                if (uncalledInputFuncs.length > 0) {
                    results = null;
                } else {
                    results = lib.runTests(code, tests);
                }
                displayTestResults(results, tests, uncalledInputFuncs);
            });
        }

        // Listen for clicks on the save button
        const saveButton = document.getElementById('saveButton');
        saveButton.addEventListener('click', saveState);

        // Listen for clicks on the submit button
        const submitButton = document.getElementById('submitButton');
        submitButton.addEventListener('click', submitWorkspace);
    }

    return {
        /**
         * @param {String} contents The contents of the tests file
         * @param {String} loadedSave The contents of the loaded save, in a base64-encoded JSON string
         * @param {{}} customBlocks The custom blocks to be added to the toolbox, created by the exercise creator
         */
        init: function(contents, loadedSave, customBlocks) {
            const blocklyDiv = document.getElementById('blocklyDiv');
            const blocklyArea = document.getElementById('blocklyArea');

            // If there are custom blocks, add a new category to the toolbox
            if (customBlocks.length > 0) {
                toolbox.contents.push({
                    'kind': 'category',
                    'name': 'Custom Blocks',
                    'colour': 'a55b80',
                    "cssConfig": {
                        'icon': 'customIcon fa-solid fa-code',
                    },
                    'contents': [],
                });
            }

            // eslint-disable-next-line no-console
            console.log(customBlocks);
            customBlocks.forEach((block) => {
                let splitTest = block.generator.split("forBlock['");
                let dotCase = false;
                if (splitTest.length < 2) {
                    splitTest = block.generator.split("forBlock.");
                    if (splitTest.length < 2) {
                        throw new Error("Invalid generator");
                    }
                    dotCase = true;
                }
                const blockName = splitTest[1].split(dotCase ? " = " : "']")[0].trim();
                // Add block to toolbox
                toolbox.contents[toolbox.contents.length - 1].contents.push({
                    'kind': 'block',
                    'type': blockName,
                });

                // eslint-disable-next-line no-eval
                eval(block.definition);
                // eslint-disable-next-line no-eval
                eval(block.generator);
            });

            nextblocksWorkspace = Blockly.inject(blocklyDiv, options);
            javascript.javascriptGenerator.init(nextblocksWorkspace);

            // Use resize observer instead of window resize event. This captures both window resize and element resize
            const resizeObserver = new ResizeObserver(() => onResize(blocklyArea, blocklyDiv, nextblocksWorkspace));
            resizeObserver.observe(blocklyArea);

            // Parse json from contents
            const tests = JSON.parse(contents);
            let inputFunctionDeclarations = {funcDecs: ""};

            if (tests !== null) {
                // Create forced input blocks from tests file. Only add to workspace if there is no workspace to load. If there
                // was a workspace to load, they would be added twice.
                const inputs = tests[0].inputs;

                inputs.forEach((input, i) => {
                    const inputName = Object.keys(input)[0];
                    createForcedInputBlock(inputName, inputFunctionDeclarations); // Doesn't add block to workspace, just
                                                                                  // defines it. Needed for save loading

                    if (loadedSave === null) { // Only add to workspace if there is no workspace to load
                        const blockName = "forced_input_" + inputName;
                        let newBlock = addBlockToWorkspace(blockName, nextblocksWorkspace);
                        newBlock.moveBy(0, i * 50); // Move block down a bit so that they don't overlap
                    }
                });
            }

            // Load the save, if there is one
            if (loadedSave !== null) {
                loadSave(loadedSave, nextblocksWorkspace);
            } else {
                addBlockToWorkspace('start', nextblocksWorkspace);
            }

            setupButtons(tests, nextblocksWorkspace, inputFunctionDeclarations.funcDecs);
        }
    };
});

const onResize = function(blocklyArea, blocklyDiv, nextblocksWorkspace) {
    // Compute the absolute coordinates and dimensions of blocklyArea.
    let element = blocklyArea;
    let x = 0;
    let y = 0;
    do {
        x += element.offsetLeft;
        y += element.offsetTop;
        element = element.offsetParent;
    } while (element);
    // Position blocklyDiv over blocklyArea.
    blocklyDiv.style.left = x + 'px';
    blocklyDiv.style.top = y + 'px';
    blocklyDiv.style.width = blocklyArea.offsetWidth + 'px';
    blocklyDiv.style.height = blocklyArea.offsetHeight + 'px';
    Blockly.svgResize(nextblocksWorkspace);
};

/**
 * @param {String} blockName The name of the input block to be added (prompt on the left side of the block
 * @param {WorkspaceSvg} workspace The workspace to add the input block to
 * @returns {BlockSvg} The newly created block
 */
function addBlockToWorkspace(blockName, workspace) {
    const newBlock = workspace.newBlock(blockName);
    newBlock.initSvg();
    newBlock.render();
    return newBlock;
}

/**
 * @param {String} loadedSave
 * @param {WorkspaceSvg} workspace
 */
function loadSave(loadedSave, workspace) {
    const state = JSON.parse(atob(loadedSave));
    Blockly.serialization.workspaces.load(state, workspace);
}

/**
 * @returns {Number} The course module id of the current page
 */
function getCMID() {
    const classList = document.body.classList;
    const cmidClass = Array.from(classList).find((className) => className.startsWith('cmid-'));
    return parseInt(cmidClass.split('-')[1]);
}

/**
 * @param {string} prompt The name of the input block to be added (prompt on the left side of the block)
 * @param {object} inputFunctionDeclarations Contains the string containing the function declarations for the input
 * blocks, to be added to the top of the code. Is an object so that it is passed by reference.
 */
function createForcedInputBlock(prompt, inputFunctionDeclarations) {
    const blockName = "forced_input_" + prompt;
    Blockly.Blocks[blockName] = {
        init: function() {
            this.appendDummyInput()
                .appendField(prompt)
                .appendField(new Blockly.FieldTextInput('text'), prompt);
            this.setOutput(true, "String");
            this.setDeletable(false);
            this.setColour(180);
            this.setTooltip("");
            this.setHelpUrl("");
        }
    };

    inputFunctionDeclarations.funcDecs += `function input${prompt}(string) {\n   return string;\n}\n`;
    javascript.javascriptGenerator.addReservedWords(`input${prompt}`);

    // eslint-disable-next-line no-unused-vars
    javascript.javascriptGenerator.forBlock[blockName] = function(block, generator) {
        const text = block.getFieldValue(prompt);
        let blockCode = `input${prompt}('${text}')`;
        return [blockCode, Blockly.JavaScript.ORDER_NONE];
    };
}

// eslint-disable-next-line no-extend-native
String.prototype.hideWrapperFunction = function() {
    const lines = this.split('\n');
    lines.splice(0, 2); // Remove the first two lines
    return lines.join('\n');
};

// Redefine the text_print block to use the outputString variable instead of alert.
javascript.javascriptGenerator.forBlock.text_print = function(block, generator) {
    return (
        "print(" +
        (generator.valueToCode(
            block,
            "TEXT",
            Blockly.JavaScript.ORDER_NONE
        ) || "''") +
        ");\n"
    );
};

Blockly.Blocks.text_input = {
    init: function() {
        this.appendDummyInput()
            .appendField("text input:")
            .appendField(new Blockly.FieldTextInput('text'),
                'text_input');
        this.setOutput(true, "String");
        this.setColour(180);
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
        this.setColour(180);
        this.setTooltip("");
        this.setHelpUrl("");
    }
};

Blockly.Blocks.start = {
    init: function() {
        this.appendDummyInput()
            .appendField("start");
        this.setNextStatement(true, null);
        this.setColour(60);
        this.setTooltip("");
        this.setHelpUrl("");
        this.setDeletable(false);
    }
};

// eslint-disable-next-line no-unused-vars
javascript.javascriptGenerator.forBlock.start = function(block, generator) {
    // TODO: Assemble javascript into code variable.
    // get all blocks attached to this block
    let code = '';
    return code;
};

// eslint-disable-next-line no-unused-vars
javascript.javascriptGenerator.forBlock.text_input = function(block, generator) {
    const text = block.getFieldValue('text_input');
    let code = 'input("' + text + '")';
    return [code, Blockly.JavaScript.ORDER_NONE];
};

// eslint-disable-next-line no-unused-vars
javascript.javascriptGenerator.forBlock.text_multiline_input = function(block, generator) {
    const text = block.getFieldValue('text_input');
    let code = "input(`" + text + "`)";
    return [code, Blockly.JavaScript.ORDER_NONE];
};

class CustomCategory extends Blockly.ToolboxCategory {
    /**
     * Constructor for a custom category.
     * @override
     */
    constructor(categoryDef, toolbox, optParent) {
        super(categoryDef, toolbox, optParent);
    }

    /** @override */
    addColourBorder_(colour) {
        this.rowDiv_.style.backgroundColor = colour;
    }

    /** @override */
    setSelected(isSelected) {
        // We do not store the label span on the category, so use getElementsByClassName.
        var labelDom = this.rowDiv_.getElementsByClassName('blocklyTreeLabel')[0];
        if (isSelected) {
            // Change the background color of the div to white.
            this.rowDiv_.style.backgroundColor = 'white';
            // Set the colour of the text to the colour of the category.
            labelDom.style.color = this.colour_;
            this.iconDom_.style.color = this.colour_;
        } else {
            // Set the background back to the original colour.
            this.rowDiv_.style.backgroundColor = this.colour_;
            // Set the text back to white.
            labelDom.style.color = 'white';
            this.iconDom_.style.color = 'white';
        }
        // This is used for accessibility purposes.
        Blockly.utils.aria.setState(/** @type {!Element} */ (this.htmlDiv_),
            Blockly.utils.aria.State.SELECTED, isSelected);
    }
}

class ToolboxLabel extends Blockly.ToolboxItem {
    constructor(toolboxItemDef, parentToolbox) {
        super(toolboxItemDef, parentToolbox);
    }

    /** @override */
    init() {
        // Create the label.
        this.label = document.createElement('label');

        // Set the name.
        this.label.textContent = this.toolboxItemDef_.name;
        // Set the color.
        this.label.style.color = this.toolboxItemDef_.colour;
    }

    /** @override */
    getDiv() {
        return this.label;
    }
}

Blockly.registry.register(Blockly.registry.Type.TOOLBOX_ITEM, 'toolboxlabel', ToolboxLabel);

Blockly.registry.register(Blockly.registry.Type.TOOLBOX_ITEM, Blockly.ToolboxCategory.registrationName, CustomCategory, true);
