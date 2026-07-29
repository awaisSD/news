<?php

use App\Entities\Media;
use Config\Media as MediaConfig;

/**
 * View-layer helpers for rendering media (images) — placeholder fallback,
 * responsive srcset, and escaped alt text. Plain functions (not a class) so
 * they can be called directly from views via `helper('image')`.
 */

if (! function_exists('media_url')) {
    /**
     * Resolves a display URL for a Media entity, falling back to a static
     * placeholder when no media is available.
     *
     * Base URL resolution is delegated to Media::getUrl() (cdn_url column,
     * else Config\Media::$cdnBaseUrl, else a local /uploads/ URL) — this
     * function used to check $media->cdn_url directly and fall back to the
     * bare relative filename, which skipped $cdnBaseUrl entirely and wasn't
     * a usable URL on its own. The `?w=` resize hint is only appended when
     * an actual CDN is in play; a plain local static file has no resizing
     * capability to hint at.
     *
     * @param int $width Desired display width — the exact query param name
     *                    a real CDN honors depends on which CDN is
     *                    provisioned (e.g. Bunny/Cloudflare use `width`/`w`
     *                    differently); adjust this to match once chosen.
     */
    function media_url(?Media $media, int $width = 800): string
    {
        if ($media === null) {
            return base_url('assets/placeholder-1200x630.png');
        }

        $url = $media->getUrl();

        return mediaHasCdn($media) ? $url . '?w=' . $width : $url;
    }
}

if (! function_exists('media_srcset')) {
    /**
     * Builds a `srcset` attribute value from Config\Media::$variantWidths,
     * e.g. "https://cdn.example.com/x.jpg?w=400 400w, ...?w=800 800w, ...".
     * Empty when no CDN is configured — a local static file can't be
     * resized on request, so there's nothing meaningful to offer beyond
     * the single media_url() src.
     */
    function media_srcset(?Media $media): string
    {
        if ($media === null || ! mediaHasCdn($media)) {
            return '';
        }

        $base = $media->getUrl();

        /** @var MediaConfig $config */
        $config = config(MediaConfig::class);

        $parts = [];

        foreach ($config->variantWidths as $variantWidth) {
            $parts[] = "{$base}?w={$variantWidth} {$variantWidth}w";
        }

        return implode(', ', $parts);
    }
}

if (! function_exists('mediaHasCdn')) {
    /**
     * Whether this media item resolves through an actual CDN (per-row
     * cdn_url, or the sitewide Config\Media::$cdnBaseUrl) rather than the
     * local /uploads/ fallback — used to decide whether resize query
     * params are meaningful to append.
     */
    function mediaHasCdn(Media $media): bool
    {
        /** @var MediaConfig $config */
        $config = config(MediaConfig::class);

        return ! empty($media->cdn_url) || ! empty($config->cdnBaseUrl);
    }
}

if (! function_exists('image_alt')) {
    /**
     * Raw (unescaped) alt text for a Media entity.
     *
     * NOTE: deliberately NOT htmlspecialchars-escaped here, even though
     * that's the more common convention for a helper meant to be "safe to
     * echo directly" — every existing call site in this codebase
     * (app/Views/front/_article_card.php, front/home.php,
     * front/article/show.php) already wraps this call in CodeIgniter's own
     * `esc()`, matching how media_url()/media_srcset() above are used.
     * Escaping here too would double-escape entities (e.g. "&amp;" would
     * render as "&amp;amp;"). Always call this as `esc(image_alt($media))`.
     */
    function image_alt(?Media $media): string
    {
        if ($media === null) {
            return '';
        }

        return (string) ($media->alt_text ?? '');
    }
}
