import { describe, it, expect, vi, afterEach } from 'vitest';
import { introPosition, revealCircle, transitionColorScheme } from './motion';
import { createThemeSwitcherComponent } from './app';

describe('revealCircle', () => {
  it('reaches the farthest viewport corner from the button centre', () => {
    const { x, y, radius } = revealCircle({ left: 90, top: 10, width: 20, height: 20 }, 100, 200);
    expect(x).toBe(100);
    expect(y).toBe(20);
    // Farthest corner is bottom left: (0, 200).
    expect(radius).toBeCloseTo(Math.hypot(100, 180));
  });
});

describe('introPosition', () => {
  it('runs 50 -> 15 -> 85 -> 50', () => {
    expect(introPosition(0)).toBe(50);
    expect(introPosition(1 / 3)).toBeCloseTo(15);
    expect(introPosition(2 / 3)).toBeCloseTo(85);
    expect(introPosition(1)).toBe(50);
  });

  it('clamps outside 0 to 1', () => {
    expect(introPosition(-1)).toBe(50);
    expect(introPosition(2)).toBe(50);
  });
});

describe('transitionColorScheme', () => {
  afterEach(() => {
    delete (document as { startViewTransition?: unknown }).startViewTransition;
    vi.restoreAllMocks();
  });

  it('applies the change at once without the View Transitions API', () => {
    const update = vi.fn();
    transitionColorScheme(update, document.createElement('button'));
    expect(update).toHaveBeenCalledOnce();
  });

  it('applies the change at once with reduced motion, without a transition', () => {
    const start = vi.fn();
    (document as { startViewTransition?: unknown }).startViewTransition = start;
    vi.spyOn(window, 'matchMedia').mockReturnValue({ matches: true } as MediaQueryList);
    const update = vi.fn();

    transitionColorScheme(update, document.createElement('button'));

    expect(update).toHaveBeenCalledOnce();
    expect(start).not.toHaveBeenCalled();
  });

  it('wraps the change in a View Transition when available', () => {
    const start = vi.fn((cb: () => void) => {
      cb();
      return { ready: new Promise<void>(() => undefined) };
    });
    (document as { startViewTransition?: unknown }).startViewTransition = start;
    vi.spyOn(window, 'matchMedia').mockReturnValue({ matches: false } as MediaQueryList);
    const update = vi.fn();

    transitionColorScheme(update, document.createElement('button'));

    expect(start).toHaveBeenCalledOnce();
    expect(update).toHaveBeenCalledOnce();
  });
});

describe('createThemeSwitcherComponent', () => {
  afterEach(() => {
    localStorage.clear();
    document.documentElement.removeAttribute('data-theme');
  });

  it('starts from the stored choice and writes data-theme and storage', () => {
    localStorage.setItem('wp-starter-theme', 'dark');
    const c = createThemeSwitcherComponent();
    expect(c.mode).toBe('dark');

    c.apply('light');
    expect(document.documentElement.getAttribute('data-theme')).toBe('light');
    expect(localStorage.getItem('wp-starter-theme')).toBe('light');

    c.apply('system');
    expect(document.documentElement.hasAttribute('data-theme')).toBe(false);
    expect(localStorage.getItem('wp-starter-theme')).toBeNull();
    expect(c.mode).toBe('system');
  });
});
