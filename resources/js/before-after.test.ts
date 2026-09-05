import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { createBeforeAfterComponent, type BeforeAfterComponent } from './app';

/**
 * Tests for the before/after slider Alpine.js component
 * (createBeforeAfterComponent). Keyboard navigation, aria-valuenow and the
 * reduced-motion transition class are declared directly in the Alpine
 * bindings of templates/flexible/before-after.blade.php
 * (@keydown.*, :aria-valuenow, motion-reduce:transition-none), not in this
 * factory, so they are not exercised here. A dedicated exported keyboard
 * handler (mirroring handleMouseDown/handleTouchStart) would be needed to
 * unit-test that behaviour directly.
 */

function buildHandleAndContainer(rect: Partial<DOMRect> = {}): {
  container: HTMLElement;
  handle: HTMLElement;
} {
  const container = document.createElement('div');
  const handle = document.createElement('div');
  handle.className = 'before-after-handle';
  container.appendChild(handle);
  document.body.appendChild(container);

  vi.spyOn(container, 'getBoundingClientRect').mockReturnValue({
    left: 0,
    width: 200,
    top: 0,
    height: 100,
    right: 200,
    bottom: 100,
    x: 0,
    y: 0,
    toJSON: () => ({}),
    ...rect,
  } as DOMRect);

  return { container, handle };
}

// Built as a plain Event with a `touches` property attached rather than
// `new TouchEvent(...)`, so the test does not depend on happy-dom's
// TouchEvent/Touch constructor support; the handler only reads e.touches[0].
function touchEvent(type: string, clientX: number): TouchEvent {
  const event = new Event(type) as unknown as TouchEvent & { touches: Touch[] };
  Object.defineProperty(event, 'touches', { value: [{ clientX } as Touch] });
  return event as TouchEvent;
}

describe('createBeforeAfterComponent', () => {
  let component: BeforeAfterComponent;

  beforeEach(() => {
    component = createBeforeAfterComponent();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('starts at position 50', () => {
    expect(component.position).toBe(50);
  });

  describe('handleMouseDown', () => {
    it('updates position on mousemove within the container bounds', () => {
      const { handle } = buildHandleAndContainer();
      component.handleMouseDown({
        preventDefault: vi.fn(),
        target: handle,
      } as unknown as MouseEvent);

      document.dispatchEvent(new MouseEvent('mousemove', { clientX: 100 }));
      expect(component.position).toBe(50);

      document.dispatchEvent(new MouseEvent('mousemove', { clientX: 0 }));
      expect(component.position).toBe(0);
    });

    it('clamps position to 0..100 for coordinates outside the container', () => {
      const { handle } = buildHandleAndContainer();
      component.handleMouseDown({
        preventDefault: vi.fn(),
        target: handle,
      } as unknown as MouseEvent);

      document.dispatchEvent(new MouseEvent('mousemove', { clientX: -50 }));
      expect(component.position).toBe(0);

      document.dispatchEvent(new MouseEvent('mousemove', { clientX: 500 }));
      expect(component.position).toBe(100);
    });

    it('prevents the default action on mousedown to avoid text-selection drag', () => {
      const { handle } = buildHandleAndContainer();
      const preventDefault = vi.fn();
      component.handleMouseDown({ preventDefault, target: handle } as unknown as MouseEvent);

      expect(preventDefault).toHaveBeenCalledTimes(1);
    });

    it('stops updating position after mouseup', () => {
      const { handle } = buildHandleAndContainer();
      component.handleMouseDown({
        preventDefault: vi.fn(),
        target: handle,
      } as unknown as MouseEvent);

      document.dispatchEvent(new MouseEvent('mousemove', { clientX: 100 }));
      expect(component.position).toBe(50);

      document.dispatchEvent(new MouseEvent('mouseup'));
      document.dispatchEvent(new MouseEvent('mousemove', { clientX: 0 }));
      expect(component.position).toBe(50);
    });

    it('does nothing when the event target is not inside a handle', () => {
      const orphan = document.createElement('div');
      document.body.appendChild(orphan);
      component.handleMouseDown({
        preventDefault: vi.fn(),
        target: orphan,
      } as unknown as MouseEvent);

      document.dispatchEvent(new MouseEvent('mousemove', { clientX: 0 }));
      expect(component.position).toBe(50);
    });
  });

  describe('handleTouchStart', () => {
    it('updates position on touchmove, clamped to 0..100, without scroll-jacking the move itself', () => {
      const { handle } = buildHandleAndContainer();
      const startPreventDefault = vi.fn();
      component.handleTouchStart({
        preventDefault: startPreventDefault,
        target: handle,
      } as unknown as TouchEvent);
      expect(startPreventDefault).toHaveBeenCalledTimes(1);

      document.dispatchEvent(touchEvent('touchmove', 150));
      expect(component.position).toBe(75);

      document.dispatchEvent(touchEvent('touchmove', -100));
      expect(component.position).toBe(0);
    });

    it('stops updating position after touchend', () => {
      const { handle } = buildHandleAndContainer();
      component.handleTouchStart({
        preventDefault: vi.fn(),
        target: handle,
      } as unknown as TouchEvent);

      document.dispatchEvent(touchEvent('touchmove', 150));
      expect(component.position).toBe(75);

      document.dispatchEvent(new Event('touchend'));
      document.dispatchEvent(touchEvent('touchmove', 0));
      expect(component.position).toBe(75);
    });
  });
});
