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
$string['cannotgenerate'] = 'The AI service was unable to generate a response.';
$string['checkrevised'] = 'Check revised';
$string['checkrevisedtitle'] = 'Check the revised text against the original review';
$string['cmcontextidlabel'] = 'Section / activity';
$string['cmcontextidlabel_help'] = 'Pick the section (course context) or activity (module context) the test case targets. The selected option\'s value is the context id sent to generate_ai_response.';
$string['cmcontextnotincourse'] = 'The selected section or activity does not belong to the chosen course.';
$string['confirmdeletetestcase'] = 'Are you sure you want to delete the test case "{$a}" and all of its run history?';
$string['contextidlabel'] = 'Context id';
$string['contextidlabel_help'] = 'Numeric Moodle context id passed to generate_ai_response. Use 1 for the system context, or the id of a real course or module context.';
$string['courselabel'] = 'Course';
$string['courselabel_help'] = 'The course whose section/activity the test case targets. Used to populate the section/activity dropdown below; the course id itself is not stored on the test case row.';
$string['coursenotfound'] = 'The selected course no longer exists.';
$string['defaultprompt'] = 'Default prompt';
$string['defaultprompt_desc'] = 'The default review prompt sent to the AI when no previous response exists. This text is appended after any context (course, module, etc.) and before the editor content.';
$string['defaultpromptcontext'] = 'Default prompt context';
$string['defaultpromptcontext_desc'] = 'Standard context prepended to every review prompt, before any course, module, or page/editor-specific context. Leave blank to include nothing by default.';
$string['deletetestcase'] = 'Delete test case';
$string['editingtestcase'] = 'Editing test case "{$a}"';
$string['editorcontentlabel'] = 'Editor content';
$string['editorcontextlabel'] = 'Editor context';
$string['editorcontextlabel_help'] = 'The HTML id of the textarea associated with the editor, e.g. id_summary_editor.';
$string['editorcontextpromptlabel'] = 'Editor context prompt';
$string['editorcontextpromptlabel_help'] = 'Auto-filled with the prompt component of the selected page/editor context entry. You can edit this snapshot for documentation, but at run time the AI response is built from the live tiny_muai/prompts setting (or the prompts override below).';
$string['emptycontent'] = 'There is no text in the editor to review.';
$string['historyheading'] = 'History';
$string['historyhide'] = 'Hide history';
$string['historyitemlabel'] = 'Response {$a}';
$string['historyshow'] = 'Show history';
$string['historytitle'] = 'Show previous responses';
$string['invalidcontextid'] = 'No Moodle context exists with that id.';
$string['lastrunfailed'] = 'Last run failed';
$string['lastrunoutput'] = 'Last run output';
$string['lastrunsucceeded'] = 'Last run succeeded';
$string['muai:managetestcases'] = 'Manage tiny_muai test cases';
$string['namelabel'] = 'Test case name';
$string['nameparamlabel'] = 'Page "name" field value';
$string['nameparamlabel_help'] = 'Value sent as the "name" parameter to generate_ai_response. In the live editor this is the value of the HTML field with id "id_name". Optional.';
$string['nametaken'] = 'A test case with this name already exists.';
$string['newtestcase'] = 'New test case';
$string['overridesheading'] = 'Setting overrides';
$string['overridesheading_desc'] = 'These three values are sent to the AI in place of the site-wide tiny_muai settings, but only for this test case. They are saved with the test case and are not written back to system settings.';
$string['nopromptsconfigured'] = '— no matching page/editor context entries —';
$string['pagelabel'] = 'Page';
$string['pagelabel_help'] = 'The HTML id of the page body tag, e.g. page-course-editsection.';
$string['promptkeylabel'] = 'Page and editor context';
$string['promptkeylabel_help'] = 'Page/editor context entries from the tiny_muai prompts setting, filtered to those that match the selected activity (or whose page id is course-related when a section is selected).';
$string['promptkeyunknown'] = 'The selected page/editor context entry is not in the configured prompts list.';
$string['sectionoptionprefix'] = '— this section (course context) —';
$string['selectcoursefirst'] = '— select a course —';
$string['panelclose'] = 'Close';
$string['panelprocessing'] = 'Processing…';
$string['panelrefresh'] = 'Refresh with current editor content';
$string['pluginname'] = 'Massey University artificial intelligence';
$string['previousresponselabel'] = 'Previous response';
$string['previousresponselabel_help'] = 'Optional previous AI response, used by the editor "Check revised" flow. Leave blank to simulate the first review.';
$string['privacy:metadata'] = 'The MUAI plugin doesn\'t store any personal data.';
$string['prompts'] = 'Page/editor prompts';
$string['prompts_desc'] = 'One entry per line, using the format <code>page|editor_context|prompt</code>. <strong>page</strong> is the id of the page <code>&lt;body&gt;</code> tag (e.g. <code>page-course-editsection</code>). <strong>editor_context</strong> is the id of the textarea the editor is attached to (e.g. <code>id_summary_editor</code>). <strong>prompt</strong> is extra context that will be prepended to "Please review the following text:" when that page and editor combination is matched. Lines starting with <code>#</code> are ignored.';
$string['promptslabel'] = 'Page/editor prompts';
$string['recentruns'] = 'Recent runs';
$string['reviewheading'] = 'Review';
$string['runduration'] = 'Duration';
$string['runerrormessage'] = 'Error message';
$string['runhistory'] = 'Run history';
$string['runinputs'] = 'Inputs';
$string['runoutput'] = 'Output';
$string['runprompttext'] = 'Prompt sent';
$string['runresult'] = 'Result';
$string['runsysteminstruction'] = 'System instruction';
$string['runtimecreated'] = 'Run at';
$string['runuser'] = 'Run by';
$string['runviewheading'] = 'Test case run';
$string['saveandrun'] = 'Save and run';
$string['success'] = 'Success';
$string['testcasecreated'] = 'Test case saved.';
$string['testcasedeleted'] = 'Test case deleted.';
$string['testcases'] = 'Test cases';
$string['testcasesheading'] = 'Tiny muai test cases';
$string['testcaseupdated'] = 'Test case saved.';
$string['viewrun'] = 'View run';
