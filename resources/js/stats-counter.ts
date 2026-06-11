/**
 * Shared stats counter animation core.
 * Both app.ts and editor.ts import from here.
 */

export interface StatsCounterOptions {
  /** Whether to respect prefers-reduced-motion (app entry: yes; editor: no) */
  respectReducedMotion: boolean;
  /** Whether to use an IntersectionObserver (app entry: yes; editor: yes) */
  useIntersectionObserver: boolean;
  /** Whether to preserve decimal precision from the target value (app entry: yes; editor: no) */
  preserveDecimals: boolean;
}

export interface StatsCounterCore {
  $el: HTMLElement;
  target: number;
  current: number;
  decimals: number;
  duration: number;
  started: boolean;
  observer: IntersectionObserver | null;
  init(): void;
  animate(): void;
}

export function createStatsCounterCore(
  target: number,
  options: StatsCounterOptions
): StatsCounterCore {
  const decimals = options.preserveDecimals ? (target.toString().split('.')[1] || '').length : 0;

  return {
    $el: null as unknown as HTMLElement,
    target,
    current: 0,
    decimals,
    duration: 2000,
    started: false,
    observer: null,

    init() {
      const prefersReducedMotion =
        options.respectReducedMotion &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      if (options.useIntersectionObserver) {
        this.observer = new IntersectionObserver(
          (entries) => {
            if (entries[0].isIntersecting && !this.started) {
              this.started = true;
              this.observer?.disconnect();
              if (prefersReducedMotion) {
                this.current = this.target;
              } else {
                this.animate();
              }
            }
          },
          { threshold: 0.5 }
        );
        this.observer.observe(this.$el as Element);
      } else {
        this.animate();
      }
    },

    animate() {
      const start = performance.now();
      const step = (timestamp: number) => {
        const progress = Math.min((timestamp - start) / this.duration, 1);
        if (this.decimals > 0) {
          const multiplier = Math.pow(10, this.decimals);
          this.current = Math.round(progress * this.target * multiplier) / multiplier;
        } else {
          this.current = Math.floor(progress * this.target);
        }
        if (progress < 1) {
          requestAnimationFrame(step);
        } else {
          this.current = this.target;
        }
      };
      requestAnimationFrame(step);
    },
  };
}
