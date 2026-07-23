<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\PageModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PageController extends BaseController
{
    public function show(string $slug)
    {
        $page = model(PageModel::class)->findPublishedBySlug($slug);

        if ($page === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('front/pages/static', [
            'page'            => $page,
            'pageTitle'       => $page->title,
            'metaDescription' => $page->meta_description,
            'canonicalUrl'    => site_url($page->slug),
        ]);
    }
}
