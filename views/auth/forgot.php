<div class="container">
    <div class="auth-wrap">
        <div class="card"><div class="card-body p-4">
            <h1 class="h4 mb-1">Forgot your password?</h1>
            <p class="text-body-secondary mb-4">Enter your account email and we'll send you a link to choose a new one.</p>

            <?php if (!empty($_SESSION['dev_reset_link'])): ?>
                <div class="alert alert-warning small">
                    <strong>Development mode:</strong> XAMPP can't send email, so here is your reset link.<br>
                    <a class="alert-link text-break" href="<?= e($_SESSION['dev_reset_link']) ?>"><?= e($_SESSION['dev_reset_link']) ?></a>
                </div>
                <?php unset($_SESSION['dev_reset_link']); ?>
            <?php endif; ?>

            <form method="post" action="<?= e(url('forgot-password')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus></div>
                <button class="btn btn-primary w-100">Send reset link</button>
            </form>
            <p class="text-center small mt-3 mb-0"><a href="<?= e(url('login')) ?>">Back to log in</a></p>
        </div></div>
    </div>
</div>
