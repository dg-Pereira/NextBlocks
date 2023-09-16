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
      'name': 'Variables',
      'custom': 'VARIABLE',
    },
    {
      'kind': 'category',
      'name': 'Functions',
      'custom': 'PROCEDURE',
    },
  ],
};
