<?php echo page_header('Verify collection', 'Scan the QR code shown by the volunteer or NGO collecting your food.'); ?>
<div class="row"><div class="col-lg-6">
    <div class="card"><div class="card-body">
        <div id="reader" class="mb-2"></div>
        <p id="scan-msg" class="text-center small text-body-secondary"></p>
        <div class="text-center mb-4"><button type="button" id="start-scan" class="btn btn-primary"><i class="bi bi-camera"></i> Scan with camera</button></div>
        <hr>
        <form method="post" action="<?= e(url('verify')) ?>" id="verify-form">
            <?= csrf_field() ?>
            <label class="form-label" for="token">Or type the code</label>
            <div class="input-group">
                <input class="form-control token" id="token" name="token" maxlength="32" value="<?= e($token) ?>" placeholder="32-character code" autocomplete="off" required>
                <button class="btn btn-outline-primary">Verify</button>
            </div>
            <div class="form-text">Camera scanning needs HTTPS or localhost and your permission.</div>
        </form>
    </div></div>
</div></div>
