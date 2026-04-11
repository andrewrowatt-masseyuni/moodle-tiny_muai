// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Options helper for muai plugin
 *
 * @module     tiny_muai/options
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from 'tiny_muai/common';

const contextIdName = getPluginOptionName(pluginName, 'contextid');

/**
 * Register the options for the muai plugin.
 *
 * @param {tinyMCE} editor
 */
export const register = (editor) => {
    const registerOption = editor.options.register;

    registerOption(contextIdName, {
        processor: 'number',
        "default": 0,
    });
};

/**
 * Get the context id.
 *
 * @param {TinyMCE} editor
 * @returns {number}
 */
export const getContextId = (editor) => editor.options.get(contextIdName);
