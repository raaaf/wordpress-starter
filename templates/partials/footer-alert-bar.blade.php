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
                                class="shrink-0 cursor-pointer rounded p-2 text-content-tertiary transition-colors hover:bg-surface-tertiary hover:text-content-secondary focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring-ghost)]"
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
