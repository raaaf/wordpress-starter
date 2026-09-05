{{-- Member Area Login Form (Partial) --}}
@php
    $authMode = function_exists('get_field') ? (get_field('member_auth_mode', 'option') ?: 'password') : 'password';
    $loginTitle = function_exists('get_field') ? (get_field('member_login_title', 'option') ?: __('Interner Bereich', 'wp-starter')) : __('Interner Bereich', 'wp-starter');
    $loginDescription = function_exists('get_field') ? (get_field('member_login_description', 'option') ?: __('Bitte melde dich an, um auf den internen Bereich zuzugreifen.', 'wp-starter')) : __('Bitte melde dich an, um auf den internen Bereich zuzugreifen.', 'wp-starter');
@endphp

<x-section background="primary" padding="xl">
    <div class="max-w-md mx-auto">
        <x-card variant="elevated" padding="lg">
            @include('partials.gate-card', ['gateIcon' => 'lock', 'gateHeading' => $loginTitle, 'gateDescription' => $loginDescription])

            {{-- Hidden until Alpine mounts: before hydration a submit would
                 post the password to the page URL, which nothing consumes. --}}
            <form
                x-data="memberLogin"
                method="post"
                action="{{ esc_url(get_permalink()) }}"
                @submit.prevent="submit"
                novalidate
                x-cloak
            >
                @if($authMode === 'wordpress')
                    <div class="mb-4">
                        <x-input
                            name="username"
                            type="text"
                            :label="__('Benutzername', 'wp-starter')"
                            iconLeft="user"
                            required
                            x-model="username"
                            autocomplete="username"
                        />
                    </div>
                @endif

                <div class="mb-6">
                    <x-input
                        name="password"
                        type="password"
                        :label="__('Passwort', 'wp-starter')"
                        iconLeft="lock"
                        required
                        x-model="password"
                        autocomplete="current-password"
                    />
                </div>

                <div x-show="error" role="alert" aria-live="assertive" aria-atomic="true" class="mb-4 flex items-start gap-3 p-4 rounded-lg bg-surface-error border border-line-error text-content-error text-sm" x-cloak>
                    <x-icon name="warning" class="w-5 h-5 text-icon-error shrink-0 mt-0.5" />
                    <span x-text="error"></span>
                </div>

                <x-button
                    type="submit"
                    :title="__('Anmelden', 'wp-starter')"
                    variant="primary"
                    class="w-full"
                    x-bind:disabled="loading"
                />

                <div x-show="loading" role="status" class="mt-3 text-center text-sm text-content-secondary" x-cloak>
                    {{ __('Wird geprüft…', 'wp-starter') }}
                </div>

            </form>
            <noscript><p class="text-content-secondary">{{ __('Die Anmeldung benötigt JavaScript.', 'wp-starter') }}</p></noscript>
        </x-card>
    </div>
</x-section>
