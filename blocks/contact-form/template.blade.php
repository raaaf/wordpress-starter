{{--
    Contact Form Block (Contact Form 7)

    Uses shared components: x-section, x-grid
    Fields: title, content, form_id, show_contact_info, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $content = $fields['content'] ?? '';
    $formId = $fields['form_id'] ?? '';
    $showContactInfo = $fields['show_contact_info'] ?? true;
    $background = $fields['background_color'] ?? 'primary';

    // Get contact info from theme options
    $companyName = \WordpressStarter\Acf\Fields::option('company_name', '');
    $address = \WordpressStarter\Acf\Fields::option('address', '');
    $phone = \WordpressStarter\Acf\Fields::option('phone', '');
    $email = \WordpressStarter\Acf\Fields::option('email', '');
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} contact-form">
    <x-grid cols="2" gap="xl" class="items-start">
        {{-- Left: Title, Content, Contact Info --}}
        <div>
            @if($title)
                <h2 class="mb-6 text-3xl font-bold text-content">{{ $title }}</h2>
            @endif

            @if($content)
                <div class="mb-8 prose text-content-secondary">
                    {!! $content !!}
                </div>
            @endif

            @if($showContactInfo && ($companyName || $address || $phone || $email))
                <div class="p-6 rounded-lg bg-surface-secondary">
                    <h3 class="mb-4 text-lg font-semibold text-content">Kontaktdaten</h3>

                    @if($companyName)
                        <p class="mb-2 font-medium text-content">{{ $companyName }}</p>
                    @endif

                    @if($address)
                        <p class="mb-4 whitespace-pre-line text-content-secondary">{{ $address }}</p>
                    @endif

                    @if($phone)
                        <p class="flex items-center gap-2 mb-2 text-content-secondary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a href="tel:{{ esc_attr(preg_replace('/[^0-9+]/', '', $phone)) }}" class="hover:text-content-link-hover">{{ esc_html($phone) }}</a>
                        </p>
                    @endif

                    @if($email)
                        <p class="flex items-center gap-2 text-content-secondary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <a href="mailto:{{ esc_attr($email) }}" class="hover:text-content-link-hover">{{ esc_html($email) }}</a>
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Right: Contact Form 7 --}}
        <div class="p-8 rounded-lg bg-surface-secondary">
            @if($formId && shortcode_exists('contact-form-7'))
                {!! do_shortcode('[contact-form-7 id="' . intval($formId) . '"]') !!}
            @else
                <p class="text-content-secondary">
                    @if(!shortcode_exists('contact-form-7'))
                        Contact Form 7 Plugin ist nicht installiert.
                    @else
                        Bitte wähle ein Formular aus.
                    @endif
                </p>
            @endif
        </div>
    </x-grid>
</x-section>
