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

// Per-editor panel state. Keyed by editor so each TinyMCE instance gets its own panel.
const panelStates = new WeakMap();

const PANEL_STYLE = 'position:fixed;top:80px;right:40px;width:380px;'
    + 'max-width:calc(100vw - 40px);max-height:calc(100vh - 120px);'
    + 'background:#fff;border:1px solid #ced4da;border-radius:6px;'
    + 'box-shadow:0 6px 24px rgba(0,0,0,0.18);z-index:10050;'
    + 'display:flex;flex-direction:column;font-size:0.95rem;color:#212529;';

const HEADER_STYLE = 'display:flex;align-items:center;gap:0.25rem;'
    + 'padding:0.5rem 0.75rem;border-bottom:1px solid #dee2e6;'
    + 'background:#f8f9fa;cursor:move;user-select:none;'
    + 'border-top-left-radius:6px;border-top-right-radius:6px;';

const TITLE_STYLE = 'flex:1 1 auto;font-weight:600;';

const BTN_STYLE = 'flex:0 0 auto;background:transparent;border:1px solid transparent;'
    + 'border-radius:4px;padding:0.15rem 0.45rem;cursor:pointer;'
    + 'font-size:1rem;line-height:1;color:#495057;';

const BODY_STYLE = 'padding:0.75rem 1rem;overflow:auto;flex:1 1 auto;'
    + 'white-space:pre-wrap;word-break:break-word;min-height:3rem;';

const SPINNER_STYLE = 'display:inline-block;width:1rem;height:1rem;'
    + 'border:2px solid #adb5bd;border-top-color:#0d6efd;border-radius:50%;'
    + 'animation:tiny-muai-spin 0.8s linear infinite;vertical-align:middle;'
    + 'margin-right:0.5rem;';

let keyframesInjected = false;
const ensureKeyframes = () => {
    if (keyframesInjected) {
        return;
    }
    const style = document.createElement('style');
    style.textContent = '@keyframes tiny-muai-spin{to{transform:rotate(360deg);}}';
    document.head.appendChild(style);
    keyframesInjected = true;
};

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
    ensureKeyframes();

    const [titleText, refreshText, closeText] = await Promise.all([
        getString('critiqueheading', component),
        getString('panelrefresh', component),
        getString('panelclose', component),
    ]);

    const panel = document.createElement('div');
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', titleText);
    panel.style.cssText = PANEL_STYLE;

    const header = document.createElement('div');
    header.style.cssText = HEADER_STYLE;

    const title = document.createElement('span');
    title.textContent = titleText;
    title.style.cssText = TITLE_STYLE;

    const refreshBtn = document.createElement('button');
    refreshBtn.type = 'button';
    refreshBtn.title = refreshText;
    refreshBtn.setAttribute('aria-label', refreshText);
    refreshBtn.style.cssText = BTN_STYLE;
    refreshBtn.textContent = '\u21bb';

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.title = closeText;
    closeBtn.setAttribute('aria-label', closeText);
    closeBtn.style.cssText = BTN_STYLE;
    closeBtn.textContent = '\u00d7';

    header.appendChild(title);
    header.appendChild(refreshBtn);
    header.appendChild(closeBtn);

    const body = document.createElement('div');
    body.style.cssText = BODY_STYLE;

    panel.appendChild(header);
    panel.appendChild(body);

    makeDraggable(panel, header);

    const state = {
        panel,
        body,
        hasContent: false,
        loading: false,
    };

    refreshBtn.addEventListener('click', () => fetchIntoState(editor, state));
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
    spinner.style.cssText = SPINNER_STYLE;
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
