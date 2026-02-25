/**
 * TinyMCE Icon Picker Plugin
 *
 * Adds a toolbar button that opens a grid of available theme icons.
 * Clicking an icon inserts [icon name="..."] at the cursor position.
 */

declare global {
  interface Window {
    themeIconsData?: {
      icons: string[];
      baseUrl: string;
      nonce: string;
    };
    tinymce: typeof import('tinymce');
  }
}

(function () {
  if (typeof window.tinymce === 'undefined') {
    return;
  }

  window.tinymce.PluginManager.add('themeicons', function (editor) {
    editor.addButton('themeicons', {
      title: 'Icon einfügen',
      icon: 'image',
      onclick: function () {
        const data = window.themeIconsData;
        if (!data || !data.icons || data.icons.length === 0) {
          editor.windowManager.alert('Keine Icons gefunden.');
          return;
        }

        editor.windowManager.open({
          title: 'Icon auswählen',
          body: [
            {
              type: 'container',
              html: (() => {
                let html =
                  '<div style="display:flex;flex-wrap:wrap;gap:8px;max-width:600px;padding:8px;">';
                data.icons.forEach((name: string) => {
                  html += `<button type="button"
                    data-icon="${name}"
                    title="${name}"
                    style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:8px 12px;border:1px solid #ddd;border-radius:4px;background:#fff;cursor:pointer;font-size:11px;min-width:64px;"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='#fff'"
                  >
                    <img src="${data.baseUrl}${name}.svg" width="24" height="24" alt="${name}" style="opacity:0.7;">
                    ${name}
                  </button>`;
                });
                html += '</div>';
                return html;
              })(),
            },
          ],
          buttons: [{ text: 'Schließen', onclick: 'close' }],
          width: 640,
          height: 400,
          onopen: function () {
            const win = editor.windowManager.getWindows()[0];
            const container = win.find('container')[0];
            if (container) {
              const el = container.getEl();
              if (el) {
                el.addEventListener('click', function (e: Event) {
                  const target = (e.target as HTMLElement).closest(
                    '[data-icon]'
                  ) as HTMLElement | null;
                  if (target) {
                    const iconName = target.getAttribute('data-icon');
                    if (iconName) {
                      editor.insertContent(`[icon name="${iconName}"]`);
                      editor.windowManager.close();
                    }
                  }
                });
              }
            }
          },
        });
      },
    });
  });
})();
