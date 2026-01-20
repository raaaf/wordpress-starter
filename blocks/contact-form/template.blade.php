{{--
    Contact Form Block (Contact Form 7)

    Uses shared components: x-section, x-grid, x-icon
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
                <h2 class="text-h2 mb-6 text-content">{{ $title }}</h2>
            @endif

            @if($content)
                <div class="mb-8 prose text-content-secondary">
                    {!! $content !!}
                </div>
            @endif

            @if($showContactInfo && ($companyName || $address || $phone || $email))
                <div class="p-6 rounded-lg bg-surface-secondary">
                    <h3 class="text-h5 mb-4 text-content">Kontaktdaten</h3>

                    @if($companyName)
                        <p class="mb-2 font-medium text-content">{{ $companyName }}</p>
                    @endif

                    @if($address)
                        <p class="mb-4 whitespace-pre-line text-content-secondary">{{ $address }}</p>
                    @endif

                    @if($phone)
                        <p class="flex items-center gap-2 mb-2 text-content-secondary">
                            <x-icon name="phone" size="lg" />
                            <a href="tel:{{ esc_attr(preg_replace('/[^0-9+]/', '', $phone)) }}" class="hover:text-content-link-hover">{{ esc_html($phone) }}</a>
                        </p>
                    @endif

                    @if($email)
                        <p class="flex items-center gap-2 text-content-secondary">
                            <x-icon name="mail" size="lg" />
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
