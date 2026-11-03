<?php
/**
 * Base controller: view rendering, redirects, JSON and error pages.
 */
abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $title = $title ?? APP_NAME;
        $libs  = $libs ?? [];
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . "/views/{$view}.php";
        require ROOT_PATH . '/includes/footer.php';
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function abort(int $code, string $message = ''): void
    {
        $titles = [403 => 'Access denied', 404 => 'Page not found', 419 => 'Session expired', 500 => 'Server error'];
        http_response_code($code);
        $this->view('errors/error', [
            'title'   => $titles[$code] ?? 'Error',
            'code'    => $code,
            'heading' => $titles[$code] ?? 'Error',
            'message' => $message,
        ]);
        exit;
    }

    /** Flash validation errors, keep the typed values, go back. */
    protected function fail(string $path, array $errors): void
    {
        foreach ($errors as $err) {
            flash('danger', $err);
        }
        $_SESSION['old'] = $_POST;
        redirect($path);
    }
}
