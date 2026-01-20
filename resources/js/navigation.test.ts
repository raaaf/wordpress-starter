import { describe, it, expect, beforeEach } from 'vitest';
import { createNavigationComponent, type NavigationComponent } from './app';

/**
 * Tests for the navigation Alpine.js component.
 */
describe('Navigation Component', () => {
  let navigation: NavigationComponent;

  beforeEach(() => {
    navigation = createNavigationComponent();
  });

  it('has initial state closed', () => {
    expect(navigation.isOpen).toBe(false);
  });

  it('opens when toggle is called while closed', () => {
    navigation.toggle();

    expect(navigation.isOpen).toBe(true);
  });

  it('closes when toggle is called while open', () => {
    navigation.isOpen = true;

    navigation.toggle();

    expect(navigation.isOpen).toBe(false);
  });

  it('sets isOpen to false when close is called', () => {
    navigation.isOpen = true;

    navigation.close();

    expect(navigation.isOpen).toBe(false);
  });

  it('close is idempotent - multiple calls have same result', () => {
    navigation.isOpen = true;

    navigation.close();
    navigation.close();
    navigation.close();

    expect(navigation.isOpen).toBe(false);
  });

  it('can toggle multiple times', () => {
    expect(navigation.isOpen).toBe(false);

    navigation.toggle();
    expect(navigation.isOpen).toBe(true);

    navigation.toggle();
    expect(navigation.isOpen).toBe(false);

    navigation.toggle();
    expect(navigation.isOpen).toBe(true);
  });

  it('close works regardless of current state', () => {
    // When already closed
    navigation.isOpen = false;
    navigation.close();
    expect(navigation.isOpen).toBe(false);

    // When open
    navigation.isOpen = true;
    navigation.close();
    expect(navigation.isOpen).toBe(false);
  });
});
