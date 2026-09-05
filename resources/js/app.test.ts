import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { createStyleguideModulComponent, type StyleguideModulComponent } from './app';

/**
 * Tests for the styleguide module switcher (registered as Alpine.data
 * 'styleguideModul', used by templates/partials/styleguide-module.blade.php).
 */
describe('Styleguide Modul Component', () => {
  let modul: StyleguideModulComponent;
  let container: HTMLElement;

  beforeEach(() => {
    modul = createStyleguideModulComponent();

    container = document.createElement('div');
    container.innerHTML = `
      <div data-variant="eins"></div>
      <div data-variant="zwei"></div>
    `;
    modul.$el = container;

    const chips = document.createElement('div');
    chips.innerHTML = `
      <button role="radio"></button>
      <button role="radio"></button>
    `;
    container.appendChild(chips);
    document.body.appendChild(container);
    modul.$refs = { chips };
    modul.$nextTick = (callback?: () => void) => {
      callback?.();
      return Promise.resolve();
    };
  });

  afterEach(() => {
    container.remove();
  });

  it('starts on the first instance', () => {
    expect(modul.aktiv).toBe(0);
  });

  it('updates aktiv and focuses the matching radio when waehlen is called', () => {
    modul.waehlen(1);

    expect(modul.aktiv).toBe(1);
    expect(document.activeElement).toBe(
      (modul.$refs.chips as HTMLElement).querySelectorAll('[role=radio]')[1]
    );
  });

  it('does not touch the location hash unless schreibeHash is true', () => {
    const before = window.location.hash;
    modul.waehlen(1);
    expect(window.location.hash).toBe(before);
  });

  it('writes the anchor hash when schreibeHash is true', () => {
    modul.waehlen(1, true);
    expect(window.location.hash).toBe('#zwei');
  });

  it('activates the instance matching the current hash on init', () => {
    window.location.hash = '#zwei';
    modul.init();
    expect(modul.aktiv).toBe(1);
    window.location.hash = '';
  });

  it('leaves aktiv untouched when there is no hash', () => {
    window.location.hash = '';
    modul.init();
    expect(modul.aktiv).toBe(0);
  });
});
