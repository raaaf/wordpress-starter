import { describe, it, expect, beforeEach } from 'vitest';
import {
  collectSearchSections,
  collectSections,
  highlightMatches,
  revealMatch,
  searchSections,
  type JumpSection,
} from './jump-menu';

function section(id: string, label: string, text: string): JumpSection {
  return { id, label, text };
}

describe('searchSections', () => {
  it.each(['', '   ', 'a', '  a  '])(
    'returns nothing when the trimmed query is shorter than 2 characters (%j)',
    (query) => {
      const sections = [section('s1', 'Alpha', 'a banana a')];

      expect(searchSections(sections, query)).toEqual([]);
    }
  );

  it('searches with the trimmed query once it reaches 2 characters', () => {
    const sections = [section('s1', 'Alpha', 'a banana a')];

    expect(searchSections(sections, '  an ').map((hit) => hit.id)).toEqual(['s1']);
  });

  it('matches case-insensitively with German lowercasing', () => {
    const sections = [section('s1', 'Hallo', 'Ein Text über Preise')];

    expect(searchSections(sections, 'ÜBER').map((hit) => hit.id)).toEqual(['s1']);
  });

  it('matches a section by its label alone', () => {
    const sections = [section('s1', 'Pricing', 'nothing relevant here')];

    expect(searchSections(sections, 'pric').map((hit) => hit.id)).toEqual(['s1']);
  });

  it('matches a section by its text alone', () => {
    const sections = [section('s1', 'Intro', 'our pricing is fair')];

    expect(searchSections(sections, 'pric').map((hit) => hit.id)).toEqual(['s1']);
  });

  it('returns hits in input order and each section only once', () => {
    const sections = [
      section('s1', 'Cat', 'cat cat cat'),
      section('s2', 'Dog', 'no match'),
      section('s3', 'Bird', 'a cat too'),
      section('s4', 'Cats', 'cat'),
    ];

    expect(searchSections(sections, 'cat').map((hit) => hit.id)).toEqual(['s1', 's3', 's4']);
  });

  it('carries id and label of the section into the hit', () => {
    const sections = [section('s1', 'Intro', 'our pricing is fair')];

    expect(searchSections(sections, 'pricing')[0]).toMatchObject({ id: 's1', label: 'Intro' });
  });

  describe('hit parts for a text match', () => {
    it('splits the snippet into before, match and after and keeps the original casing of the match', () => {
      const text = 'the quick NEEDLE jumps';

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect([hit.before, hit.match, hit.after]).toEqual(['the quick ', 'NEEDLE', ' jumps']);
    });

    it('builds snippet as before + match + after', () => {
      const text =
        'a'.repeat(30) + ' ' + 'b'.repeat(30) + ' NEEDLE ' + 'c'.repeat(30) + ' ' + 'd'.repeat(30);

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.snippet).toBe(hit.before + hit.match + hit.after);
    });

    it('collapses whitespace runs and trims before windowing', () => {
      const text = '  first \n\t line   with   NEEDLE \n\n inside  ';

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.snippet).toBe('first line with NEEDLE inside');
    });

    it('drops the partial first word when the 40-character window starts mid-word', () => {
      // NEEDLE sits at index 68, so the window starts at 28, inside the 30 a's.
      const text = 'a'.repeat(30) + ' ' + 'b'.repeat(30) + ' ' + 'c'.repeat(5) + ' NEEDLE';

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.before).toMatch(/^… ?b{30} c{5} $/);
    });

    it('drops the partial last word when the 40-character window ends mid-word', () => {
      // The window ends at index 46, inside the 30 f's.
      const text = 'NEEDLE ' + 'd'.repeat(5) + ' ' + 'e'.repeat(30) + ' ' + 'f'.repeat(30);

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.after).toMatch(/^ d{5} e{30} ?…$/);
    });

    it('never cuts into the match when the match sits inside a long word', () => {
      const text = 'a'.repeat(60) + 'NEEDLE' + 'b'.repeat(60);

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.match).toBe('NEEDLE');
    });

    it('sets no leading ellipsis when the window starts at the text start', () => {
      const text = 'z' + 'a'.repeat(38) + ' NEEDLE';

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.before).toBe('z' + 'a'.repeat(38) + ' ');
    });

    it('sets a leading ellipsis when text precedes a word-aligned window', () => {
      // NEEDLE sits at index 43, the window starts at 3, right after "zz ".
      const text = 'zz ' + 'a'.repeat(39) + ' NEEDLE';

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.before).toBe('…' + 'a'.repeat(39) + ' ');
    });

    it('sets no trailing ellipsis when the window reaches the text end', () => {
      const text = 'NEEDLE ' + 'b'.repeat(39);

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.after).toBe(' ' + 'b'.repeat(39));
    });

    it('sets a trailing ellipsis when text follows a word-aligned window', () => {
      // The window ends at index 46, right before the space that precedes "yy".
      const text = 'NEEDLE ' + 'b'.repeat(39) + ' yy';

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect(hit.after).toBe(' ' + 'b'.repeat(39) + '…');
    });

    it('windows around the first match when the text holds several', () => {
      const text = 'NEEDLE ' + 'y'.repeat(5) + ' ' + 'z'.repeat(100) + ' NEEDLE';

      const [hit] = searchSections([section('s1', 'Label', text)], 'needle');

      expect([hit.before, hit.after]).toEqual(['', expect.stringMatching(/^ y{5} ?…$/)]);
    });
  });

  describe('count', () => {
    it('counts non-overlapping case-insensitive occurrences of the query in the text', () => {
      // Overlapping counting would give 3.
      const [hit] = searchSections([section('s1', 'Label', 'aAaA')], 'aa');

      expect(hit.count).toBe(2);
    });
  });

  describe('hit for a label-only match', () => {
    it('has empty before and match, count 0 and the text as after', () => {
      const [hit] = searchSections([section('s1', 'Pricing', 'short text')], 'pricing');

      expect({ before: hit.before, match: hit.match, after: hit.after, count: hit.count }).toEqual({
        before: '',
        match: '',
        after: 'short text',
        count: 0,
      });
    });

    it('cuts the text to at most 80 characters at a word boundary and adds an ellipsis when longer', () => {
      // 11 whole words fill 77 characters, the 12th would start at 77 and cross 80.
      const text = 'abcdef '.repeat(20).trim();

      const [hit] = searchSections([section('s1', 'Pricing', text)], 'pricing');

      expect(hit.after).toMatch(/^(abcdef ){10}abcdef ?…$/);
    });

    it('shows a text of exactly 80 characters without an ellipsis', () => {
      const text = 'x'.repeat(80);

      const [hit] = searchSections([section('s1', 'Pricing', text)], 'pricing');

      expect(hit.after).toBe(text);
    });

    it('collapses whitespace before counting the 80 characters', () => {
      const text = 'word   \n  '.repeat(30);

      const [hit] = searchSections([section('s1', 'Pricing', text)], 'pricing');

      expect(hit.after).toMatch(/^(word ){15}word ?…$/);
    });
  });

  describe('invariants (seeded)', () => {
    /**
     * Local mulberry32 PRNG: no global RNG state, so nothing leaks into other
     * tests. A failing message carries the seed; rerun with that seed in
     * SEEDS to reproduce the exact sections and query.
     */
    function mulberry32(seed: number): () => number {
      let state = seed;
      return () => {
        state = (state + 0x6d2b79f5) | 0;
        let t = Math.imul(state ^ (state >>> 15), 1 | state);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
      };
    }

    const SEEDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    it.each(SEEDS)(
      'keeps input order, one hit per section, snippet = before + match + after and a bounded length (seed %i)',
      (seed) => {
        const random = mulberry32(seed);
        const alphabet = ['a', 'b', 'Ü', 'ü', ' ', '\n'];
        const word = (max: number): string =>
          Array.from(
            { length: Math.floor(random() * max) },
            () => alphabet[Math.floor(random() * alphabet.length)]
          ).join('');

        const sections = Array.from({ length: 12 }, (_, index) =>
          section(`s${index}`, word(8), word(250))
        );
        const query = ['ab', 'ba', 'üb', 'ÜA'][Math.floor(random() * 4)];
        const needle = query.toLocaleLowerCase('de');

        const expectedIds = sections
          .filter(
            (s) =>
              s.label.toLocaleLowerCase('de').includes(needle) ||
              s.text.toLocaleLowerCase('de').includes(needle)
          )
          .map((s) => s.id);

        const hits = searchSections(sections, query);

        expect(
          hits.map((hit) => hit.id),
          `seed ${seed}`
        ).toEqual(expectedIds);
        for (const hit of hits) {
          expect(hit.snippet, `seed ${seed}, section ${hit.id}`).toBe(
            hit.before + hit.match + hit.after
          );
          // 40 + match + 40 + two ellipses; the label-only form is 80 + one ellipsis.
          expect(hit.snippet.length, `seed ${seed}, section ${hit.id}`).toBeLessThanOrEqual(
            82 + query.length
          );
        }
      }
    );
  });
});

