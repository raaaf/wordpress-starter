{{-- Member Area Downloads --}}
@php
    $rawDownloads = function_exists('get_field') ? (get_field('member_downloads', 'option') ?: []) : [];

    // Attach original index before sorting (needed for nonce + AJAX parameter)
    foreach ($rawDownloads as $i => $download) {
        $rawDownloads[$i]['_index'] = $i;
    }

    // Sort by download_sort field
    usort($rawDownloads, fn($a, $b) => (int)($a['download_sort'] ?? 0) - (int)($b['download_sort'] ?? 0));

    // Group by category
    $grouped = [];
    $categoryLabels = [
        'general' => __('Allgemein', 'wp-starter'),
        'reports' => __('Berichte', 'wp-starter'),
        'forms'   => __('Formulare', 'wp-starter'),
    ];
    foreach ($rawDownloads as $download) {
        $cat = $download['download_category'] ?? 'general';
        $grouped[$cat][] = $download;
    }
@endphp

<div class="space-y-8">
    <h2 class="text-h3">{{ __('Dokumente', 'wp-starter') }}</h2>

    @if(empty($rawDownloads))
        <x-card variant="default" padding="lg">
            <div class="text-center py-8 text-content-secondary">
                <x-icon name="download" class="w-12 h-12 mx-auto mb-3 text-icon-tertiary" />
                <p>{{ __('Noch keine Dokumente verfügbar.', 'wp-starter') }}</p>
            </div>
        </x-card>
    @else
        @foreach($grouped as $category => $items)
            <div>
                <h3 class="text-h5 mb-4 text-content-secondary uppercase tracking-wide text-xs font-semibold">
                    {{ $categoryLabels[$category] ?? $category }}
                </h3>
                <div class="space-y-3">
                    @foreach($items as $download)
                        @php
                            $index       = $download['_index'];
                            $sourceType  = $download['download_source_type'] ?? 'upload';
                            $available   = (bool) ($download['download_available'] ?? true);
                            $lastModified = $download['download_last_modified'] ?? null;

                            // Determine file extension badge
                            $fileExt = '';
                            if ($sourceType === 'upload') {
                                $file    = $download['download_file'] ?? null;
                                $fileId  = $file ? (is_array($file) ? ($file['ID'] ?? 0) : (int)$file) : 0;
                                $fileName = is_array($file) ? ($file['filename'] ?? '') : '';
                                $fileExt = $fileName ? strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)) : '';
                                $hasFile = $fileId > 0;
                            } elseif ($sourceType === 'external') {
                                $externalUrl = $download['download_external_url'] ?? '';
                                $fileExt     = $externalUrl ? strtoupper(pathinfo($externalUrl, PATHINFO_EXTENSION)) : '';
                                $hasFile     = !empty($externalUrl);
                            } else {
                                // folder — individual imported file
                                $externalUrl = $download['download_external_url'] ?? '';
                                $fileExt     = $externalUrl ? strtoupper(pathinfo($externalUrl, PATHINFO_EXTENSION)) : 'Extern';
                                $hasFile     = !empty($externalUrl);
                            }

                            // Build download URL using index-based parameter
                            $downloadNonce = wp_create_nonce('member_download_' . $index);
                            $downloadUrl   = admin_url('admin-ajax.php')
                                . '?action=member_download&download_index=' . $index
                                . '&nonce=' . $downloadNonce;

                            // Format last-modified date
                            $lastModifiedLabel = '';
                            if ($lastModified) {
                                $timestamp = strtotime($lastModified);
                                if ($timestamp !== false) {
                                    $lastModifiedLabel = sprintf(
                                        __('Aktualisiert: %s', 'wp-starter'),
                                        date_i18n(get_option('date_format'), $timestamp)
                                    );
                                }
                            }
                        @endphp
                        <x-card variant="default" padding="md">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-4 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-surface-secondary flex items-center justify-center shrink-0 mt-0.5">
                                        <x-icon name="download" class="w-5 h-5 text-icon-secondary" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-content">{{ $download['download_title'] ?? '' }}</div>
                                        @if($download['download_description'] ?? '')
                                            <p class="text-sm text-content-secondary mt-1">{{ $download['download_description'] }}</p>
                                        @endif
                                        <div class="flex flex-wrap items-center gap-2 mt-2">
                                            @if($fileExt)
                                                <x-badge variant="gray" size="sm">{{ $fileExt }}</x-badge>
                                            @endif
                                            @if(!$available)
                                                <x-badge variant="gray" size="sm">{{ __('Nicht verfügbar', 'wp-starter') }}</x-badge>
                                            @endif
                                            @if($lastModifiedLabel)
                                                <span class="text-xs text-content-secondary">{{ $lastModifiedLabel }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($hasFile && $available)
                                    <x-button
                                        url="{{ $downloadUrl }}"
                                        :title="__('Herunterladen', 'wp-starter')"
                                        variant="primary"
                                        size="sm"
                                        class="shrink-0"
                                    />
                                @endif
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
