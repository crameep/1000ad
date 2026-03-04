@props(['src', 'alt', 'size' => 32])

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $size }}"
    class="game-icon game-icon-{{ $size }}"
    loading="lazy"
    onerror="this.style.display='none'"
>