describe('collectSections', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  function collect(html: string): JumpSection[] {
    document.body.innerHTML = html;
    return collectSections(document.body);
  }

  it('lists only section.section elements with an id, in DOM order', () => {
    const result = collect(`
      <section class="section" id="b"><h2>Second</h2></section>
      <section class="section"><h2>No id</h2></section>
      <div class="section" id="div"><h2>Not a section element</h2></div>
      <section id="plain"><h2>Missing section class</h2></section>
      <section class="section" id="a"><h2>Third</h2></section>
    `);

    expect(result.map((s) => s.id)).toEqual(['b', 'a']);
  });

  it('skips sections marked data-jump-menu="hidden"', () => {
    const result = collect(`
      <section class="section" id="shown"><h2>Shown</h2></section>
      <section class="section" id="off" data-jump-menu="hidden"><h2>Off</h2></section>
    `);

    expect(result.map((s) => s.id)).toEqual(['shown']);
  });

  it('takes the label from the first h2, trimmed and whitespace-collapsed', () => {
    const result = collect(`
      <section class="section" id="a">
        <h3>Smaller</h3>
        <h2>
          Our   big
          headline
        </h2>
        <h2>Second h2</h2>
      </section>
    `);

    expect(result[0].label).toBe('Our big headline');
  });

  it('falls back to the first h3 when the section has no h2', () => {
    const result = collect(`
      <section class="section" id="a"><h3>First h3</h3><h3>Second h3</h3></section>
    `);

    expect(result[0].label).toBe('First h3');
  });

  it('skips a section with neither h2 nor h3', () => {
    const result = collect(`
      <section class="section" id="a"><h4>Only h4</h4><p>Body</p></section>
      <section class="section" id="b"><h2>Has one</h2></section>
    `);

    expect(result.map((s) => s.id)).toEqual(['b']);
  });

  it('builds the text from the section minus its label heading, collapsed and trimmed, hidden and inert content included', () => {
    const result = collect(`
      <section class="section" id="a">
        <h2>Title</h2>
        <p>Visible   copy</p>
        <div hidden>Hidden copy</div>
        <div inert>Inert copy</div>
      </section>
    `);

    expect(result[0].text).toBe('Visible copy Hidden copy Inert copy');
  });

  it('does not list nested non-section elements as separate entries', () => {
    const result = collect(`
      <section class="section" id="outer">
        <h2>Outer</h2>
        <div class="section-nested" id="inner"><h2>Inner</h2></div>
      </section>
    `);

    expect(result.map((s) => s.id)).toEqual(['outer']);
  });
});

