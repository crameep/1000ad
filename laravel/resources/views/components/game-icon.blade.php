@props(['src', 'alt', 'size' => 32])

@php
    $cacheBust = '';
    if ($src && !str_contains($src, '?')) {
        $filePath = public_path(str_replace('/images/', 'images/', parse_url($src, PHP_URL_PATH) ?? ''));
        if (file_exists($filePath)) {
            $cacheBust = '?v=' . filemtime($filePath);
        }
    }
@endphp

<img
    src="{{ $src . $cacheBust }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $size }}"
    class="game-icon game-icon-{{ $size }}"
    loading="lazy"
    onerror="this.style.display='none'"
>
