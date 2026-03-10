{{-- Member Area Login Form (Partial) --}}
@php
    $authMode = function_exists('get_field') ? (get_field('member_auth_mode', 'option') ?: 'password') : 'password';
    $loginTitle = function_exists('get_field') ? (get_field('member_login_title', 'option') ?: __('Interner Bereich', 'wp-starter')) : __('Interner Bereich', 'wp-starter');
    $loginDescription = function_exists('get_field') ? (get_field('member_login_description', 'option') ?: __('Bitte melden Sie sich an, um auf den internen Bereich zuzugreifen.', 'wp-starter')) : __('Bitte melden Sie sich an, um auf den internen Bereich zuzugreifen.', 'wp-starter');
@endphp

<x-section background="primary" padding="xl">
    <div class="max-w-md mx-auto">
        <x-card variant="elevated" padding="lg">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-surface-accent-subtle mb-4">
                    <x-icon name="lock" class="w-8 h-8 text-icon-brand" />
                </div>
                <h1 class="text-h3 mb-2">{{ $loginTitle }}</h1>
                @if($loginDescription)
                    <p class="text-content-secondary">{{ $loginDescription }}</p>
                @endif
            </div>

            <form
                x-data="memberLogin"
                @submit.prevent="submit"
                novalidate
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

                <div x-show="error" role="alert" class="mb-4 flex items-start gap-3 p-4 rounded-lg bg-surface-error border border-line-error text-content-error text-sm" x-cloak>
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

                <div x-show="loading" class="mt-3 text-center text-sm text-content-secondary" x-cloak>
                    {{ __('Wird geprüft…', 'wp-starter') }}
                </div>
            </form>
        </x-card>
    </div>
</x-section>
