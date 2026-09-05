import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import {
  createStyleguideModulComponent,
  type StyleguideModulComponent,
  initCf7SpamTrapTokens,
} from './app';

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

/**
 * Tests for the CF7 spam-trap JS token (mirrors
 * ContactForm7Configurator::JS_TOKEN_FIELD / detectSpam()).
 */
describe('CF7 Spam Trap Token', () => {
  let form: HTMLFormElement;
  let tokenField: HTMLInputElement;

  beforeEach(() => {
    form = document.createElement('form');
    form.className = 'wpcf7-form';
    form.innerHTML = `
      <input type="text" name="your-name">
      <input type="hidden" name="_wpcf7_js_token" value="">
    `;
    document.body.appendChild(form);
    tokenField = form.elements.namedItem('_wpcf7_js_token') as HTMLInputElement;
  });

  afterEach(() => {
    form.remove();
  });

  it('leaves the token field empty until the visitor interacts with the form', () => {
    initCf7SpamTrapTokens();

    expect(tokenField.value).toBe('');
  });

  it('fills the token field on the first interaction with the form', () => {
    initCf7SpamTrapTokens();

    const nameField = form.elements.namedItem('your-name') as HTMLInputElement;
    nameField.dispatchEvent(new Event('focusin', { bubbles: true }));

    expect(tokenField.value).toBe('ok');
  });
});