describe('collectSearchSections', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  function collect(html: string): JumpSection[] {
    document.body.innerHTML = html;
    return collectSearchSections(document.body);
  }

  it('lists section.section elements with an id in DOM order, opted-out sections included', () => {
    const result = collect(`
      <section class="section" id="a"><h2>First</h2></section>
      <section class="section"><h2>No id</h2></section>
      <section class="section" id="off" data-jump-menu="hidden"><h2>Off</h2></section>
      <section class="section" id="b"><h2>Second</h2></section>
    `);

    expect(result.map((s) => s.id)).toEqual(['a', 'off', 'b']);
  });

  it('skips a section that holds the jump menu itself', () => {
    const result = collect(`
      <section class="section" id="a"><h2>First</h2></section>
      <section class="section" id="menu"><h2>Menu</h2><div data-jump-menu-root></div></section>
    `);

    expect(result.map((s) => s.id)).toEqual(['a']);
  });

  it('keeps sections without h2 or h3 instead of skipping them', () => {
    const result = collect(`
      <section class="section" id="a"><h2>Chapter</h2></section>
      <section class="section" id="b"><p>No heading</p></section>
    `);

    expect(result.map((s) => s.id)).toEqual(['a', 'b']);
  });

  it('uses the own first h2, falling back to the first h3', () => {
    const result = collect(`
      <section class="section" id="a"><h3>Small</h3><h2>Big</h2></section>
      <section class="section" id="b"><h3>Only h3</h3></section>
    `);

    expect(result.map((s) => s.label)).toEqual(['Big', 'Only h3']);
  });

  it('gives a heading-less section the label of the closest preceding section with its own heading', () => {
    const result = collect(`
      <section class="section" id="a"><h2>Chapter A</h2></section>
      <section class="section" id="b"><p>No heading</p></section>
      <section class="section" id="c"><h2>Chapter C</h2></section>
      <section class="section" id="d"><p>No heading</p></section>
      <section class="section" id="e"><p>No heading either</p></section>
    `);

    expect(result.map((s) => s.label)).toEqual([
      'Chapter A',
      'Chapter A',
      'Chapter C',
      'Chapter C',
      'Chapter C',
    ]);
  });

  it('uses an empty label when no section with a heading precedes', () => {
    const result = collect(`
      <section class="section" id="a"><p>No heading</p></section>
      <section class="section" id="b"><h2>Later</h2></section>
    `);

    expect(result[0].label).toBe('');
  });

  it('never takes the chapter label from a hidden section', () => {
    const result = collect(`
      <section class="section" id="a"><h2>Visible chapter</h2></section>
      <section class="section" id="off" data-jump-menu="hidden"><h2>Hidden chapter</h2></section>
      <section class="section" id="b"><p>No heading</p></section>
    `);

    expect(result.find((s) => s.id === 'b')?.label).toBe('Visible chapter');
  });

  it('builds the text from the section minus its label heading, collapsed, hidden and inert content included', () => {
    const result = collect(`
      <section class="section" id="a">
        <h2>Title</h2>
        <p>Visible   copy</p>
        <div hidden>Hidden copy</div>
        <div inert>Inert copy</div>
      </section>
    `);

    expect(result[0].text).toBe('Visible copy Hidden copy Inert copy');
  });
});

