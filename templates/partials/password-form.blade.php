{{--
    Password form for pages protected with WordPress' built-in per-page password.

    WordPress' own the_password_form() emits unstyled markup, so the form is
    rebuilt here against the design system. The action, field name and cookie
    handling stay exactly as core expects them.
--}}

@php
    $fieldId = 'pwbox-' . (int) get_the_ID();
    // A cookie that is present while the gate still shows means the previous
    // attempt was wrong. COOKIEHASH is guarded so the template also renders
    // outside a full WordPress bootstrap.
    $hasError = defined('COOKIEHASH') && isset($_COOKIE['wp-postpass_' . COOKIEHASH]);
@endphp

<section class="section bg-surface py-20 md:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto">
            <x-card variant="outlined" padding="none">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-surface-accent-subtle mb-4">
                            <x-icon name="lock" class="w-8 h-8 text-icon-brand" />
                        </div>
                        <h1 class="text-h3 mb-2">{{ get_the_title() }}</h1>
                        <p class="text-content-secondary">
                            {{ __('Diese Seite ist passwortgeschützt. Bitte gib das Passwort ein.', 'wp-starter') }}
                        </p>
                    </div>

                    @if($hasError)
                        {{-- Appears after a full page load following a server round
                             trip, never on a keystroke, so a transition is safe. --}}
                        <div
                            class="mb-6"
                            x-data="{ shown: false }"
                            x-init="requestAnimationFrame(() => (shown = true))"
                            x-show="shown"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-cloak
                        >
                            <x-alert variant="error">
                                {{ __('Das eingegebene Passwort war nicht korrekt.', 'wp-starter') }}
                            </x-alert>
                        </div>
                    @endif

                    <form action="{{ esc_url(site_url('wp-login.php?action=postpass', 'login_post')) }}" method="post">
                        <div class="mb-6">
                            <x-input
                                type="password"
                                name="post_password"
                                :id="$fieldId"
                                :label="__('Passwort', 'wp-starter')"
                                :required="true"
                                size="md"
                                autocomplete="current-password"
                            />
                        </div>

                        <x-button
                            type="submit"
                            :title="__('Anzeigen', 'wp-starter')"
                            variant="primary"
                            size="md"
                            class="w-full justify-center"
                        />
                    </form>
                </div>
            </x-card>
        </div>
    </div>
</section>
