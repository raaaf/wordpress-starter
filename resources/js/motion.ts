// Motion helpers shared by the colour scheme switcher and the before/after
// slider. Everything here backs off to the plain, instant change when the
// visitor asks for reduced motion or the browser lacks the API.

export function prefersReducedMotion(): boolean {
  return (
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches
  );
}

/**
 * Centre of the origin element and the radius that reaches the farthest
 * viewport corner from there, so the circle covers the whole screen.
 */
export function revealCircle(
  rect: Pick<DOMRect, 'left' | 'top' | 'width' | 'height'>,
  viewportWidth: number,
  viewportHeight: number
): { x: number; y: number; radius: number } {
  const x = rect.left + rect.width / 2;
  const y = rect.top + rect.height / 2;
  const radius = Math.hypot(Math.max(x, viewportWidth - x), Math.max(y, viewportHeight - y));

  return { x, y, radius };
}

type ViewTransitionDocument = Document & {
  startViewTransition?: (update: () => void) => { ready: Promise<void> };
};

/**
 * Runs `update` (which flips the colour scheme) inside a View Transition that
 * reveals the new scheme as a circle growing from `origin`. Without the API,
 * without an origin or with reduced motion, `update` runs immediately.
 */
export function transitionColorScheme(update: () => void, origin: HTMLElement | null): void {
  const doc = document as ViewTransitionDocument;

  if (!origin || typeof doc.startViewTransition !== 'function' || prefersReducedMotion()) {
    update();
    return;
  }

  const { x, y, radius } = revealCircle(
    origin.getBoundingClientRect(),
    window.innerWidth,
    window.innerHeight
  );
  const ease = getComputedStyle(document.documentElement)
    .getPropertyValue('--motion-enter-ease')
    .trim();

  doc
    .startViewTransition(update)
    .ready.then(() => {
      document.documentElement.animate(
        { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`] },
        {
          duration: 560,
          easing: ease || 'cubic-bezier(0.22, 1, 0.36, 1)',
          pseudoElement: '::view-transition-new(root)',
        }
      );
    })
    // A skipped transition (e.g. the tab went hidden) has already applied the update.
    .catch(() => undefined);
}

// Before/after intro: 50 -> 15 -> 85 -> 50, each leg eased in and out.
const INTRO_STOPS = [50, 15, 85, 50];

export const INTRO_DURATION_MS = 1600;

function easeInOut(t: number): number {
  return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
}

/** Slider position (0 to 100) at `progress` (0 to 1) of the intro. */
export function introPosition(progress: number): number {
  const p = Math.max(0, Math.min(1, progress));
  const legs = INTRO_STOPS.length - 1;
  const leg = Math.min(legs - 1, Math.floor(p * legs));
  const local = easeInOut(p * legs - leg);
  const from = INTRO_STOPS[leg];
  const to = INTRO_STOPS[leg + 1];

  return from + (to - from) * local;
}
