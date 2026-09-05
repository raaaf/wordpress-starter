{{--
    Password form for pages protected with WordPress' built-in per-page password.

    WordPress' own the_password_form() emits unstyled markup, so the form is
    rebuilt here against the design system. The action, field name and cookie
    handling stay exactly as core expects them.
--}}

@php
    $fieldId = 'pwbox-' . (int) get_the_ID();
    // The wp-postpass_* cookie is site-wide, not per-post: a visitor who
    // unlocked a DIFFERENT protected page still carries it here, and
    // post_password_required() is true again for THIS post simply because
    // that cookie doesn't match this post's password. That looks identical
    // to "just submitted the wrong password" from this cookie check alone,
    // and there is no reliable request signal (Referer survives the
    // wp-login.php redirect unpredictably across browsers) to tell the two
    // apart. So the message stays neutral instead of asserting a wrong
    // password. COOKIEHASH is guarded so the template also renders outside
    // a full WordPress bootstrap.
    $hasError = defined('COOKIEHASH')
        && isset($_COOKIE['wp-postpass_' . COOKIEHASH])
        && post_password_required();
@endphp

<section class="section bg-surface py-20 md:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto">
            <x-card variant="outlined" padding="none">
                <div class="p-8">
                    @include('partials.gate-card', [
                        'gateIcon' => 'lock',
                        'gateHeading' => get_the_title(),
                        'gateDescription' => __('Diese Seite ist passwortgeschützt. Bitte gib das Passwort ein.', 'wp-starter'),
                    ])

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
                                {{ __('Bitte geben Sie das Passwort erneut ein.', 'wp-starter') }}
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
                            :title="__('Inhalt anzeigen', 'wp-starter')"
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
