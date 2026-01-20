// Alpine.js Component Context Types
// These types extend the component interface with Alpine's magic properties

export interface AlpineComponentContext {
  $el: HTMLElement;
  $refs: Record<string, HTMLElement>;
  $store: Record<string, any>;
  $dispatch: (event: string, detail?: any) => void;
  $nextTick: (callback: () => void) => Promise<void>;
  $watch: <T>(property: string, callback: (value: T, oldValue: T) => void) => void;
  $root: HTMLElement;
  $data: Record<string, any>;
  $id: (name: string, key?: string | number) => string;
}

// Augment the navigation component to include Alpine context
declare module '../js/app' {
  interface NavigationComponent extends AlpineComponentContext {}
}
