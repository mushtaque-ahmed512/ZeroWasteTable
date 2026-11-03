<?php
/** Pages only for visitors who are not logged in (login, register). */
class GuestMiddleware
{
    public function handle(array $args): void
    {
        if (user()) {
            redirect('dashboard');
        }
    }
}
