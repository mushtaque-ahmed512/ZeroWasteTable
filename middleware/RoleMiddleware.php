<?php
/** role:donor,ngo - allows only the listed roles. */
class RoleMiddleware
{
    public function handle(array $roles): void
    {
        if (!has_role(...$roles)) {
            (new class extends Controller {
                public function deny(): void { $this->abort(403, 'Your role does not have access to this page.'); }
            })->deny();
        }
    }
}
