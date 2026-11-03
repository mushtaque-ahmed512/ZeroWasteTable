<?php
class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Log in']);
    }

    public function login(): void
    {
        $now = time();
        $tries = $_SESSION['login_fail'] ?? ['n' => 0, 't' => $now];
        if ($tries['n'] >= self::MAX_ATTEMPTS && $now - $tries['t'] < self::LOCK_SECONDS) {
            $this->fail('login', ['Too many failed attempts. Please wait a few minutes and try again.']);
        }

        $users = new User();
        $row = $users->findByEmail(post('email'));

        if (!$row || !password_verify($_POST['password'] ?? '', $row['password_hash'])) {
            $_SESSION['login_fail'] = ['n' => ($tries['n'] >= self::MAX_ATTEMPTS ? 0 : $tries['n']) + 1, 't' => $now];
            $this->fail('login', ['Incorrect email or password.']);
        }
        if ($row['status'] !== 'active') {
            $this->fail('login', ['This account has been suspended. Contact the administrator.']);
        }

        unset($_SESSION['login_fail']);
        $users->touchLogin((int)$row['id']);
        login_user($users->sessionData($row));
        flash('success', 'Welcome back, ' . $row['name'] . '.');
        redirect('dashboard');
    }

    public function showRegister(): void
    {
        $this->view('auth/register', ['title' => 'Create an account', 'libs' => ['leaflet']]);
    }

    public function register(): void
    {
        $role = post('role');
        $d = [
            'role'            => $role,
            'name'            => post('name'),
            'email'           => post('email'),
            'phone'           => post('phone'),
            'password'        => $_POST['password'] ?? '',
            'org_name'        => post('org_name'),
            'org_type'        => post('org_type'),
            'registration_no' => post('registration_no'),
            'address'         => post('address'),
            'city'            => post('city'),
            'latitude'        => post('latitude'),
            'longitude'       => post('longitude'),
            'vehicle'         => post('vehicle', 'none'),
            'service_area'    => post('service_area'),
        ];
        $users = new User();
        $err = [];

        if (!in_array($role, ['donor', 'ngo', 'volunteer'], true)) $err[] = 'Choose how you want to use SmartFood Rescue.';
        if (mb_strlen($d['name']) < 2)                             $err[] = 'Please enter your full name.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL))       $err[] = 'Enter a valid email address.';
        elseif ($users->emailExists($d['email']))                  $err[] = 'That email is already registered.';
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $d['phone']))    $err[] = 'Enter a valid phone number.';
        if (strlen($d['password']) < 8)                            $err[] = 'Password must be at least 8 characters.';
        if ($d['password'] !== ($_POST['password_confirm'] ?? ''))  $err[] = 'Passwords do not match.';

        if (in_array($role, ['donor', 'ngo'], true)) {
            $types = $role === 'donor' ? ['restaurant', 'bakery', 'hotel', 'grocery'] : ['ngo', 'charity'];
            if ($d['org_name'] === '')                       $err[] = 'Organization name is required.';
            if (!in_array($d['org_type'], $types, true))     $err[] = 'Select a valid organization type.';
            if ($d['address'] === '')                        $err[] = 'Address is required.';
            if (!valid_coords($d['latitude'], $d['longitude'])) $err[] = 'Tap your location on the map so food can be matched by distance.';
        } elseif ($role === 'volunteer' && !in_array($d['vehicle'], ['none', 'bike', 'car', 'van'], true)) {
            $err[] = 'Select a valid vehicle option.';
        }

        if ($err) {
            unset($_POST['password'], $_POST['password_confirm']);
            $this->fail('register', $err);
        }

        $id = $users->register($d);
        login_user($users->sessionData($users->find($id)));
        flash('success', 'Your account is ready.' . ($role === 'ngo' && NGO_REQUIRES_VERIFICATION ? ' An administrator needs to verify your organization before you can request food.' : ''));
        redirect('dashboard');
    }

    public function showForgot(): void
    {
        $this->view('auth/forgot', ['title' => 'Forgot password']);
    }

    public function forgot(): void
    {
        $email = post('email');
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? (new User())->findByEmail($email) : null;

        if ($user && $user['status'] === 'active') {
            $token = (new PasswordReset())->create((int)$user['id']);
            $link = absolute_url('reset-password', ['token' => $token]);
            $sent = send_mail($user['email'], APP_NAME . ' password reset',
                "Hello {$user['name']},\n\nUse this link to choose a new password (valid for 60 minutes):\n$link\n\nIf you did not ask for this, ignore this email.");
            // XAMPP can't send mail out of the box, so in development show the link on screen
            if (APP_ENV === 'development' && !$sent) {
                $_SESSION['dev_reset_link'] = $link;
            }
        }
        // same answer whether or not the account exists
        flash('info', 'If that email is registered, a reset link has been sent.');
        redirect('forgot-password');
    }

    public function showReset(): void
    {
        $token = get('token');
        if (!(new PasswordReset())->findValid($token)) {
            flash('danger', 'This reset link is invalid or has expired. Request a new one.');
            redirect('forgot-password');
        }
        $this->view('auth/reset', ['title' => 'Choose a new password', 'token' => $token]);
    }

    public function reset(): void
    {
        $token = post('token');
        $resets = new PasswordReset();
        $row = $resets->findValid($token);
        if (!$row) {
            flash('danger', 'This reset link is invalid or has expired. Request a new one.');
            redirect('forgot-password');
        }
        $pw = $_POST['password'] ?? '';
        $problem = strlen($pw) < 8 ? 'Password must be at least 8 characters.'
                 : ($pw !== ($_POST['password_confirm'] ?? '') ? 'Passwords do not match.' : '');
        if ($problem) {
            flash('danger', $problem);
            redirect('reset-password', ['token' => $token]);
        }

        (new User())->setPassword((int)$row['user_id'], $pw);
        $resets->consume((int)$row['user_id']);
        flash('success', 'Password updated. You can log in now.');
        redirect('login');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('info', 'You have been logged out.');
        redirect('login');
    }
}
