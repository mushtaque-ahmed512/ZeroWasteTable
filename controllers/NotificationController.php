<?php
class NotificationController extends Controller
{
    public function index(): void
    {
        $this->view('notifications/index', [
            'title' => 'Notifications',
            'items' => (new Notification())->forUser(user()['id']),
        ]);
    }

    public function open(string $id): void
    {
        $n = new Notification();
        $row = $n->find((int)$id, user()['id']);
        if (!$row) {
            $this->abort(404);
        }
        $n->markRead((int)$id, user()['id']);
        redirect($row['link'] ?: 'notifications');
    }

    public function readAll(): void
    {
        (new Notification())->markAllRead(user()['id']);
        redirect('notifications');
    }
}
