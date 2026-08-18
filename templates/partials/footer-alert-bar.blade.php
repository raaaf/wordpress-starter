@php
    $alerts = \WordpressStarter\Support\FooterAlertBar::getVisibleAlerts();
@endphp

@if(!empty($alerts))
    <aside role="region" aria-label="{{ __('Hinweise', 'wp-starter') }}" class="border-t border-line bg-surface-secondary">
        @foreach($alerts as $alert)
            <div
                class="border-b border-line last:border-b-0"
                @if($alert['dismissible'])
                    x-data="{ dismissed: (() => { try { const d = JSON.parse(localStorage.getItem('{{ $alert['storage_key'] }}')); return d && d.t > Date.now() - 604800000; } catch { return false; } })() }"
                    x-show="!dismissed"
                    {{-- Fade plus leichtes Zusammenziehen beim Schließen, damit der
                         Hinweis nicht in einem Frame verschwindet; Exit-Token der
                         Motion-Skala wie beim Alert-Component. --}}
                    x-transition:leave="transition duration-[var(--motion-exit-duration)] ease-[var(--motion-exit-ease)]"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    x-cloak
                @endif
            >
                <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 text-xs leading-relaxed text-content-secondary [&_p]:mb-0">
                            {!! wp_kses_post($alert['text']) !!}
                        </div>
                        @if($alert['dismissible'])
                            <button
                                type="button"
                                @click="localStorage.setItem('{{ $alert['storage_key'] }}', JSON.stringify({t: Date.now()})); dismissed = true"
                                {{-- 44x44 Mindest-Trefferfläche wie bei den Social-Buttons in
                                     team.blade.php, inline-flex zentriert das 16px-Icon darin. --}}
                                class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded p-2 min-h-11 min-w-11 text-content-tertiary transition-colors hover:bg-surface-tertiary hover:text-content-secondary focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring-ghost)]"
                                aria-label="{{ __('Hinweis schließen', 'wp-starter') }}"
                            >
                                <x-icon name="close" class="h-4 w-4" />
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </aside>
@endif
