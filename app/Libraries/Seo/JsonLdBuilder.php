<?php

namespace App\Libraries\Seo;

use App\Entities\Article;
use App\Entities\Category;
use App\Entities\Media;
use App\Entities\User;
use Config\SiteIdentity;

/**
 * Pure, stateless schema.org JSON-LD array builders.
 *
 * Deliberately has zero DB access — every method takes fully-hydrated
 * entities/objects as explicit parameters rather than lazy-loading related
 * records itself. This keeps the class trivially unit-testable (no DB mocks
 * needed) and keeps the responsibility for fetching related entities (author,
 * category, featured media, etc.) where it belongs: in the controller that
 * already has them in hand for rendering the page.
 */
class JsonLdBuilder
{
    /**
     * Builds the schema.org Organization node used both standalone (footer,
     * about page) and nested as `publisher` inside forArticle().
     */
    public function forOrganization(): array
    {
        /** @var SiteIdentity $pub */
        $pub = config(SiteIdentity::class);

        $data = [
            '@type'     => 'Organization',
            'name'      => $pub->name,
            'legalName' => $pub->legalName,
            'url'       => $pub->url,
            'logo'      => [
                '@type'  => 'ImageObject',
                'url'    => $pub->logoUrl,
                'width'  => $pub->logoWidth,
                'height' => $pub->logoHeight,
            ],
        ];

        if (! empty($pub->sameAs)) {
            $data['sameAs'] = $pub->sameAs;
        }

        return $data;
    }

    /**
     * Builds a full NewsArticle JSON-LD graph for an article detail page.
     *
     * Explicit params (rather than the Article entity lazily loading its own
     * category/author/media relations) so this class stays a pure function
     * of its inputs.
     */
    public function forArticle(
        Article $article,
        Category $category,
        User $author,
        ?Media $featuredMedia
    ): array {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'NewsArticle',
            // Google's practical headline length guidance.
            'headline' => mb_substr((string) $article->headline, 0, 110),
            'image'    => $featuredMedia !== null ? [
                '@type'  => 'ImageObject',
                'url'    => $featuredMedia->getUrl(),
                'width'  => $featuredMedia->width,
                'height' => $featuredMedia->height,
            ] : null,
            'datePublished' => $this->formatDate($article->published_at),
            'dateModified'  => $this->formatDate($article->updated_at_content ?? $article->published_at),
            'author'        => [
                '@type' => 'Person',
                'name'  => $author->name,
                // TODO: App\Entities\User has no slug-like field in the
                // finalized schema (checked: id, uuid, name, email,
                // password_hash, role, bio, credentials, avatar_media_id,
                // twitter_handle, linkedin_url, is_active, last_login_at,
                // created_at, updated_at). The author route is registered as
                // `author/(:segment)` in Config/Routes.php, which accepts any
                // string segment, so link by numeric id for now. Add a
                // friendly author slug (e.g. derived from name) to the users
                // table in a future migration and switch this to use it.
                'url' => site_url('author/' . $author->id),
            ],
            'publisher'       => $this->forOrganization(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => site_url($category->slug . '/' . $article->slug),
            ],
            'description' => $article->excerpt,
        ];

        return array_filter($data, static fn ($value) => $value !== null);
    }

    /**
     * @param array<int, array{name: string, url: string}> $items list of
     *                                                             {name, url} from home to current page, in order
     */
    public function forBreadcrumb(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn ($item, $i) => [
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'name'     => $item['name'],
                    'item'     => $item['url'],
                ],
                $items,
                array_keys($items)
            ),
        ];
    }

    /**
     * Article entity casts published_at/updated_at_content to a
     * CodeIgniter\I18n\Time (a DateTime subclass) via its ?datetime cast, but
     * we format defensively in case a caller passes a raw string through.
     *
     * @param mixed $value
     */
    private function formatDate($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value) && method_exists($value, 'format')) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value) && $value !== '') {
            $timestamp = strtotime($value);

            return $timestamp !== false ? date(DATE_ATOM, $timestamp) : null;
        }

        return null;
    }
}