describe.each([
  ['collectSections', collectSections],
  ['collectSearchSections', collectSearchSections],
] as const)('%s text exclusions', (_name, collector) => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  function textOf(html: string): string {
    document.body.innerHTML = html;
    return collector(document.body)[0].text;
  }

  it('excludes the text of the heading used as the label but keeps other headings', () => {
    const text = textOf(`
      <section class="section" id="a">
        <h3>Sub</h3><h2>Title</h2><h2>Second</h2><p>Body</p>
      </section>
    `);

    expect(text).toBe('Sub Second Body');
  });

  it('excludes the h3 text when the h3 is the label fallback', () => {
    const text = textOf(`
      <section class="section" id="a"><h3>Label h3</h3><p>Body</p></section>
    `);

    expect(text).toBe('Body');
  });

  it.each([
    ['nav', '<nav>Menu</nav>'],
    ['[aria-hidden="true"]', '<div aria-hidden="true">Decoration</div>'],
    ['.sr-only', '<span class="sr-only">Screen reader</span>'],
    ['script', '<script>var tracking = 1;</script>'],
    ['style', '<style>.tracking { color: red; }</style>'],
    ['template', '<template><p>Template copy</p></template>'],
  ])('excludes descendants matching %s', (_selector, markup) => {
    const text = textOf(`
      <section class="section" id="a"><h2>Title</h2><p>Keep</p>${markup}</section>
    `);

    expect(text).toBe('Keep');
  });
});

describe('collectSearchSections text of a chapter-labelled section', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('removes nothing but the UI selectors', () => {
    document.body.innerHTML = `
      <section class="section" id="a"><h2>Chapter</h2></section>
      <section class="section" id="b"><nav>Menu</nav><h4>Sub</h4><p>Body</p></section>
    `;

    const second = collectSearchSections(document.body)[1];

    expect({ label: second.label, text: second.text }).toEqual({
      label: 'Chapter',
      text: 'Sub Body',
    });
  });
});

