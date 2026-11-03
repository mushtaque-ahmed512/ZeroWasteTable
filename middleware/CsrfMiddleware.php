<?php
/** Applied automatically by the router to every POST request. */
class CsrfMiddleware
{
    public function handle(array $args): void
    {
        if (!csrf_valid()) {
            (new class extends Controller {
                public function fail419(): void { $this->abort(419, 'Your form expired. Go back, refresh the page and try again.'); }
            })->fail419();
        }
    }
}
