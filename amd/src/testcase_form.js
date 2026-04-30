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
 * Cascading dropdowns for the tiny_muai testcase form.
 *
 * @module     tiny_muai/testcase_form
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';

const SECTION_PROMPT_TOKEN = 'course-';
const MODULE_PROMPT_TOKEN_PREFIX = 'mod-';

const promptMatchesTarget = (entry, modname) => {
    const page = String(entry.page || '');
    if (modname) {
        return page.indexOf(MODULE_PROMPT_TOKEN_PREFIX + modname) !== -1;
    }
    if (page.indexOf(SECTION_PROMPT_TOKEN) !== -1) {
        return true;
    }
    return page.indexOf(MODULE_PROMPT_TOKEN_PREFIX) === -1;
};

const resetSelect = (select, placeholder) => {
    select.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    select.appendChild(opt);
};

const wireForm = (config) => {
    const form = document.getElementById(config.formId);
    if (!form) {
        return;
    }

    const courseSelect = form.querySelector('[name="courseid"]');
    const cmPicker = form.querySelector('#tiny_muai_cmpicker');
    const cmHidden = form.querySelector('[name="cmcontextid"]');
    const promptSelect = form.querySelector('[name="promptkey"]');
    const promptText = form.querySelector('[name="editorcontextprompt"]');
    const pageHidden = form.querySelector('[name="page"]');
    const editorcontextHidden = form.querySelector('[name="editorcontext"]');
    const nameparamInput = form.querySelector('[name="nameparam"]');

    if (!courseSelect || !cmPicker || !cmHidden || !promptSelect || !promptText
            || !pageHidden || !editorcontextHidden || !nameparamInput) {
        return;
    }

    const prompts = Array.isArray(config.prompts) ? config.prompts : [];
    let lastAutoFilled = promptText.value;
    let lastNameparamAutoFilled = nameparamInput.value;

    const renderModules = (payload, preserveValue) => {
        cmPicker.innerHTML = '';
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = '';
        cmPicker.appendChild(blank);

        const wantedValue = preserveValue !== undefined && preserveValue !== null
            ? String(preserveValue)
            : '';

        (payload.sections || []).forEach((section) => {
            const group = document.createElement('optgroup');
            group.label = section.name || '';

            const sectionOpt = document.createElement('option');
            sectionOpt.value = String(payload.coursecontextid);
            sectionOpt.textContent = config.sectionLabel;
            sectionOpt.dataset.modname = '';
            sectionOpt.dataset.displayname = section.name || '';
            group.appendChild(sectionOpt);

            (section.modules || []).forEach((mod) => {
                const opt = document.createElement('option');
                opt.value = String(mod.modcontextid);
                opt.textContent = mod.name + ' (' + mod.modname + ')';
                opt.dataset.modname = mod.modname;
                opt.dataset.displayname = mod.name;
                group.appendChild(opt);
            });

            cmPicker.appendChild(group);
        });

        if (wantedValue) {
            cmPicker.value = wantedValue;
        }
        cmHidden.value = cmPicker.value || '';
    };

    const renderPrompts = (preserveValue) => {
        const selected = cmPicker.options[cmPicker.selectedIndex];
        const modname = selected ? (selected.dataset.modname || '') : '';

        const filtered = prompts.filter((entry) => promptMatchesTarget(entry, modname));

        promptSelect.innerHTML = '';
        if (filtered.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = config.noPromptsPlaceholder;
            promptSelect.appendChild(opt);
            return;
        }

        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = '';
        promptSelect.appendChild(blank);

        filtered.forEach((entry) => {
            const opt = document.createElement('option');
            opt.value = entry.page + '|' + entry.editorcontext;
            opt.textContent = entry.page + ' — ' + entry.editorcontext;
            promptSelect.appendChild(opt);
        });

        if (preserveValue) {
            promptSelect.value = preserveValue;
        }
    };

    const syncFromPromptSelection = () => {
        const value = promptSelect.value || '';
        const sep = value.indexOf('|');
        if (sep === -1) {
            pageHidden.value = '';
            editorcontextHidden.value = '';
            return;
        }
        const page = value.substring(0, sep);
        const ec = value.substring(sep + 1);
        pageHidden.value = page;
        editorcontextHidden.value = ec;

        const match = prompts.find((entry) => entry.page === page && entry.editorcontext === ec);
        if (!match) {
            return;
        }
        if (promptText.value === '' || promptText.value === lastAutoFilled) {
            promptText.value = match.prompt || '';
            lastAutoFilled = promptText.value;
        }
    };

    courseSelect.addEventListener('change', () => {
        const courseid = parseInt(courseSelect.value || '0', 10);
        if (!courseid) {
            resetSelect(cmPicker, config.selectCoursePlaceholder);
            resetSelect(promptSelect, config.noPromptsPlaceholder);
            cmHidden.value = '';
            pageHidden.value = '';
            editorcontextHidden.value = '';
            return;
        }
        fetchMany([{
            methodname: 'tiny_muai_get_course_modules',
            args: {courseid},
        }])[0].then((payload) => {
            renderModules(payload, '');
            renderPrompts('');
            cmHidden.value = '';
            pageHidden.value = '';
            editorcontextHidden.value = '';
            return null;
        }).catch(() => {
            resetSelect(cmPicker, config.selectCoursePlaceholder);
        });
    });

    const autofillNameparam = () => {
        const selected = cmPicker.options[cmPicker.selectedIndex];
        const displayname = selected ? (selected.dataset.displayname || '') : '';
        if (!displayname) {
            return;
        }
        if (nameparamInput.value === '' || nameparamInput.value === lastNameparamAutoFilled) {
            nameparamInput.value = displayname;
            lastNameparamAutoFilled = displayname;
        }
    };

    cmPicker.addEventListener('change', () => {
        cmHidden.value = cmPicker.value || '';
        renderPrompts('');
        pageHidden.value = '';
        editorcontextHidden.value = '';
        autofillNameparam();
    });

    promptSelect.addEventListener('change', syncFromPromptSelection);

    // Bootstrap from any pre-selected values (when editing an existing testcase).
    const initialCourseid = parseInt(courseSelect.value || '0', 10);
    if (initialCourseid) {
        fetchMany([{
            methodname: 'tiny_muai_get_course_modules',
            args: {courseid: initialCourseid},
        }])[0].then((payload) => {
            renderModules(payload, cmHidden.value);
            renderPrompts(promptSelect.value);
            return null;
        }).catch(() => {
            // Leave dropdown empty on error.
        });
    }
};

export const init = async(config) => {
    const [sectionLabel, selectCoursePlaceholder, noPromptsPlaceholder] = await Promise.all([
        getString('sectionoptionprefix', 'tiny_muai'),
        getString('selectcoursefirst', 'tiny_muai'),
        getString('nopromptsconfigured', 'tiny_muai'),
    ]);

    wireForm({
        formId: config.formId,
        prompts: config.prompts || [],
        sectionLabel,
        selectCoursePlaceholder,
        noPromptsPlaceholder,
    });
};
