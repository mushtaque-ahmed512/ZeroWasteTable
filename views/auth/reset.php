<div class="container">
    <div class="auth-wrap">
        <div class="card"><div class="card-body p-4">
            <h1 class="h4 mb-3">Choose a new password</h1>
            <form method="post" action="<?= e(url('reset-password')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3"><label class="form-label" for="password">New password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8" required autofocus autocomplete="new-password">
                    <div class="form-text">At least 8 characters.</div></div>
                <div class="mb-3"><label class="form-label" for="password_confirm">Confirm password</label>
                    <input class="form-control" type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password"></div>
                <button class="btn btn-primary w-100">Update password</button>
            </form>
        </div></div>
    </div>
</div>
