<div class="container">
    <div class="auth-wrap">
        <div class="card"><div class="card-body p-4">
            <div class="text-center mb-3" style="font-size:3rem">🍛</div>
            <h1 class="h4 fw-bold mb-1 text-center">Welcome back</h1>
            <p class="text-body-secondary mb-4 text-center">Log in to manage your donations, requests and pickups.</p>
            <form method="post" action="<?= e(url('login')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between"><label class="form-label" for="password">Password</label><a class="small" href="<?= e(url('forgot-password')) ?>">Forgot password?</a></div>
                    <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button class="btn btn-primary w-100">Log in</button>
            </form>
            <p class="text-center small mt-3 mb-0">New here? <a href="<?= e(url('register')) ?>">Create an account</a></p>
        </div></div>

        <?php if (APP_ENV === 'development'): ?>
        <div class="card mt-3"><div class="card-body small">
            <div class="fw-semibold mb-2">Demo accounts <span class="text-body-secondary fw-normal">(development only)</span></div>
            <table class="table table-sm mb-0">
                <tr><td>Admin</td><td>admin@smartfood.test</td><td>Admin@123</td></tr>
                <tr><td>Donor</td><td>donor@smartfood.test</td><td rowspan="3" class="align-middle">Password@123</td></tr>
                <tr><td>NGO</td><td>ngo@smartfood.test</td></tr>
                <tr><td>Volunteer</td><td>volunteer@smartfood.test</td></tr>
            </table>
        </div></div>
        <?php endif; ?>
    </div>
</div>
