<?php echo page_header('Schedule pickup', 'Choose when ' . $req['ngo_name'] . ' (or a volunteer) can collect the food.'); ?>
<div class="row">
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <div class="mb-3">
                <div class="fw-semibold"><?= e($req['title']) ?></div>
                <div class="small text-body-secondary">For <?= e($req['ngo_name']) ?> · <?= e(qty((float)$req['quantity_needed'], $req['unit'])) ?> · expires <?= e(fmt_dt($req['expiry_at'])) ?> <?= expiry_badge($req['expiry_at']) ?></div>
            </div>
            <form method="post" action="<?= e(url('requests/' . $req['id'] . '/schedule')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="pickup_at">Pickup date & time</label>
                    <input class="form-control" id="pickup_at" name="pickup_at" type="datetime-local" data-future value="<?= e(old('pickup_at')) ?>" required>
                    <div class="form-text">Must be before the food expires.</div></div>
                <div class="mb-3"><label class="form-label" for="notes">Instructions <span class="text-body-secondary">(optional)</span></label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="500" placeholder="Which door, who to ask for, what to bring"><?= e(old('notes')) ?></textarea></div>
                <div class="d-flex gap-2"><button class="btn btn-primary">Confirm schedule</button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('requests')) ?>">Later</a></div>
            </form>
        </div></div>
    </div>
</div>
