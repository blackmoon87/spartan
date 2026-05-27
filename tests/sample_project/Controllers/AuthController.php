<?php

declare(strict_types=1);

namespace Tests\Sample\Controllers;

use App\Core\Controller;
use App\Core\Request;
use Tests\Sample\Models\User;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function login(): string
    {
        // Redirect to home if already logged in
        if ($this->session->get('user_id')) {
            $this->response->redirect('/');
            return '';
        }

        return $this->render('auth/login', [
            'errors' => $this->session->getFlash('validation_errors', []),
            'old' => $this->session->getFlash('old_input', []),
        ]);
    }

    /**
     * Handle the login request.
     */
    public function postLogin(Request $request): void
    {
        $email = trim((string)($request->getBody()['email'] ?? ''));
        $password = (string)($request->getBody()['password'] ?? '');

        $errors = [];
        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        }
        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }

        if (!empty($errors)) {
            $this->session->setFlash('validation_errors', $errors);
            $this->session->setFlash('old_input', ['email' => $email]);
            $this->response->redirect('/login');
            return;
        }

        $userModel = new User();
        $user = $userModel->table()->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['email'] = 'Invalid email or password.';
            $this->session->setFlash('validation_errors', $errors);
            $this->session->setFlash('old_input', ['email' => $email]);
            $this->response->redirect('/login');
            return;
        }

        // Login success
        $this->session->regenerate();
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_name', $user['name']);

        $this->response->redirect('/');
    }

    /**
     * Show the registration form.
     */
    public function register(): string
    {
        // Redirect to home if already logged in
        if ($this->session->get('user_id')) {
            $this->response->redirect('/');
            return '';
        }

        return $this->render('auth/register', [
            'errors' => $this->session->getFlash('validation_errors', []),
            'old' => $this->session->getFlash('old_input', []),
        ]);
    }

    /**
     * Handle the registration request.
     */
    public function postRegister(Request $request): void
    {
        $name = trim((string)($request->getBody()['name'] ?? ''));
        $email = trim((string)($request->getBody()['email'] ?? ''));
        $password = (string)($request->getBody()['password'] ?? '');

        $errors = [];
        if (empty($name)) {
            $errors['name'] = 'Full name is required.';
        }
        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if (!empty($errors)) {
            $this->session->setFlash('validation_errors', $errors);
            $this->session->setFlash('old_input', ['name' => $name, 'email' => $email]);
            $this->response->redirect('/register');
            return;
        }

        $userModel = new User();
        $existing = $userModel->table()->where('email', $email)->first();

        if ($existing) {
            $errors['email'] = 'Email address is already registered.';
            $this->session->setFlash('validation_errors', $errors);
            $this->session->setFlash('old_input', ['name' => $name, 'email' => $email]);
            $this->response->redirect('/register');
            return;
        }

        // Create user
        $now = date('Y-m-d H:i:s');
        $userId = $userModel->table()->insert([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Log user in
        $this->session->regenerate();
        $this->session->set('user_id', $userId);
        $this->session->set('user_name', $name);

        $this->response->redirect('/');
    }

    /**
     * Handle the logout request.
     */
    public function logout(): void
    {
        $this->session->destroy();
        $this->response->redirect('/');
    }
}
