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
     * @param int $width Desired display width. Only used to build a `?w=`
     *                    query hint when the media has a `cdn_url` — the
     *                    exact query param name a real CDN honors depends
     *                    on which CDN is provisioned (e.g. Bunny/Cloudflare
     *                    use `width`/`w` differently); adjust this to match
     *                    once a CDN is chosen.
     */
    function media_url(?Media $media, int $width = 800): string
    {
        if ($media === null) {
            return '/assets/placeholder-1200x630.png';
        }

        if (! empty($media->cdn_url)) {
            return $media->cdn_url . '?w=' . $width;
        }

        return (string) $media->path;
    }
}

if (! function_exists('media_srcset')) {
    /**
     * Builds a `srcset` attribute value from Config\Media::$variantWidths,
     * e.g. "https://cdn.example.com/x.jpg?w=400 400w, ...?w=800 800w, ...".
     */
    function media_srcset(?Media $media): string
    {
        if ($media === null) {
            return '';
        }

        $base = ! empty($media->cdn_url) ? $media->cdn_url : (string) $media->path;

        /** @var MediaConfig $config */
        $config = config(MediaConfig::class);

        $parts = [];

        foreach ($config->variantWidths as $variantWidth) {
            $parts[] = "{$base}?w={$variantWidth} {$variantWidth}w";
        }

        return implode(', ', $parts);
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
