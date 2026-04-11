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

namespace tiny_muai;

/**
 * Utility helpers for the tiny_muai plugin.
 *
 * @package    tiny_muai
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Build a prompt from the plugin's configured prompts plus the editor
     * content, dispatch it to the AI manager, and return the generated text.
     *
     * Throws moodle_exception if the AI service fails to produce a response.
     *
     * @param int $contextid The Moodle context id of the editor instance.
     * @param string $page The HTML id of the page body tag.
     * @param string $editorcontext The HTML id of the textarea associated with the editor.
     * @param string $editorcontent The text content of the editor.
     * @param string $name The value of the HTML field with id "id_name" on the page.
     * @return string
     */
    public static function generate_ai_response(
        int $contextid,
        string $page,
        string $editorcontext,
        string $editorcontent,
        string $name,
    ): string {
        global $USER, $OUTPUT;

        $extraprompt = '';
        foreach (self::get_configured_prompts() as $row) {
            if ($row['page'] === $page && $row['editorcontext'] === $editorcontext) {
                $extraprompt = $row['prompt'];
                break;
            }
        }

        $tokens = ['{name}' => $name];

        $context = \core\context::instance_by_id($contextid);
        $coursedetails = null;
        $moduledetails = null;

        if ($context instanceof \core\context\module) {
            $moduledetails = self::get_module_details($context->instanceid);
            $coursecontext = $context->get_course_context(false);
            if ($coursecontext) {
                $coursedetails = self::get_course_details($coursecontext->instanceid);
            }
        } else if ($context instanceof \core\context\course) {
            $coursedetails = self::get_course_details($context->instanceid);
        }

        if ($coursedetails !== null) {
            $tokens['{course_fullname}'] = $coursedetails['fullname'];
            $tokens['{course_shortname}'] = $coursedetails['shortname'];
            $tokens['{course_summary}'] = $coursedetails['summary'];
            $tokens['{course_category}'] = $coursedetails['category'];
            $tokens['{course_category_description}'] = $coursedetails['category_description'];
        }
        if ($moduledetails !== null) {
            $tokens['{module_type}'] = $moduledetails['type'];
            $tokens['{module_name}'] = $moduledetails['name'];
            $tokens['{module_section}'] = $moduledetails['section'];
            $tokens['{module_restrictions}'] = $moduledetails['restrictions'];
        }

        if ($extraprompt !== '') {
            $extraprompt = strtr($extraprompt, $tokens);
        }

        $additional = self::get_additional_context($contextid, $page, $editorcontext);

        $defaultpromptcontext = trim((string) get_config('tiny_muai', 'defaultpromptcontext'));

        $prompttext = $OUTPUT->render_from_template('tiny_muai/prompt_context', [
            'defaultpromptcontext' => $defaultpromptcontext !== '' ? $defaultpromptcontext : null,
            'course' => $coursedetails,
            'module' => $moduledetails,
            'extraprompt' => $extraprompt,
            'additional' => $additional,
            'editorcontent' => $editorcontent,
        ]);

        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: $USER->id,
            prompttext: $prompttext,
        );

        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);

        if (!$response->get_success()) {
            $message = $response->get_errormessage();
            if ($message === '' || $message === null) {
                $message = get_string('cannotgenerate', 'tiny_muai');
            }
            throw new \moodle_exception('cannotgenerate', 'tiny_muai', '', null, $message);
        }

        $generated = $response->get_response_data()['generatedcontent'] ?? '';

        if (get_config('tiny_muai', 'debugging') && is_siteadmin($USER)) {
            $debug = "contextid: {$contextid}\n"
                . "page: {$page}\n"
                . "editorcontext: {$editorcontext}\n"
                . "name: {$name}\n"
                . "prompttext: {$prompttext}\n";
            foreach ($tokens as $token => $value) {
                $debug .= "{$token}: {$value}\n";
            }
            $generated = $debug . "\n" . $generated;
        }

        return $generated;
    }

    /**
     * Return additional context to include in the prompt for generate_text.
     *
     * Stub implementation: future versions may inspect the surrounding course
     * or activity to pull richer context. For now this returns an empty string.
     *
     * @param int $contextid The Moodle context id of the editor instance.
     * @param string $page The HTML id of the page body tag.
     * @param string $editorcontext The HTML id of the textarea associated with the editor.
     * @return string
     */
    public static function get_additional_context(
        int $contextid,
        string $page,
        string $editorcontext,
    ): string {
        return '';
    }

    /**
     * Return the course fullname, shortname, and top-level (root) category name.
     *
     * @param int $courseid
     * @return array{fullname: string, shortname: string, category: string, summary: string, category_description: string}
     */
    public static function get_course_details(int $courseid): array {
        $course = get_course($courseid);

        $topname = '';
        $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
        if ($category) {
            $ancestors = array_filter(explode('/', trim((string) $category->path, '/')));
            $topid = (int) reset($ancestors);
            $top = $topid ? \core_course_category::get($topid, IGNORE_MISSING, true) : null;
            if ($top) {
                $topname = $top->get_formatted_name();
            }
        }

        return [
            'fullname' => format_string($course->fullname),
            'shortname' => format_string($course->shortname),
            'summary' => format_string($course->summary),
            'category' => $topname,
            'category_description' => format_string($category ? $category->description : ''),
        ];
    }

    /**
     * Return the module type, name, section name, and any restrict-access conditions.
     *
     * Restrictions are returned as the raw availability JSON string (empty if none are set).
     *
     * @param int $cmid Course module id.
     * @return array{type: string, name: string, section: string, restrictions: string}
     */
    public static function get_module_details(int $cmid): array {
        [$course, $cm] = get_course_and_cm_from_cmid($cmid);

        $sectionname = '';
        $section = $cm->get_section_info();
        if ($section) {
            $sectionname = get_section_name($course, $section);
        }

        return [
            'type' => $cm->modname,
            'name' => format_string($cm->name),
            'section' => $sectionname,
            'restrictions' => (string) ($cm->availability ?? ''),
        ];
    }

    /**
     * Parse the admin "prompts" setting into a list of rows.
     *
     * Each non-blank line in the setting is expected to use the form:
     *   page|editor_context|prompt
     * where "page" matches the id of the body tag, "editor_context" matches
     * the id of the textarea replaced by TinyMCE, and "prompt" is the extra
     * context to prepend to the critique prompt.
     *
     * @return array<int, array{page: string, editorcontext: string, prompt: string}>
     */
    public static function get_configured_prompts(): array {
        $raw = get_config('tiny_muai', 'prompts');
        if ($raw === false || trim((string) $raw) === '') {
            return [];
        }

        $rows = [];
        foreach (preg_split('/\R/', (string) $raw) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 3));
            if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
                continue;
            }
            $rows[] = [
                'page' => $parts[0],
                'editorcontext' => $parts[1],
                'prompt' => $parts[2],
            ];
        }
        return $rows;
    }
}
