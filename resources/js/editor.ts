/**
 * Editor-specific JavaScript
 * Loads Alpine.js for ACF block previews in the Gutenberg editor
 */

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Stats Counter Component for block editor
interface StatsCounterComponent {
  $el: HTMLElement;
  target: number;
  current: number;
  duration: number;
  started: boolean;
  init(): void;
  animate(): void;
}

function createStatsCounterComponent(target: number): StatsCounterComponent {
  return {
    $el: null as unknown as HTMLElement,
    target,
    current: 0,
    duration: 2000,
    started: false,

    init() {
      const observer = new IntersectionObserver(
        (entries) => {
          if (entries[0].isIntersecting && !this.started) {
            this.started = true;
            this.animate();
          }
        },
        { threshold: 0.5 }
      );
      observer.observe(this.$el);
    },

    animate() {
      const start = performance.now();
      const step = (timestamp: number) => {
        const progress = Math.min((timestamp - start) / this.duration, 1);
        this.current = Math.floor(progress * this.target);
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

// Make Alpine available globally
window.Alpine = Alpine;

// Register Alpine plugins
Alpine.plugin(collapse);

// Register Alpine components
Alpine.data('statsCounter', (target: number) => createStatsCounterComponent(target));

// Start Alpine
Alpine.start();

// Re-initialize Alpine when block previews are updated
// Use MutationObserver to detect when ACF refreshes block previews
const observer = new MutationObserver((mutations) => {
  for (const mutation of mutations) {
    if (mutation.addedNodes.length > 0) {
      // Check if any added nodes contain Alpine directives
      mutation.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement && node.querySelector('[x-data]')) {
          Alpine.initTree(node);
        }
      });
    }
  }
});

// Observe the editor for changes
const editorRoot = document.getElementById('editor') || document.body;
observer.observe(editorRoot, { childList: true, subtree: true });

/**
 * ACF Icon Radio Field Enhancement
 * Replaces text labels with actual SVG icons in radio button fields
 */
const iconRadioEnhancer = {
  // Cache for loaded SVG icons
  iconCache: new Map<string, string>(),

  // Theme URL for icons
  themeUrl: '',

  /**
   * Initialize icon radio enhancement
   */
  init() {
    // Get theme URL from window.themeData (set via wp_add_inline_script)
    const themeData = (window as { themeData?: { themeUrl?: string } }).themeData;
    if (themeData?.themeUrl) {
      this.themeUrl = themeData.themeUrl;
    }

    // Fallback: try to get from stylesheet link
    if (!this.themeUrl) {
      const stylesheetLink = document.querySelector<HTMLLinkElement>(
        'link[href*="/themes/"][href*="/dist/"]'
      );
      if (stylesheetLink) {
        const match = stylesheetLink.href.match(/(.+\/themes\/[^/]+)/);
        if (match) {
          this.themeUrl = match[1];
        }
      }
    }

    // Initial enhancement
    this.enhanceAllIconRadios();

    // Watch for new ACF fields (when blocks are added/edited)
    const acfObserver = new MutationObserver(() => {
      this.enhanceAllIconRadios();
    });

    acfObserver.observe(document.body, {
      childList: true,
      subtree: true,
    });
  },

  /**
   * Find and enhance all icon radio fields
   */
  enhanceAllIconRadios() {
    // Find all ACF icon radio fields
    const radioFields = document.querySelectorAll<HTMLElement>(
      '.acf-icon-radio-field .acf-radio-list'
    );

    radioFields.forEach((radioList) => {
      if (!radioList.dataset.iconEnhanced) {
        this.enhanceRadioList(radioList);
        radioList.dataset.iconEnhanced = 'true';
      }
    });
  },

  /**
   * Enhance a radio list with icon previews
   */
  enhanceRadioList(radioList: HTMLElement) {
    const labels = radioList.querySelectorAll<HTMLLabelElement>('label');

    labels.forEach((label) => {
      // ACF structure: <label><input type="radio" value="..."> Text</label>
      const input = label.querySelector<HTMLInputElement>('input[type="radio"]');
      if (!input) return;

      const iconName = input.value;

      // Store original text (excluding input element)
      const originalText = label.textContent?.trim() || '';
      label.setAttribute('title', originalText);
      label.setAttribute('data-icon', iconName);

      if (iconName && this.themeUrl) {
        // Load and insert icon (keep the input, replace text)
        this.loadIcon(iconName).then((svg) => {
          if (svg) {
            // Clear label but keep input
            label.innerHTML = '';
            label.appendChild(input);
            label.insertAdjacentHTML('beforeend', svg);
          }
        });
      } else {
        // Empty value = "No icon" option
        label.innerHTML = '';
        label.appendChild(input);
        label.insertAdjacentHTML('beforeend', '<span class="no-icon-text">—</span>');
        label.style.width = 'auto';
        label.style.padding = '0 12px';
        label.style.fontSize = '12px';
      }
    });
  },

  /**
   * Load an icon SVG from the theme
   */
  async loadIcon(iconName: string): Promise<string> {
    if (this.iconCache.has(iconName)) {
      return this.iconCache.get(iconName)!;
    }

    try {
      const response = await fetch(`${this.themeUrl}/resources/icons/${iconName}.svg`);
      if (response.ok) {
        let svg = await response.text();
        // Remove width/height attributes for flexible sizing
        svg = svg.replace(/\s*(width|height)="[^"]*"/g, '');
        // Add currentColor for proper color inheritance
        svg = svg.replace(/fill="[^"]*"/g, 'fill="currentColor"');
        this.iconCache.set(iconName, svg);
        return svg;
      }
    } catch {
      // Icon loading failed silently
    }

    return '';
  },
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => iconRadioEnhancer.init());
} else {
  iconRadioEnhancer.init();
}
