// Import Alpine.js
import Alpine from 'alpinejs';

// Make Alpine available globally
window.Alpine = Alpine;

// Alpine.js components
Alpine.data('navigation', () => ({
    isOpen: false,
    toggle() {
        this.isOpen = !this.isOpen;
    },
    close() {
        this.isOpen = false;
    }
}));

// Start Alpine
Alpine.start();
