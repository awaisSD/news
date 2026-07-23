<?php
/**
 * Only ever included when $corrections is non-empty — see article/show.php.
 *
 * @var \App\Entities\ArticleCorrection[] $corrections newest-first
 */
?>
<aside class="corrections-box" aria-label="Corrections">
    <h2 class="corrections-box__title">Corrections</h2>
    <ul class="corrections-box__list">
        <?php foreach ($corrections as $correction): ?>
            <li class="corrections-box__item corrections-box__item--<?= esc($correction->severity ?? 'minor', 'attr') ?>">
                <?php if (! empty($correction->created_at)): ?>
                    <time datetime="<?= esc($correction->created_at->format(DATE_ATOM), 'attr') ?>"><?= esc($correction->created_at->format('M j, Y')) ?></time>
                    &mdash;
                <?php endif; ?>
                <?= esc($correction->correction_note) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>
