{{--
    Contact Form (Contact Form 7) - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-icon, x-card, x-link
    Fields: title, content, form_id, show_contact_info, background_color
--}}

@php
    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $content = get_sub_field('content');
    $formId = get_sub_field('form_id');
    $showContactInfo = get_sub_field('show_contact_info') ?? true;
    $background = get_sub_field('background_color') ?: 'primary';

    // Get contact info from theme options
    $companyName = \WordpressStarter\Acf\Fields::option('company_name', '');
    $address = \WordpressStarter\Acf\Fields::option('address', '');
    $phone = \WordpressStarter\Acf\Fields::option('phone', '');
    $email = \WordpressStarter\Acf\Fields::option('email', '');
@endphp

<x-section :background="$background" class="contact-form">
    <x-grid cols="2" gap="xl" class="items-start">
        {{-- Left: Title, Content, Contact Info --}}
        <div>
            @if($title)
                <h2 class="text-h2 mb-6 text-content">{!! $title !!}</h2>
            @endif

            @if($content)
                <div class="mb-8 prose text-content-secondary">
                    {!! $content !!}
                </div>
            @endif

            @if($showContactInfo && ($companyName || $address || $phone || $email))
                <x-card variant="filled" padding="lg">
                    <h3 class="text-h5 mb-4 text-content">{{ __('Kontaktdaten', 'wp-starter') }}</h3>

                    @if($companyName)
                        <p class="mb-2 font-medium text-content">{{ $companyName }}</p>
                    @endif

                    @if($address)
                        <p class="mb-4 whitespace-pre-line text-content-secondary">{{ $address }}</p>
                    @endif

                    @if($phone)
                        <p class="flex items-center gap-2 mb-2 text-content-secondary">
                            <x-icon name="phone" size="lg" />
                            <x-link url="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" variant="dark">{{ $phone }}</x-link>
                        </p>
                    @endif

                    @if($email)
                        <p class="flex items-center gap-2 text-content-secondary">
                            <x-icon name="mail" size="lg" />
                            <x-link url="mailto:{{ $email }}" variant="dark">{{ $email }}</x-link>
                        </p>
                    @endif
                </x-card>
            @endif
        </div>

        {{-- Right: Contact Form 7 --}}
        <div class="p-8 rounded-lg bg-surface-secondary" aria-live="polite" aria-atomic="false">
            @if($formId && shortcode_exists('contact-form-7'))
                {!! do_shortcode('[contact-form-7 id="' . intval($formId) . '"]') !!}
            @else
                <p class="text-content-secondary">
                    @if(!shortcode_exists('contact-form-7'))
                        {{ __('Contact Form 7 Plugin ist nicht installiert.', 'wp-starter') }}
                    @else
                        {{ __('Bitte wähle ein Formular aus.', 'wp-starter') }}
                    @endif
                </p>
            @endif
        </div>
    </x-grid>
</x-section>
