<?php echo page_header('Food categories', 'These appear in the donation form and the analytics charts.'); ?>
<div class="row g-3">
    <div class="col-lg-4"><div class="card"><div class="card-header">Add category</div><div class="card-body">
        <form method="post" action="<?= e(url('admin/categories')) ?>">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label" for="name">Name</label><input class="form-control" id="name" name="name" maxlength="80" required></div>
            <div class="mb-3"><label class="form-label" for="description">Description</label><input class="form-control" id="description" name="description" maxlength="255"></div>
            <button class="btn btn-primary w-100">Add</button>
        </form>
    </div></div></div>
    <div class="col-lg-8"><div class="card"><div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Description</th><th class="text-center">Donations</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $c): ?>
                <tr><td class="fw-semibold"><?= e($c['name']) ?></td><td class="text-body-secondary"><?= e($c['description']) ?></td><td class="text-center"><?= (int)$c['uses'] ?></td>
                    <td class="text-end"><form method="post" action="<?= e(url('admin/categories/' . $c['id'] . '/delete')) ?>" data-confirm="Delete this category?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" <?= $c['uses'] ? 'disabled title="In use"' : '' ?>>Delete</button></form></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div></div>
</div>
