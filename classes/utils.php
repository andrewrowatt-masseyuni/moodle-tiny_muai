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
     * The three override parameters allow the test cases admin tool to swap in
     * dynamic values for the site-wide tiny_muai/defaultpromptcontext,
     * tiny_muai/defaultprompt, and tiny_muai/prompts settings without writing
     * them to system config. When an override is null the corresponding
     * get_config() value is used (preserving the live editor's behaviour); when
     * non-null the override value is used verbatim, including the empty string.
     *
     * @param int $contextid The Moodle context id of the editor instance.
     * @param string $page The HTML id of the page body tag.
     * @param string $editorcontext The HTML id of the textarea associated with the editor.
     * @param string $editorcontent The text content of the editor.
     * @param string $name The value of the HTML field with id "id_name" on the page.
     * @param string $previousresponse The previous AI response (for "check revised").
     * @param string|null $defaultpromptcontextoverride Override for tiny_muai/defaultpromptcontext.
     * @param string|null $defaultpromptoverride Override for tiny_muai/defaultprompt.
     * @param string|null $promptsoverride Override for tiny_muai/prompts (raw multi-line text).
     * @return array{systeminstruction: string, prompttext: string, output: string}
     */
    public static function generate_ai_response(
        int $contextid,
        string $page,
        string $editorcontext,
        string $editorcontent,
        string $name,
        string $previousresponse = '',
        ?string $defaultpromptcontextoverride = null,
        ?string $defaultpromptoverride = null,
        ?string $promptsoverride = null,
    ): array {
        global $USER, $OUTPUT;

        $configuredprompts = $promptsoverride !== null
            ? self::parse_prompts_string($promptsoverride)
            : self::get_configured_prompts();

        $extraprompt = '';
        foreach ($configuredprompts as $row) {
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
            $tokens['{module_section_summary}'] = $moduledetails['section_summary'];
            $tokens['{module_restrictions}'] = $moduledetails['restrictions'];
        }

        if ($extraprompt !== '') {
            $extraprompt = strtr($extraprompt, $tokens);
        }

        $additional = self::get_additional_context($contextid, $page, $editorcontext);

        $defaultpromptcontext = $defaultpromptcontextoverride !== null
            ? trim($defaultpromptcontextoverride)
            : trim((string) get_config('tiny_muai', 'defaultpromptcontext'));

        if ($defaultpromptoverride !== null) {
            $defaultprompt = $defaultpromptoverride;
        } else {
            $defaultprompt = get_config('tiny_muai', 'defaultprompt');
            if ($defaultprompt === false || trim((string) $defaultprompt) === '') {
                $defaultprompt = "Please review the following text and provide feedback to:\n"
                    . "1. Correct any grammatical errors and typos.\n"
                    . "2. Improve word choice and sentence structure to make the text more concise "
                    . "and easy to read for university-level students.\n"
                    . "3. Highlight any ambiguity in relation to the surrounding context provided above.";
            }
        }
        $defaultprompt = trim((string) $defaultprompt);

        if ($extraprompt !== '') {
            $defaultprompt = strtr($defaultprompt, ['{editorcontextprompt}' => "- $extraprompt"]);
        }

        $prompttext = $OUTPUT->render_from_template('tiny_muai/prompt_context', [
            'defaultpromptcontext' => $defaultpromptcontext !== '' ? $defaultpromptcontext : null,
            'defaultprompt' => $defaultprompt,
            'course' => $coursedetails,
            'module' => $moduledetails,
            'extraprompt' => '',
            'additional' => $additional,
            'editorcontent' => $editorcontent,
            'previousresponse' => $previousresponse !== '' ? $previousresponse : null,
        ]);

        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: $USER->id,
            prompttext: $prompttext,
        );

        // Resolve the system instruction from the first enabled provider for this action. The manager
        // iterates providers in order and returns the first successful result, so this matches what
        // the run will use unless that provider fails and falls back to another.
        $systeminstruction = '';
        $providers = \core_ai\manager::get_providers_for_actions([\core_ai\aiactions\generate_text::class], true);
        if (!empty($providers[\core_ai\aiactions\generate_text::class])) {
            $provider = reset($providers[\core_ai\aiactions\generate_text::class]);
            $systeminstruction = (string) get_config($provider->get_name(), 'action_generate_text_systeminstruction');
        }

        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);

        if (!$response->get_success()) {
            $message = $response->get_errormessage();
            if ($message === '' || $message === null) {
                $message = get_string('cannotgenerate', 'tiny_muai');
            }
            throw new \moodle_exception('cannotgenerate', 'tiny_muai', '', null, $message);
        }

        return [
            'systeminstruction' => $systeminstruction,
            'prompttext' => $prompttext,
            'output' => $response->get_response_data()['generatedcontent'] ?? '',
        ];
    }

    /**
     * Run a saved test case and persist a row in tiny_muai_testcase_run.
     *
     * Loads the test case, calls generate_ai_response with the test case's
     * field values plus its overrides, and records the inputs / output /
     * success state / duration in the run history.
     *
     * @param int $testcaseid The id of the saved test case.
     * @return int The id of the newly inserted tiny_muai_testcase_run row.
     */
    public static function run_test_case(int $testcaseid): int {
        global $DB, $USER;

        $testcase = $DB->get_record('tiny_muai_testcase', ['id' => $testcaseid], '*', MUST_EXIST);

        $inputs = [
            'name' => $testcase->name,
            'contextid' => (int) $testcase->contextid,
            'page' => $testcase->page,
            'editorcontext' => $testcase->editorcontext,
            'editorcontent' => $testcase->editorcontent,
            'nameparam' => (string) $testcase->nameparam,
            'previousresponse' => (string) $testcase->previousresponse,
            'defaultpromptcontext' => (string) $testcase->defaultpromptcontext,
            'defaultprompt' => (string) $testcase->defaultprompt,
            'prompts' => (string) $testcase->prompts,
        ];

        $start = microtime(true);
        $systeminstruction = '';
        $prompttext = '';
        $output = '';
        $success = 0;
        $errormessage = '';
        try {
            $result = self::generate_ai_response(
                contextid: (int) $testcase->contextid,
                page: $testcase->page,
                editorcontext: $testcase->editorcontext,
                editorcontent: $testcase->editorcontent,
                name: (string) $testcase->nameparam,
                previousresponse: (string) $testcase->previousresponse,
                defaultpromptcontextoverride: (string) $testcase->defaultpromptcontext,
                defaultpromptoverride: (string) $testcase->defaultprompt,
                promptsoverride: (string) $testcase->prompts,
            );
            $systeminstruction = $result['systeminstruction'];
            $prompttext = $result['prompttext'];
            $output = $result['output'];
            $success = 1;
        } catch (\Throwable $e) {
            $errormessage = $e->getMessage();
        }
        $durationms = (int) round((microtime(true) - $start) * 1000);

        $run = (object) [
            'testcaseid' => $testcase->id,
            'userid' => $USER->id,
            'inputs' => json_encode($inputs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'systeminstruction' => $systeminstruction,
            'prompttext' => $prompttext,
            'output' => $output,
            'success' => $success,
            'errormessage' => $errormessage,
            'durationms' => $durationms,
            'timecreated' => time(),
        ];
        return $DB->insert_record('tiny_muai_testcase_run', $run);
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
     * Return the module type, name, section name, summary, and content, plus any restrict-access conditions.
     *
     * Restrictions are returned as the raw availability JSON string (empty if none are set).
     * The section summary and section content are returned as plain-text strings (empty if none is set).
     *
     * @param int $cmid Course module id.
     * @return array{
     *     type: string,
     *     name: string,
     *     section: string,
     *     section_summary: string,
     *     section_content: string,
     *     restrictions: string,
     * }
     */
    public static function get_module_details(int $cmid): array {
        [$course, $cm] = get_course_and_cm_from_cmid($cmid);

        $sectionname = '';
        $sectionsummary = '';
        $sectioncontent = '';
        $section = $cm->get_section_info();
        if ($section) {
            $sectionname = get_section_name($course, $section);
            if (!empty($section->summary)) {
                $coursecontext = \core\context\course::instance($course->id);
                $summary = file_rewrite_pluginfile_urls(
                    $section->summary,
                    'pluginfile.php',
                    $coursecontext->id,
                    'course',
                    'section',
                    $section->id
                );
                $sectionsummary = trim(format_text(
                    $summary,
                    $section->summaryformat,
                    ['context' => $coursecontext, 'noclean' => true]
                ));
            }
            $sectioncontent = self::get_section_content($course->id, $section->id);
        }

        return [
            'type' => $cm->modname,
            'name' => format_string($cm->name),
            'section' => $sectionname,
            'section_summary' => $sectionsummary,
            'section_content' => $sectioncontent,
            'restrictions' => (string) ($cm->availability ?? ''),
        ];
    }

    /**
     * Return a plain-text/markdown-ish rendering of the label and book modules in a course section.
     *
     * Labels contribute their intro text. Books contribute each non-hidden chapter as a heading plus
     * the chapter body. HTML is collapsed to plain text via format_text + html_to_text so the result
     * can be dropped directly into an AI prompt.
     *
     * @param int $courseid
     * @param int $sectionid The id of the course_sections row (not the section number).
     * @return string Empty string if the section has no label/book content.
     */
    public static function get_section_content(int $courseid, int $sectionid): string {
        global $DB;

        $modinfo = get_fast_modinfo($courseid);
        $section = $modinfo->get_section_info_by_id($sectionid, IGNORE_MISSING);
        if (!$section || empty($section->sequence)) {
            return '';
        }

        $blocks = [];
        foreach (explode(',', $section->sequence) as $cmid) {
            $cmid = (int) trim($cmid);
            if ($cmid === 0) {
                continue;
            }
            try {
                $cm = $modinfo->get_cm($cmid);
            } catch (\moodle_exception $e) {
                continue;
            }
            if ($cm->deletioninprogress) {
                continue;
            }

            if ($cm->modname === 'label') {
                $label = $DB->get_record('label', ['id' => $cm->instance], 'intro, introformat');
                if (!$label || trim((string) $label->intro) === '') {
                    continue;
                }
                $intro = file_rewrite_pluginfile_urls(
                    $label->intro,
                    'pluginfile.php',
                    $cm->context->id,
                    'mod_label',
                    'intro',
                    null
                );
                $html = format_text($intro, $label->introformat, [
                    'context' => $cm->context,
                    'noclean' => true,
                ]);
                $text = trim(html_to_text($html, 0, false));
                if ($text !== '') {
                    $blocks[] = $text;
                }
            } else if ($cm->modname === 'book') {
                $chapters = $DB->get_records(
                    'book_chapters',
                    ['bookid' => $cm->instance, 'hidden' => 0],
                    'pagenum ASC, id ASC',
                    'id, title, content, contentformat, subchapter'
                );
                if (empty($chapters)) {
                    continue;
                }
                $parts = ['### ' . format_string($cm->name)];
                foreach ($chapters as $chapter) {
                    $prefix = !empty($chapter->subchapter) ? '#####' : '####';
                    $parts[] = $prefix . ' ' . format_string($chapter->title);
                    $chaptercontent = file_rewrite_pluginfile_urls(
                        $chapter->content,
                        'pluginfile.php',
                        $cm->context->id,
                        'mod_book',
                        'chapter',
                        $chapter->id
                    );
                    $html = format_text($chaptercontent, $chapter->contentformat, [
                        'context' => $cm->context,
                        'noclean' => true,
                    ]);
                    $text = trim(html_to_text($html, 0, false));
                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }
                $blocks[] = implode("\n\n", $parts);
            }
        }

        return implode("\n\n", $blocks);
    }

    /**
     * Read the admin "prompts" setting and parse it into a list of rows.
     *
     * @return array<int, array{page: string, editorcontext: string, prompt: string}>
     */
    public static function get_configured_prompts(): array {
        return self::parse_prompts_string((string) get_config('tiny_muai', 'prompts'));
    }

    /**
     * Parse a raw prompts string (the same format as the tiny_muai/prompts
     * admin setting) into a list of rows.
     *
     * Each non-blank line is expected to use the form:
     *   page|editor_context|prompt
     * where "page" matches the id of the body tag, "editor_context" matches
     * the id of the textarea replaced by TinyMCE, and "prompt" is the extra
     * context to prepend to the review prompt. Lines starting with "#" are
     * ignored.
     *
     * @param string $raw
     * @return array<int, array{page: string, editorcontext: string, prompt: string}>
     */
    public static function parse_prompts_string(string $raw): array {
        if (trim($raw) === '') {
            return [];
        }

        $rows = [];
        foreach (preg_split('/\R/', $raw) as $line) {
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
