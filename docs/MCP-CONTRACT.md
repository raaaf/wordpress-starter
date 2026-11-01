# MCP contract

A separate plugin (constant `WPSMCP_VERSION`, page Werkzeuge > Claude at `tools.php?page=wpsmcp`) lets Claude read and
write page content over MCP. It knows nothing about this theme's ACF structure and asks for it through the filter
`wp_starter_mcp_contract`, answered by `Mcp\ContractProvider`. The filter name is a literal, not derived from the theme
slug, so it stays the same in every theme forked with `bin/setup.php`.

While the plugin is active, the theme options menu links to its page (`Acf\Options::addMcpPluginLink()`, capability
`edit_theme_options` like the menu itself). Editors reach the page through Werkzeuge > Claude, which the plugin registers.

## Version 1

| Key                     | Value                                                                                                                     |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| `version`               | `1`. Additive: new keys may appear, existing keys keep their meaning                                                      |
| `page_field`            | `page_sections`                                                                                                           |
| `page_field_key`        | `field_page_sections`                                                                                                     |
| `writable`              | Explicit layout allowlist (`ContractProvider::WRITABLE_LAYOUTS`)                                                          |
| `copy_meta`             | Meta keys copied with a duplicated page: Yoast SEO fields, `page_is_member_area`                                          |
| `layouts`               | `callable(): array`, the registered layouts, read lazily                                                                  |
| `is_readable`           | `callable(int): bool`, published, draft or pending pages; no private, password, member-area, protected or reference pages |
| `after_write`           | `callable(int): void`, fills missing section anchors like saving in the backend                                           |
| `is_allowed_embed_host` | `callable(string): bool`, a host (or https URL) against the embed allowlist                                               |
| `design_tokens`         | `callable(): array`, `colors` (base colours), `endpoint` (token REST route), `files`                                      |
| `validate_sections`     | `callable(array): list<string>`, readable problems, empty when the sections may be written                                |

`layouts` is a callable because `FlexibleContent::layouts()` is filterable and cached on first use: reading it while the
plugin merely asks for the contract would freeze the list before themes and plugins have added theirs.

## Writable layouts

Every layout except `newsletter` and `contact_form` (wired to plugins and consent) and `member_downloads` and
`download_list` (content behind the member login). The list is explicit: a new layout stays read-only until it is added
to `WRITABLE_LAYOUTS`, and `tests/Unit/Mcp/ContractProviderTest.php` fails until that decision is made.

## Validation

`validate_sections()` takes the sections as a list with `acf_fc_layout` and field names (field keys work too). It reports
layouts outside the allowlist and table rows whose cell count does not match the column headers (the same rule as the
backend form, `AcfServiceProvider::findTableRowMismatches()`). The plugin runs ACF's own field validation itself.
