<?php
class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home', [
            'title' => APP_NAME . ' - turn surplus food into meals',
            'stats' => (new Analytics())->publicStats(),
        ]);
    }
}
