<?php
$publisher = config(\Config\SiteIdentity::class);

// Kept in sync by hand with the $staticPages list in app/Config/Routes.php
// and CategoryModel::RESERVED_SLUGS — these six slugs are the CMS-managed
// E-E-A-T/policy pages every route in this app treats as reserved.
$policyPages = [
    'about-us'            => 'About Us',
    'contact-us'          => 'Contact Us',
    'editorial-policy'    => 'Editorial Policy',
    'corrections-policy'  => 'Corrections Policy',
    'privacy-policy'      => 'Privacy Policy',
    'terms-conditions'    => 'Terms & Conditions',
];
?>
<footer class="site-footer">
    <nav class="site-footer__links" aria-label="Footer">
        <ul>
            <?php foreach ($policyPages as $slug => $label): ?>
                <li><a href="<?= esc(site_url($slug), 'attr') ?>"><?= esc($label) ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?= esc(site_url('feed'), 'attr') ?>">RSS</a></li>
        </ul>
    </nav>

    <!--
        Ad slot placeholder. Real integration point: paste the AdSense
        auto-ads / unit <script> snippet for this slot here (or load it
        once sitewide in _head.php and just leave the <ins class="adsbygoogle">
        unit markup in this div) once an AdSense account is approved.
    -->
    <div class="ad-slot" style="min-height:250px" aria-hidden="true"></div>

    <p class="site-footer__copyright">&copy; <?= esc((string) date('Y')) ?> <?= esc($publisher->legalName) ?>. All rights reserved.</p>
</footer>
