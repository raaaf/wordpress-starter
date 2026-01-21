/**
 * Force ACF blocks to edit mode
 * This script switches all ACF blocks to edit mode in the Gutenberg editor
 */
(function() {
  'use strict';

  // Wait for wp.data to be available
  function waitForEditor() {
    if (typeof wp === 'undefined' || !wp.data || !wp.data.select('core/block-editor')) {
      setTimeout(waitForEditor, 100);
      return;
    }

    initForceEditMode();
  }

  function initForceEditMode() {
    const { select, dispatch, subscribe } = wp.data;

    // Function to force all ACF blocks to edit mode
    function forceEditMode() {
      const blocks = select('core/block-editor').getBlocks();

      blocks.forEach(function(block) {
        // Check if it's an ACF block
        if (block.name && block.name.startsWith('acf/')) {
          // Check current mode
          const currentMode = block.attributes.mode;

          // If not in edit mode, switch to edit
          if (currentMode !== 'edit') {
            dispatch('core/block-editor').updateBlockAttributes(block.clientId, {
              mode: 'edit'
            });
          }
        }

        // Also check inner blocks
        if (block.innerBlocks && block.innerBlocks.length > 0) {
          forceEditModeRecursive(block.innerBlocks);
        }
      });
    }

    function forceEditModeRecursive(blocks) {
      blocks.forEach(function(block) {
        if (block.name && block.name.startsWith('acf/')) {
          const currentMode = block.attributes.mode;
          if (currentMode !== 'edit') {
            dispatch('core/block-editor').updateBlockAttributes(block.clientId, {
              mode: 'edit'
            });
          }
        }
        if (block.innerBlocks && block.innerBlocks.length > 0) {
          forceEditModeRecursive(block.innerBlocks);
        }
      });
    }

    // Run immediately
    forceEditMode();

    // Run whenever blocks change (new block added, etc.)
    let lastBlockCount = 0;
    subscribe(function() {
      const blocks = select('core/block-editor').getBlocks();
      const currentCount = blocks.length;

      if (currentCount !== lastBlockCount) {
        lastBlockCount = currentCount;
        // Small delay to let the block initialize
        setTimeout(forceEditMode, 100);
      }
    });
  }

  // Start when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', waitForEditor);
  } else {
    waitForEditor();
  }
})();
