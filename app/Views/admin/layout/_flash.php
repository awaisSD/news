<?php
/**
 * Reads CI4 flashdata set via ->with('error', ...) / ->with('success', ...)
 * on any redirect()->to(...) response. Included from layout/main.php above
 * the yielded content section so every admin page gets it for free.
 */
?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="flash flash-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="flash flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
