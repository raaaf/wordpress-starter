import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { createStatsCounterCore } from './stats-counter';

/**
 * Tests for the shared stats counter animation core (createStatsCounterCore).
 * Number/locale formatting and the suffix rendering happen in
 * templates/flexible/stats.blade.php, not in this module, so they are not
 * covered here.
 *
 * requestAnimationFrame and performance.now() are both stubbed with a manual
 * frame queue so each animation frame can be advanced deterministically.
 */

let now = 0;
let rafCallbacks: FrameRequestCallback[] = [];

function advanceFrame(ms: number): void {
  now += ms;
  const callbacks = rafCallbacks;
  rafCallbacks = [];
  callbacks.forEach((cb) => cb(now));
}

class MockIntersectionObserver {
  static instances: MockIntersectionObserver[] = [];
  callback: IntersectionObserverCallback;
  observe = vi.fn();
  disconnect = vi.fn();
  unobserve = vi.fn();

  constructor(callback: IntersectionObserverCallback) {
    this.callback = callback;
    MockIntersectionObserver.instances.push(this);
  }

  trigger(isIntersecting: boolean): void {
    this.callback(
      [{ isIntersecting } as IntersectionObserverEntry],
      this as unknown as IntersectionObserver
    );
  }
}

function mockMatchMedia(matches: boolean): void {
  vi.stubGlobal(
    'matchMedia',
    vi.fn().mockImplementation((query: string) => ({
      matches,
      media: query,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
    }))
  );
}

describe('createStatsCounterCore', () => {
  beforeEach(() => {
    now = 0;
    rafCallbacks = [];
    vi.stubGlobal('requestAnimationFrame', (cb: FrameRequestCallback) => {
      rafCallbacks.push(cb);
      return rafCallbacks.length;
    });
    vi.spyOn(performance, 'now').mockImplementation(() => now);
    MockIntersectionObserver.instances = [];
    vi.stubGlobal('IntersectionObserver', MockIntersectionObserver);
    mockMatchMedia(false);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('counts from 0 to the target within the duration', () => {
    const counter = createStatsCounterCore(100, {
      respectReducedMotion: false,
      useIntersectionObserver: false,
      preserveDecimals: false,
    });
    counter.$el = document.createElement('div');
    counter.init();

    expect(counter.current).toBe(0);
    advanceFrame(1000); // half the 2000ms duration
    expect(counter.current).toBe(50);
    advanceFrame(1000); // reaches the full duration
    expect(counter.current).toBe(100);
  });

  it('preserves decimal precision from the target when preserveDecimals is on', () => {
    const counter = createStatsCounterCore(99.5, {
      respectReducedMotion: false,
      useIntersectionObserver: false,
      preserveDecimals: true,
    });
    expect(counter.decimals).toBe(1);
    counter.$el = document.createElement('div');
    counter.init();

    advanceFrame(2000);
    expect(counter.current).toBe(99.5);
  });

  it('drops decimals when preserveDecimals is off, even for a fractional target', () => {
    const counter = createStatsCounterCore(99.5, {
      respectReducedMotion: false,
      useIntersectionObserver: false,
      preserveDecimals: false,
    });
    expect(counter.decimals).toBe(0);
  });

  it('does not start animating before the element intersects, when the observer gate is on', () => {
    const counter = createStatsCounterCore(100, {
      respectReducedMotion: false,
      useIntersectionObserver: true,
      preserveDecimals: false,
    });
    counter.$el = document.createElement('div');
    counter.init();

    expect(rafCallbacks).toHaveLength(0);

    MockIntersectionObserver.instances[0].trigger(true);
    expect(rafCallbacks).toHaveLength(1);

    advanceFrame(2000);
    expect(counter.current).toBe(100);
  });

  it('jumps straight to the target when prefers-reduced-motion matches', () => {
    mockMatchMedia(true);
    const counter = createStatsCounterCore(100, {
      respectReducedMotion: true,
      useIntersectionObserver: true,
      preserveDecimals: false,
    });
    counter.$el = document.createElement('div');
    counter.init();

    MockIntersectionObserver.instances[0].trigger(true);

    expect(counter.current).toBe(100);
    expect(rafCallbacks).toHaveLength(0);
  });

  it('ignores prefers-reduced-motion when respectReducedMotion is off (editor entry)', () => {
    mockMatchMedia(true);
    const counter = createStatsCounterCore(100, {
      respectReducedMotion: false,
      useIntersectionObserver: true,
      preserveDecimals: false,
    });
    counter.$el = document.createElement('div');
    counter.init();

    MockIntersectionObserver.instances[0].trigger(true);

    expect(rafCallbacks).toHaveLength(1);
    advanceFrame(2000);
    expect(counter.current).toBe(100);
  });

  it('handles a non-numeric target without throwing', () => {
    const counter = createStatsCounterCore(NaN, {
      respectReducedMotion: false,
      useIntersectionObserver: false,
      preserveDecimals: false,
    });
    counter.$el = document.createElement('div');

    expect(() => counter.init()).not.toThrow();
    expect(() => advanceFrame(2000)).not.toThrow();
    expect(counter.current).toBeNaN();
  });
});
