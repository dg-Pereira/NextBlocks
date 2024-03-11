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
                    'type': 'number_input',
                },
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

// GetMainWorkspace might remove need for global variable
let nextblocksWorkspace;

define(['mod_nextblocks/lib', 'mod_nextblocks/repository', 'mod_nextblocks/chat'], function(lib, repository, chat) {
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
     * By default, the workspace is saved to the currently logged-in user's entry in the database
     * If a teacher is adding a comment to a student's submission, the student's id is passed as an argument,
     * because in that case the workspace should be saved to the student's entry in the database, not to the teacher's.
     * @param {bool} isTeacherReport whether the current page is a teacher report. If so, we need to pass the student's id,
     * because PHP will not be able to get it from the user api, as the logged-in user will be the teacher
     */
    const saveState = (isTeacherReport) => {
        const state = Blockly.serialization.workspaces.save(nextblocksWorkspace);
        const stateB64 = btoa(JSON.stringify(state));
        const cmid = getCMID();

        if (isTeacherReport) {
            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            const userId = urlParams.get('userid');

            repository.saveWorkspace(cmid, stateB64, userId);
        } else {
            repository.saveWorkspace(cmid, stateB64);
        }
    };

    const submitWorkspace = async(inputFuncDecs) => {
        const codeString = lib.getWorkspaceCode(nextblocksWorkspace, inputFuncDecs).getSubmittableCodeString();
        const state = Blockly.serialization.workspaces.save(nextblocksWorkspace);
        const stateB64 = btoa(JSON.stringify(state));
        const cmid = getCMID();
        repository.submitWorkspace(cmid, stateB64, codeString);

        const delay = ms => new Promise(res => setTimeout(res, ms));
        await delay(1000);

        location.reload();
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
     * @param {number} lastUserReaction The type of reaction the current user last submitted
     * @param {boolean} isTeacherReport Whether the report to be displayed is a teacher report
     */
    function setupButtons(tests, workspace, inputFuncDecs, lastUserReaction, isTeacherReport) {
        // Listen for clicks on the run button
        const runButton = document.getElementById('runButton');
        runButton.addEventListener('click', function() {
            const code = lib.getWorkspaceCode(workspace, inputFuncDecs);
            //lib.replaceCode(code);
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
        saveButton.addEventListener('click', () => {
            saveState(isTeacherReport);
        });

        // Listen for clicks on the submit button, if it exists (doesn't exist in report pages)
        const submitButton = document.getElementById('submitButton');
        if (submitButton !== null) {
            submitButton.addEventListener('click', () => {
                submitWorkspace(inputFuncDecs);
            });
        }

        // Convert the lastUserReaction to a string
        let lastUserReactionString = "";
        if (lastUserReaction === 1) {
            lastUserReactionString = "easy";
        } else if (lastUserReaction === 2) {
            lastUserReactionString = "medium";
        } else if (lastUserReaction === 3) {
            lastUserReactionString = "hard";
        }

        const imgs = document.getElementsByClassName("emoji-img");
        Array.from(imgs).forEach((img) => {
            let imageType = '';
            if (img.src.includes("easy")) {
                imageType = "easy";
            } else if (img.src.includes("think")) {
                imageType = "medium";
            } else if (img.src.includes("hard")) {
                imageType = "hard";
            }

            // Start with one image selected if the user has already reacted in a previous session
            if (lastUserReactionString === imageType) {
                changeImageBackground(img);
            }

            // Only listen for clicks on the images if page is not a teacher report
            if (!isTeacherReport) {
                img.addEventListener("click", () => {
                    // Submit reaction, and wait for response with new reaction counts
                    const newReactionsPromise = repository.submitReaction(getCMID(), imageType);
                    newReactionsPromise.then((newReactions) => {
                        updatePercentages(newReactions.reactionseasy, newReactions.reactionsmedium, newReactions.reactionshard);
                        changeImageBackground(img);
                    });
                });
            }
        });

        const textCodeButton = document.getElementById('showCodeButton');
        let codeVisible = false; // Variable to track the visibility state
        let overlayDiv; // Variable to store the overlay div

        textCodeButton.addEventListener('click', () => {
            const blocklyArea = document.getElementById('blocklyArea');
            const codeString = lib.getWorkspaceCode(workspace, inputFuncDecs).getPrintableCodeString().replace(/\n/g, "<br />");

            // Get the padding of the blocklyArea
            const paddingLeft = parseInt(window.getComputedStyle(blocklyArea).getPropertyValue('padding-left'));
            const paddingRight = parseInt(window.getComputedStyle(blocklyArea).getPropertyValue('padding-right'));

            if (codeVisible) {
                // If the code is currently visible, hide it
                overlayDiv.style.display = 'none';
                codeVisible = false;
            } else {
                // If the code is currently hidden, show it
                if (!overlayDiv) {
                    // If the overlay div doesn't exist, create it
                    overlayDiv = document.createElement('div');
                    overlayDiv.style.position = 'absolute';
                    overlayDiv.style.top = '0';
                    overlayDiv.style.left = `${paddingLeft}px`;
                    overlayDiv.style.width = `calc(100% - ${paddingLeft + paddingRight}px)`;
                    overlayDiv.style.height = '100%';
                    overlayDiv.style.backgroundColor = '#f9f9f9';
                    overlayDiv.style.border = '1px solid #ddd';
                    overlayDiv.style.padding = '10px';
                    overlayDiv.style.fontFamily = '"Lucida Console", "Courier New", monospace';
                    overlayDiv.style.zIndex = '1000';
                    blocklyArea.appendChild(overlayDiv);
                }
                overlayDiv.innerHTML = codeString;
                overlayDiv.style.display = 'block';
                codeVisible = true;
            }
        });
    }

    return {
        /**
         * @param {string} contents The contents of the tests file.
         * @param {string} loadedSave The contents of the loaded save, in a base64-encoded JSON string.
         * @param {{}} customBlocks The custom blocks to be added to the toolbox, created by the exercise creator.
         * @param {number} remainingSubmissions The number of remaining submissions for the current user.
         * @param {string[]} reactions An array of 3 strings, each containing the number of reactions of a certain type
         * (easy, medium, hard).
         * @param {number} lastUserReaction The type of reaction the current user last submitted
         * (0 = no reaction, 1 = easy, 2 = medium, 3 = hard).
         * @param {number} reportType Indicates the type of report to be displayed (0 = no report, 1 = teacher report,
         * 2 = student report).
         * @param {string} userName The name of the user that loaded the page.
         * @param {number} activityId The id of the activity
         */
        init: function(contents, loadedSave, customBlocks, remainingSubmissions, reactions, lastUserReaction, reportType = 0,
                       userName, activityId) {
            // If report is student but he can still submit, change to no report so he can use the workspace
            if (reportType === 2 && remainingSubmissions > 0) {
                reportType = 0;
            }
            updatePercentages(reactions[0], reactions[1], reactions[2]);

            chat.populate(repository.getMessages, activityId);

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

            nextblocksWorkspace = Blockly.inject(blocklyDiv, getOptions(remainingSubmissions, reportType !== 0));
            javascript.javascriptGenerator.init(nextblocksWorkspace);

            // Use resize observer instead of window resize event. This captures both window resize and element resize
            const resizeObserver = new ResizeObserver(() => onResize(blocklyArea, blocklyDiv, nextblocksWorkspace));
            resizeObserver.observe(blocklyArea);

            // Parse json from test file contents
            const tests = JSON.parse(contents);
            let inputFunctionDeclarations = {funcDecs: ""};
            if (tests !== null) {
                // Create forced input blocks from tests file. Only add to workspace if there is no workspace to load. If there
                // was a workspace to load, they would be added twice.
                const inputs = tests[0].inputs;

                inputs.forEach((input, i) => {
                    const inputName = Object.keys(input)[0];
                    const inputType = Object.keys(input[inputName])[0];
                    createForcedInputBlock(inputName, inputType, inputFunctionDeclarations); // Doesn't add block to workspace, just
                    // defines it. Needed for save loading

                    if (loadedSave === null) { // Only add to workspace if there is no workspace to load
                        const blockName = "forced_input_" + inputName;
                        let newBlock = addBlockToWorkspace(blockName, nextblocksWorkspace);
                        newBlock.moveBy(0, i * 50 + 50); // Move block down a bit so that they don't overlap
                    }
                });
            }

            // Load the save, if there is one
            if (loadedSave !== null) {
                loadSave(loadedSave, nextblocksWorkspace);
            } else { // Otherwise, add the start block
                addBlockToWorkspace('start', nextblocksWorkspace);
            }

            // If page is a report page, lock all workspace blocks while still allowing comments
            if (reportType !== 0) {
                lockWorkspaceBlocks(nextblocksWorkspace);
            }

            setupButtons(tests, nextblocksWorkspace, inputFunctionDeclarations.funcDecs, lastUserReaction, reportType === 1);

            chat.run(userName, activityId, repository.saveMessage);
        },
    };
});

/**
 * Locks all blocks in a workspace, preventing them from being moved or deleted
 * @param {WorkspaceSvg} workspace The workspace to lock
 */
const lockWorkspaceBlocks = function(workspace) {
    workspace.getTopBlocks(false).forEach((block) => {
        lockBlock(block);
        lockChildren(block);
    });

    /**
     * Recursively locks a block and all its children, preventing them from being moved or deleted
     * @param {BlockSvg} block The block that will be locked and have its children locked
     */
    function lockChildren(block) {
        block.getChildren(false).forEach((child) => {
            lockBlock(child);

            // Have to mess with internal Blockly stuff to block only the inputs while still allowing comments
            child.inputList.forEach((input) => {
                input.fieldRow.forEach((field) => {
                    field.setEnabled(false);
                });
            });

            lockChildren(child);
        });
    }

    /**
     * Locks a block, preventing it from being moved or deleted
     * @param {BlockSvg} block The block that will be locked
     */
    function lockBlock(block) {
        block.setMovable(false);
        block.setDeletable(false);
    }
};

// Makes background of image blue if it is not blue, and vice versa
const changeImageBackground = function(img) {
    // Change background of all other images to secondary
    const imgs = document.getElementsByClassName("emoji-img");
    Array.from(imgs).forEach((otherImg) => {
        if (otherImg !== img) {
            otherImg.classList.remove("bg-primary");
            otherImg.classList.add("bg-secondary");
        }
    });

    // Toggle background of clicked image
    if (img.classList.contains("bg-primary")) {
        img.classList.remove("bg-primary");
        img.classList.add("bg-secondary");
    } else {
        img.classList.remove("bg-secondary");
        img.classList.add("bg-primary");
    }
};

/**
 * Updates the percentages of difficulty levels (easy, medium, hard) on the page.
 *
 * @param {number} easy - The count of 'easy' reactions.
 * @param {number} medium - The count of 'medium' reactions.
 * @param {number} hard - The count of 'hard' reactions.
 * @param {string} [inc=""] - The difficulty level to increment. If not provided, no level is incremented.
 * Unused right now, just for future-proofing
 */
const updatePercentages = function(easy, medium, hard, inc = "") {
    // Mapping of difficulty levels to their corresponding HTML elements
    const elements = {
        "easy": document.getElementById('percentage-easy'),
        "medium": document.getElementById('percentage-medium'),
        "hard": document.getElementById('percentage-hard')
    };

    // Mapping of difficulty levels to their counts
    const values = {
        "easy": easy,
        "medium": medium,
        "hard": hard
    };

    // If a difficulty level to increment is provided, increment its count
    if (inc in values) {
        values[inc]++;
    }

    // Calculate the percentages for each difficulty level
    let percentages = calcPercentages(values.easy, values.medium, values.hard);

    // Update the HTML elements with the new percentages
    elements.easy.innerHTML = percentages[0] + '%';
    elements.medium.innerHTML = percentages[1] + '%';
    elements.hard.innerHTML = percentages[2] + '%';
};

const calcPercentages = (easy, medium, hard) => {
    const total = easy + medium + hard;
    return total === 0 ? [0, 0, 0] : [easy, medium, hard].map(val => Math.round((val / total) * 100));
};

const getOptions = function(remainingSubmissions, readOnly) {
    return {
        toolbox: readOnly ? null : toolbox,
        collapse: true,
        comments: true,
        disable: false,
        maxBlocks: Infinity,
        trashcan: !readOnly,
        horizontalLayout: false,
        toolboxPosition: 'start',
        css: true,
        media: 'https://blockly-demo.appspot.com/static/media/',
        rtl: false,
        scrollbars: true,
        sounds: true,
        oneBasedIndex: false,
        readOnly: remainingSubmissions <= 0,
        grid: {
            spacing: 20,
            length: 1,
            colour: '#888',
            snap: false,
        },
        zoom: !readOnly ? null : {
            controls: true,
            wheel: true,
            startScale: 1,
            maxScale: 3,
            minScale: 0.3,
            scaleSpeed: 1.2,
        },
    };
};

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
 * @param {string} inputType The type of the input block to be added (string, number, etc.)
 * @param {object} inputFunctionDeclarations Contains the string containing the function declarations for the input
 * blocks, to be added to the top of the code. Is an object so that it is passed by reference.
 */
function createForcedInputBlock(prompt, inputType, inputFunctionDeclarations) {
    const blockName = "forced_input_" + prompt;
    if (inputType === "string") {
        Blockly.Blocks[blockName] = {
            init: function() {
                this.appendDummyInput().appendField(prompt).appendField(new Blockly.FieldTextInput('text'), prompt);
                this.setOutput(true, "String");
                this.setDeletable(false);
                this.setColour(180);
                this.setTooltip("");
                this.setHelpUrl("");
            }
        };

        // eslint-disable-next-line no-unused-vars
        javascript.javascriptGenerator.forBlock[blockName] = function(block, generator) {
            const text = block.getFieldValue(prompt);
            let blockCode = `input${prompt}('${text}')`;
            return [blockCode, Blockly.JavaScript.ORDER_NONE];
        };
    } else if (inputType === "number") {
        Blockly.Blocks[blockName] = {
            init: function() {
                this.appendDummyInput().appendField(prompt).appendField(new Blockly.FieldNumber(0), prompt);
                this.setOutput(true, "Number");
                this.setColour(180);
                this.setTooltip("");
                this.setHelpUrl("");
            }
        };

        // eslint-disable-next-line no-unused-vars
        javascript.javascriptGenerator.forBlock[blockName] = function(block, generator) {
            const number = block.getFieldValue(prompt);
            let blockCode = `input${prompt}(${number})`;
            return [blockCode, Blockly.JavaScript.ORDER_NONE];
        };
    }

    inputFunctionDeclarations.funcDecs += `function input${prompt}(string) {\n   return string;\n}\n`;
    javascript.javascriptGenerator.addReservedWords(`input${prompt}`);
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

Blockly.Blocks.number_input = {
    init: function() {
        this.appendDummyInput()
        .appendField("number input")
        .appendField(new Blockly.FieldNumber(0), "number_input");
        this.setOutput(true, "Number");
        this.setColour(180);
        this.setTooltip("");
        this.setHelpUrl("");
    }
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
    // Get all blocks attached to this block
    let code = '';
    return code;
};

// eslint-disable-next-line no-unused-vars
javascript.javascriptGenerator.forBlock.number_input = function(block, generator) {
    const number = block.getFieldValue('number_input');
    let code = 'input(' + number + ')';
    return [code, Blockly.JavaScript.ORDER_NONE];
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

// O problema é que, ao fazer save, estou a guardar o workspace do aluno na minha linha da base de dados, não na dele
