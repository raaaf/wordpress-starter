// Alpine.js Component Context Types
// These types provide missing module declarations for Alpine plugins
// and a helper type for Alpine magic properties.

// Type declaration for @alpinejs/collapse plugin
declare module '@alpinejs/collapse' {
  import { PluginCallback } from 'alpinejs';
  const collapse: PluginCallback;
  export default collapse;
}

// Type declaration for @alpinejs/intersect plugin
declare module '@alpinejs/intersect' {
  import { PluginCallback } from 'alpinejs';
  const intersect: PluginCallback;
  export default intersect;
}

/**
 * Alpine magic properties injected at runtime by Alpine.js.
 * Add this to component interfaces so TypeScript knows about $el, $nextTick, etc.
 *
 * Usage: interface MyComponent extends AlpineMagics { ... }
 */
export interface AlpineMagics {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  $data: Record<string, any>;
  $dispatch: (event: string, detail?: unknown) => void;
  $el: HTMLElement;
  $id: (name: string, key?: number | string | null) => string;
  $nextTick: (callback?: () => void) => Promise<void>;
  $refs: Record<string, HTMLElement>;
  $root: HTMLElement;
  $watch: <T>(property: string, callback: (value: T, oldValue: T) => void) => void;
}
