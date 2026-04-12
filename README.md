# Massey University Artificial Intelligence (tiny_muai)

A TinyMCE editor plugin for Moodle that provides AI-powered critique of text content. It adds a toolbar button to TinyMCE that sends the editor content to Moodle's core AI subsystem and displays the critique in a slide-out panel.

## Requirements

- Moodle 4.5 (2024100700)
- A configured AI provider in Moodle's core AI subsystem (Site administration > AI)

## Installation

Copy the `muai` folder into `lib/editor/tiny/plugins/` so the path is:

    lib/editor/tiny/plugins/muai/

Then visit Site administration > Notifications to complete the installation.

## Features

- **AI text critique** - one-click critique of editor content via the TinyMCE toolbar.
- **Context-aware prompts** - automatically includes course name, category, module type, section summary, and other contextual information in the AI prompt.
- **Configurable per-page prompts** - administrators can define custom prompt context for specific page and editor combinations.
- **Check revised** - after editing, re-check revised text against the original critique.
- **Debug mode** - site administrators can enable a debug view that shows the full prompt sent to the AI service.

## Configuration

Settings are found under Site administration > Plugins > Text editors > TinyMCE editor > Massey University artificial intelligence.

### Default prompt context

Standard context prepended to every critique prompt, before any course, module, or page/editor-specific context.

### Page/editor prompts

One entry per line using the format:

    page|editor_context|prompt

- **page** - the `id` attribute of the page `<body>` tag (e.g. `page-course-editsection`).
- **editor_context** - the `id` of the textarea the editor is attached to (e.g. `id_summary_editor`).
- **prompt** - extra context prepended to the critique prompt when the page and editor combination is matched.

Lines starting with `#` are treated as comments and ignored.

#### Available tokens

The following tokens can be used in prompt text and will be replaced with values from the current context:

| Token | Description |
|---|---|
| `{name}` | Value of the HTML field with id `id_name` on the page |
| `{course_fullname}` | Course full name |
| `{course_shortname}` | Course short name |
| `{course_summary}` | Course summary |
| `{course_category}` | Top-level course category name |
| `{course_category_description}` | Course category description |
| `{module_type}` | Activity module type (e.g. `assign`, `forum`) |
| `{module_name}` | Activity module name |
| `{module_section}` | Section name |
| `{module_section_summary}` | Section summary |
| `{module_restrictions}` | Availability restrictions (raw JSON) |

### Debugging

When enabled, responses returned to site administrators are prefixed with the context ID, page ID, editor ID, and the full prompt that was sent to the AI service.

## Licence

Licensed under the [GNU GPL v3 or later](https://www.gnu.org/copyleft/gpl.html).

## Author

Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
