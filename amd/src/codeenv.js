/**
 *
 * @module      mod_nextblocks/env
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* globals Blockly */

export const init = () => {
  Blockly.inject('blocklyDiv', {toolbox: toolbox});
};

// eslint-disable-next-line no-unused-vars
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
          'type': 'number_input',
        },
      ],
    },
  ],
};

Blockly.Blocks['number_input'] = {
  init: function() {
    this.appendDummyInput().appendField('number input').appendField(new Blockly.FieldNumber(0), 'number_input');
    this.setOutput(true, 'Number');
    this.setColour(240);
    this.setTooltip('Number input tooltip');
    this.setHelpUrl('www.google.com');
  },
};
