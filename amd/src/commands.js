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
import Templates from 'core/templates';
import {component, buttonName, icon} from 'tiny_muai/common';
import {getContextId} from 'tiny_muai/options';

// Per-editor panel state. Keyed by editor so each TinyMCE instance gets its own panel.
const panelStates = new WeakMap();

const makeDraggable = (panel, handle) => {
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;
    let dragging = false;

    handle.addEventListener('mousedown', (e) => {
        if (e.target.closest('button')) {
            return;
        }
        const rect = panel.getBoundingClientRect();
        panel.style.left = rect.left + 'px';
        panel.style.top = rect.top + 'px';
        panel.style.right = 'auto';
        panel.style.bottom = 'auto';
        startX = e.clientX;
        startY = e.clientY;
        startLeft = rect.left;
        startTop = rect.top;
        dragging = true;
        e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
        if (!dragging) {
            return;
        }
        panel.style.left = (startLeft + e.clientX - startX) + 'px';
        panel.style.top = (startTop + e.clientY - startY) + 'px';
    });

    document.addEventListener('mouseup', () => {
        dragging = false;
    });
};

const buildPanel = async(editor) => {
    const [titleText, refreshText, closeText, checkRevisedText, checkRevisedTitleText] = await Promise.all([
        getString('critiqueheading', component),
        getString('panelrefresh', component),
        getString('panelclose', component),
        getString('checkrevised', component),
        getString('checkrevisedtitle', component),
    ]);

    const {html} = await Templates.renderForPromise('tiny_muai/panel', {
        title: titleText,
        refreshtitle: refreshText,
        closetitle: closeText,
        checkrevised: checkRevisedText,
        checkrevisedtitle: checkRevisedTitleText,
    });

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const panel = wrapper.firstElementChild;

    const handle = panel.querySelector('[data-region="muai-drag-handle"]');
    const body = panel.querySelector('[data-region="muai-body"]');
    const refreshBtn = panel.querySelector('[data-action="muai-refresh"]');
    const closeBtn = panel.querySelector('[data-action="muai-close"]');
    const checkRevisedBtn = panel.querySelector('[data-action="muai-checkrevised"]');

    makeDraggable(panel, handle);

    const state = {
        panel,
        body,
        checkRevisedBtn,
        hasContent: false,
        loading: false,
        originalContent: null,
        lastCritique: null,
    };

    // Listen for editor content changes to enable/disable "Check revised".
    editor.on('input NodeChange', () => {
        if (state.lastCritique === null || state.loading) {
            return;
        }
        const current = editor.getContent({format: 'text'}).trim();
        checkRevisedBtn.disabled = (current === state.originalContent);
    });

    refreshBtn.addEventListener('click', () => fetchIntoState(editor, state));
    checkRevisedBtn.addEventListener('click', () => fetchReviseIntoState(editor, state));
    closeBtn.addEventListener('click', () => {
        panel.style.display = 'none';
    });

    editor.once('remove', () => {
        panel.remove();
        panelStates.delete(editor);
    });

    return state;
};

const setProcessing = async(state) => {
    const processingText = await getString('panelprocessing', component);
    state.body.textContent = '';
    const spinner = document.createElement('span');
    spinner.className = 'tiny-muai-panel-spinner';
    const label = document.createElement('span');
    label.textContent = processingText;
    state.body.appendChild(spinner);
    state.body.appendChild(label);
    state.hasContent = false;
};

const fetchIntoState = async(editor, state) => {
    const editorContent = editor.getContent({format: 'text'}).trim();
    if (editorContent === '') {
        Notification.alert(
            await getString('buttontitle', component),
            await getString('emptycontent', component),
        );
        return;
    }

    if (state.loading) {
        return;
    }
    state.loading = true;
    await setProcessing(state);

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

        state.body.textContent = response;
        state.hasContent = true;
        state.originalContent = editorContent;
        state.lastCritique = response;
        if (state.checkRevisedBtn) {
            state.checkRevisedBtn.disabled = true;
        }
    } catch (error) {
        state.body.textContent = '';
        state.hasContent = false;
        Notification.exception(error);
    } finally {
        state.loading = false;
    }
};

const fetchReviseIntoState = async(editor, state) => {
    const editorContent = editor.getContent({format: 'text'}).trim();
    if (editorContent === '') {
        Notification.alert(
            await getString('buttontitle', component),
            await getString('emptycontent', component),
        );
        return;
    }

    if (state.loading) {
        return;
    }
    state.loading = true;
    state.checkRevisedBtn.disabled = true;
    await setProcessing(state);

    try {
        const response = await Ajax.call([{
            methodname: 'tiny_muai_get_ai_response',
            args: {
                contextid: getContextId(editor),
                page: document.body?.id ?? '',
                editorcontext: editor.id ?? '',
                editorcontent: editorContent,
                name: document.getElementById('id_name')?.value ?? '',
                previousresponse: state.lastCritique ?? '',
            },
        }])[0];

        state.body.textContent = response;
        state.hasContent = true;
        state.originalContent = editorContent;
        state.lastCritique = response;
    } catch (error) {
        state.body.textContent = '';
        state.hasContent = false;
        Notification.exception(error);
    } finally {
        state.loading = false;
    }
};

const openPanel = async(editor) => {
    let state = panelStates.get(editor);

    if (!state) {
        // First-time open: validate there is content to critique before building anything.
        if (editor.getContent({format: 'text'}).trim() === '') {
            Notification.alert(
                await getString('buttontitle', component),
                await getString('emptycontent', component),
            );
            return;
        }
        state = await buildPanel(editor);
        panelStates.set(editor, state);
        document.body.appendChild(state.panel);
        await fetchIntoState(editor, state);
        return;
    }

    // Panel already exists: reattach if needed, unhide, and leave any cached content in place.
    if (!state.panel.isConnected) {
        document.body.appendChild(state.panel);
    }
    state.panel.style.display = 'flex';

    if (!state.hasContent && !state.loading) {
        await fetchIntoState(editor, state);
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
            onAction: () => openPanel(editor),
        });

        // Register the Menu item.
        editor.ui.registry.addMenuItem(buttonName, {
            icon,
            text: buttonTitle,
            onAction: () => openPanel(editor),
        });
    };
};
