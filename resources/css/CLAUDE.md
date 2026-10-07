# CLAUDE.md (resources/css)

Rules for the stylesheets and design tokens.

## Design Tokens

Generated into `resources/css/tokens.css` from `config/design-tokens/*.tokens.json`; the design system (`raaaf/rafael-design-system`) is the source of the values; Figma is frozen and no longer a source. See [docs/DESIGN-TOKENS.md](../../docs/DESIGN-TOKENS.md) for full documentation. Herkunft der Werte, die Vier-Befehle-Kette und die Mapping-Tabelle stehen in [DESIGN.md](../../DESIGN.md).

**Update tokens:**

```bash
# config/design-tokens/*.tokens.json → resources/css/tokens.css
npm run tokens        # Generate CSS
npm run tokens:watch  # Watch mode
```

**Semantic tokens:** `--bg-*`, `--text-*`, `--border-*`, `--icon-*`

Usage:

```css
background: var(--bg-primary);
color: var(--text-primary);
```
