<?php
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
 * English language pack for Muai
 *
 * @package    tiny_muai
 * @category   string
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['buttontitle'] = 'Review text';
$string['checkrevised'] = 'Check revised';
$string['checkrevisedtitle'] = 'Check the revised text against the original review';
$string['cannotgenerate'] = 'The AI service was unable to generate a response.';
$string['reviewheading'] = 'Review';
$string['panelclose'] = 'Close';
$string['panelprocessing'] = 'Processing…';
$string['panelrefresh'] = 'Refresh with current editor content';
$string['debugging'] = 'Debugging enabled';
$string['debugging_desc'] = 'When enabled, responses returned to site administrators are prefixed with the contextid, page id, and editor textarea id that were sent to the AI service.';
$string['defaultprompt'] = 'Default prompt';
$string['defaultprompt_desc'] = 'The default review prompt sent to the AI when no previous response exists. This text is appended after any context (course, module, etc.) and before the editor content.';
$string['defaultpromptcontext'] = 'Default prompt context';
$string['defaultpromptcontext_desc'] = 'Standard context prepended to every review prompt, before any course, module, or page/editor-specific context. Leave blank to include nothing by default.';
$string['emptycontent'] = 'There is no text in the editor to review.';
$string['historyheading'] = 'History';
$string['historyshow'] = 'Show history';
$string['historyhide'] = 'Hide history';
$string['historytitle'] = 'Show previous responses';
$string['historyitemlabel'] = 'Response {$a}';
$string['pluginname'] = 'Massey University artificial intelligence';
$string['privacy:metadata'] = 'The MUAI plugin doesn\'t store any personal data.';
$string['prompts'] = 'Page/editor prompts';
$string['prompts_desc'] = 'One entry per line, using the format <code>page|editor_context|prompt</code>. <strong>page</strong> is the id of the page <code>&lt;body&gt;</code> tag (e.g. <code>page-course-editsection</code>). <strong>editor_context</strong> is the id of the textarea the editor is attached to (e.g. <code>id_summary_editor</code>). <strong>prompt</strong> is extra context that will be prepended to "Please review the following text:" when that page and editor combination is matched. Lines starting with <code>#</code> are ignored.';