describe('inherited chapter labels', () => {
  it('does not turn a borrowed label into a label-only hit', () => {
    const borrowed: JumpSection = {
      id: 's1',
      label: 'Chapter',
      text: 'unrelated',
      ownLabel: false,
    };

    expect(searchSections([borrowed], 'chapter')).toEqual([]);
  });

  it('still finds a section with a borrowed label through its text', () => {
    const borrowed: JumpSection = {
      id: 's1',
      label: 'Chapter',
      text: 'about a chapter',
      ownLabel: false,
    };

    expect(searchSections([borrowed], 'chapter').map((hit) => hit.id)).toEqual(['s1']);
  });

  it("marks in collectSearchSections whether the label is the section's own", () => {
    document.body.innerHTML = `
      <section class="section" id="a"><h2>Chapter</h2></section>
      <section class="section" id="b"><p>No heading</p></section>
    `;

    expect(collectSearchSections(document.body).map((s) => s.ownLabel)).toEqual([true, false]);
  });
});

describe('highlightMatches and revealMatch use the searchable text', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  function sectionOf(html: string): Element {
    document.body.innerHTML = `<section class="section" id="a"><h2>Title</h2>${html}</section>`;
    return document.getElementById('a') as Element;
  }

  it('finds a match that spans several text nodes, one range per touched node', () => {
    const section = sectionOf('<p>Hel<strong>lo wor</strong>ld</p>');

    const ranges = highlightMatches(section, 'llo wor');

    expect(ranges.map((range) => range.toString())).toEqual(['l', 'lo wor']);
  });

  it('ignores a match inside an excluded subtree', () => {
    const section = sectionOf('<span class="sr-only">secret</span><p>secret too</p>');

    const ranges = highlightMatches(section, 'secret');

    expect(ranges.map((range) => range.startContainer.parentElement?.tagName)).toEqual(['P']);
  });

  it('does not match the label heading, which sectionText leaves out', () => {
    const section = sectionOf('<p>Body</p>');

    expect(highlightMatches(section, 'title')).toEqual([]);
  });

  it('opens the tab whose panel holds a match spanning several text nodes', () => {
    const section = sectionOf(`
      <div role="tablist">
        <button role="tab" id="t1" aria-controls="p1" aria-selected="true">One</button>
        <button role="tab" id="t2" aria-controls="p2" aria-selected="false">Two</button>
      </div>
      <div role="tabpanel" id="p1" aria-labelledby="t1"><p>First</p></div>
      <div role="tabpanel" id="p2" aria-labelledby="t2" aria-hidden="true"><p>Sec<em>ond</em> panel</p></div>
    `);
    let clicked = '';
    section.querySelectorAll('[role="tab"]').forEach((tab) => {
      tab.addEventListener('click', () => (clicked = tab.id));
    });

    const target = revealMatch(section, 'ond pan');

    expect({ clicked, tag: target?.tagName }).toEqual({ clicked: 't2', tag: 'EM' });
  });
});

describe('one text model for search, highlight and reveal', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it.each([
    ['a <br> between the words', '<p>foo<br>bar</p>', 'foo bar', ['foo', 'bar']],
    ['a block right after text', 'text<p>block</p>', 'text block', ['text', 'block']],
    [
      'nested blocks',
      '<div><div>alpha</div><div>beta</div></div>',
      'alpha beta',
      ['alpha', 'beta'],
    ],
  ])('agrees on %s', (_name, html, query, painted) => {
    document.body.innerHTML = `<section class="section" id="a"><h2>Title</h2>${html}</section>`;
    const section = document.getElementById('a') as Element;
    const [searchable] = collectSearchSections(document.body);

    const [hit] = searchSections([searchable], query);
    const ranges = highlightMatches(section, query);

    expect({
      count: hit.count,
      painted: ranges.map((range) => range.toString()),
      target: revealMatch(section, query) !== null,
    }).toEqual({ count: 1, painted, target: true });
  });
});
