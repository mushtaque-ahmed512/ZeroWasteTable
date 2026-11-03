<div class="<?= user() ? '' : 'container' ?>">
    <div class="text-center py-5 mx-auto" style="max-width:480px">
        <div class="display-3 fw-bold text-body-tertiary"><?= (int)$code ?></div>
        <h1 class="h4"><?= e($heading) ?></h1>
        <?php if ($message): ?><p class="text-body-secondary"><?= e($message) ?></p><?php endif; ?>
        <a class="btn btn-primary" href="<?= e(url(user() ? 'dashboard' : '')) ?>">Go back home</a>
    </div>
</div>
