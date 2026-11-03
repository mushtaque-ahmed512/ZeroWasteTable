<?php
class AdminController extends Controller
{
    public function users(): void
    {
        $role = get('role');
        $this->view('admin/users', [
            'title' => 'Users & organizations',
            'rows'  => (new User())->listAll(in_array($role, ['donor', 'ngo', 'volunteer', 'admin'], true) ? $role : '', get('q')),
            'role'  => $role,
            'q'     => get('q'),
        ]);
    }

    public function userStatus(string $id): void
    {
        $users = new User();
        $target = $users->find((int)$id);
        if (!$target) $this->abort(404);
        if ((int)$id === user()['id']) {
            flash('warning', 'You cannot suspend your own account.');
            redirect('admin/users');
        }
        $new = post('status') === 'suspended' ? 'suspended' : 'active';
        $users->setStatus((int)$id, $new);
        flash('success', $target['name'] . ' is now ' . $new . '.');
        redirect('admin/users');
    }

    public function verifyOrg(string $id): void
    {
        $users = new User();
        $org = $users->organization((int)$id);
        if (!$org) $this->abort(404);
        $verify = post('verified') === '1';
        $users->setVerified((int)$id, $verify);
        if ($verify) {
            (new Notification())->add((int)$org['user_id'], 'info', 'Organization verified',
                'Your organization has been verified. You now have full access.', 'dashboard');
        }
        flash('success', $org['org_name'] . ($verify ? ' verified.' : ' marked as unverified.'));
        redirect('admin/users');
    }

    public function categories(): void
    {
        $this->view('admin/categories', ['title' => 'Food categories', 'rows' => (new Category())->withCounts()]);
    }

    public function categoryStore(): void
    {
        $name = post('name');
        if ($name === '' || mb_strlen($name) > 80) {
            $this->fail('admin/categories', ['Category name is required (max 80 characters).']);
        }
        try {
            (new Category())->create($name, post('description'));
            flash('success', 'Category added.');
        } catch (PDOException $e) {
            flash('danger', 'A category with that name already exists.');
        }
        redirect('admin/categories');
    }

    public function categoryDelete(string $id): void
    {
        try {
            (new Category())->delete((int)$id);
            flash('success', 'Category deleted.');
        } catch (PDOException $e) {
            flash('warning', 'That category is used by existing donations and cannot be deleted.');
        }
        redirect('admin/categories');
    }
}
