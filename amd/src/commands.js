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
 * Tiny muai commands
 *
 * @module     tiny_muai/commands
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getButtonImage} from 'editor_tiny/utils';
import {get_string as getString} from 'core/str';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {component, buttonName, icon} from 'tiny_muai/common';
import {getContextId} from 'tiny_muai/options';

const critiqueEditorContent = async(editor) => {
    const editorContent = editor.getContent({format: 'text'}).trim();
    if (editorContent === '') {
        Notification.alert(
            await getString('buttontitle', component),
            await getString('emptycontent', component),
        );
        return;
    }

    try {
        const response = await Ajax.call([{
            methodname: 'tiny_muai_get_ai_response',
            args: {
                contextid: getContextId(editor),
                page: document.body?.id ?? '',
                editorcontext: editor.id ?? '',
                editorcontent: editorContent,
                name: document.getElementById('id_name')?.value ?? '',
            },
        }])[0];

        editor.insertContent(
            `<p><strong>${await getString('critiqueheading', component)}</strong></p>` +
            `<p>${response.replace(/\n/g, '<br>')}</p>`
        );
    } catch (error) {
        Notification.exception(error);
    }
};

export const getSetup = async() => {
    const [
        buttonTitle,
        buttonImage,
    ] = await Promise.all([
        getString('buttontitle', component),
        getButtonImage('icon', component),
    ]);

    return (editor) => {
        // Register the muai icon.
        editor.ui.registry.addIcon(icon, buttonImage.html);

        // Register the toolbar Button.
        editor.ui.registry.addButton(buttonName, {
            icon,
            tooltip: buttonTitle,
            onAction: () => critiqueEditorContent(editor),
        });

        // Register the Menu item.
        editor.ui.registry.addMenuItem(buttonName, {
            icon,
            text: buttonTitle,
            onAction: () => critiqueEditorContent(editor),
        });
    };
};
