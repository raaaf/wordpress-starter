import { describe, expect, it } from 'vitest';
import { normalise } from './sync-icons.js';

describe('normalise (hostile SVG input)', () => {
  it('strips a <script> element', () => {
    const out = normalise('<svg><script>alert(1)</script></svg>');
    expect(out).not.toContain('<script');
  });

  it('strips a self-closing <script/> element', () => {
    const out = normalise('<svg><script src="evil.js" /></svg>');
    expect(out).not.toContain('<script');
  });

  it('strips a <foreignObject> element (paired and self-closing)', () => {
    expect(
      normalise('<svg><foreignObject><body onload="x()"/></foreignObject></svg>')
    ).not.toContain('<foreignObject');
    expect(normalise('<svg><foreignObject data-x="1" /></svg>')).not.toContain('<foreignObject');
  });

  it('strips event-handler attributes regardless of quote style', () => {
    expect(normalise('<svg onload="alert(1)"></svg>')).not.toMatch(/onload/i);
    expect(normalise("<svg onload='alert(1)'></svg>")).not.toMatch(/onload/i);
    expect(normalise('<svg onload=alert(1)></svg>')).not.toMatch(/onload/i);
    expect(normalise('<svg OnLoad="alert(1)"></svg>')).not.toMatch(/onload/i);
  });

  it('strips a javascript: href regardless of quote style and case', () => {
    expect(normalise('<svg><a href="javascript:alert(1)">x</a></svg>')).not.toMatch(/javascript:/i);
    expect(normalise("<svg><a href='JavaScript:alert(1)'>x</a></svg>")).not.toMatch(/javascript:/i);
    expect(normalise('<svg><a xlink:href="javascript:alert(1)">x</a></svg>')).not.toMatch(
      /javascript:/i
    );
  });

  it('strips an HTML-entity-encoded javascript: href', () => {
    // "&#106;avascript:" decodes to "javascript:" - the bypass the finding named.
    const out = normalise('<svg><a href="&#106;avascript:alert(1)">x</a></svg>');
    expect(out).not.toMatch(/javascript:/i);
  });

  it('keeps an ordinary href intact', () => {
    const out = normalise('<svg><a href="#anchor">x</a></svg>');
    expect(out).toContain('href="#anchor"');
  });

  it('adds fill="currentColor" only when no fill is already set', () => {
    expect(normalise('<svg></svg>')).toContain('fill="currentColor"');
    expect(normalise('<svg fill="#000"></svg>')).not.toContain('fill="currentColor"');
  });

  it('strips a </script> closing tag with whitespace before ">"', () => {
    const out = normalise('<svg><script>alert(1)</script ></svg>');
    expect(out).not.toContain('<script');
    expect(out).not.toContain('</script');
  });

  it('strips a </foreignObject> closing tag with whitespace before ">"', () => {
    const out = normalise('<svg><foreignObject>x</foreignObject ></svg>');
    expect(out).not.toContain('<foreignObject');
    expect(out).not.toContain('</foreignObject');
  });

  it('strips an event-handler attribute separated by "/" instead of whitespace', () => {
    expect(normalise('<svg/onload=alert(1)></svg>')).not.toMatch(/onload/i);
  });

  it('strips an entity-encoded javascript: href without a trailing semicolon', () => {
    // "&#106avascript:" (missing ";") still decodes to "javascript:" in browsers.
    const out = normalise('<svg><a href="&#106avascript:alert(1)">x</a></svg>');
    expect(out).not.toMatch(/javascript:/i);
  });

  it('strips a javascript: href hidden behind an entity-encoded control character', () => {
    // "&#9;" decodes to a tab, which browsers ignore inside a URL scheme.
    const out = normalise('<svg><a href="&#9;javascript:alert(1)">x</a></svg>');
    expect(out).not.toMatch(/javascript:/i);
  });

  it('strips SMIL animation elements that could fire via attributeName=onload/href', () => {
    expect(normalise('<svg><animate attributeName="onload" to="alert(1)" /></svg>')).not.toContain(
      '<animate'
    );
    expect(
      normalise('<svg><set attributeName="href" to="javascript:alert(1)"></set></svg>')
    ).not.toContain('<set');
    expect(
      normalise('<svg><animateTransform attributeName="transform" to="1"/></svg>')
    ).not.toContain('<animateTransform');
    expect(
      normalise('<svg><animateMotion><mpath xlink:href="#p"/></animateMotion></svg>')
    ).not.toContain('<animateMotion');
  });
});
