<?php
/** Requires a logged-in, active user. Refreshes session data so admin changes apply immediately. */
class AuthMiddleware
{
    public function handle(array $args): void
    {
        $sessionUser = user();
        if (!$sessionUser) {
            flash('warning', 'Please log in to continue.');
            redirect('login');
        }
        $users = new User();
        $row = $users->find($sessionUser['id']);
        if (!$row || $row['status'] !== 'active') {
            $_SESSION = [];
            session_regenerate_id(true);
            flash('danger', 'Your account is not active. Contact the administrator.');
            redirect('login');
        }
        $_SESSION['user'] = $users->sessionData($row);
    }
}
