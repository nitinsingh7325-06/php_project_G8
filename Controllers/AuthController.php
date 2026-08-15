<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Services\StorageService;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirectAfterLogin(Session::user());
        }
        $this->view('auth/login', ['title' => 'Login'], 'auth');
    }

    public function registerForm(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('dashboard');
        }
        $this->view('auth/register', ['title' => 'Register'], 'auth');
    }

    public function login(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 422);
        }

        $login = trim((string) $this->input('login'));
        $password = (string) $this->input('password');

        if ($login === '' || $password === '') {
            $this->json(['success' => false, 'message' => 'Email/phone and password are required.'], 422);
        }

        $user = null;
        if (str_contains($login, '@')) {
            $user = User::findByEmail($login);
        } else {
            $user = User::findByPhone(format_phone($login));
            if (!$user) {
                $user = User::findByEmail($login);
            }
        }

        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            $this->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        if (!(int) ($user['is_active'] ?? 1)) {
            $this->json(['success' => false, 'message' => 'Account is inactive.'], 403);
        }

        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        Session::set('user', User::safe($user));

        $redirect = 'dashboard';
        if ($user['role'] === 'admin') {
            $redirect = 'admin';
        } elseif ($user['role'] === 'staff') {
            $redirect = 'staff/dashboard';
        }

        $this->json([
            'success' => true,
            'message' => 'Welcome back!',
            'redirect' => url($redirect),
            'user' => User::safe($user),
        ]);
    }

    public function register(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 422);
        }

        [$data, $errors] = $this->validate([
            'name' => 'required|min:2',
            'phone' => 'required|phone',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors, 'message' => 'Please fix the form errors.'], 422);
        }

        $phone = format_phone($data['phone']);
        if (User::findByPhone($phone)) {
            $this->json(['success' => false, 'message' => 'Phone already registered.'], 409);
        }
        if (User::findByEmail($data['email'])) {
            $this->json(['success' => false, 'message' => 'Email already registered.'], 409);
        }

        $id = User::createCustomer([
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'],
            'password' => $data['password'],
            'phone_verified_at' => date('Y-m-d H:i:s'),
        ]);

        $user = User::find($id);
        Session::set('user', User::safe($user));

        $this->json([
            'success' => true,
            'message' => 'Account created successfully!',
            'redirect' => url('dashboard'),
            'user' => User::safe($user),
        ]);
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/');
    }

    public function profile(): void
    {
        $user = Session::user();
        $fresh = User::find((int) $user['id']);
        $this->view('customer/profile', [
            'title' => 'My Profile',
            'user' => $fresh,
            'tier' => loyalty_tier((int) ($fresh['loyalty_points'] ?? 0)),
        ], 'dashboard');
    }

    public function updateProfile(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $user = Session::user();
        $name = trim((string) $this->input('name'));
        $email = trim((string) $this->input('email'));
        $password = (string) $this->input('password');
        $newPhone = trim((string) $this->input('phone'));

        $data = ['name' => $name, 'email' => $email ?: null];
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if ($newPhone !== '') {
            $formatted = format_phone($newPhone);
            $existing = User::findByPhone($formatted);
            if ($existing && (int) $existing['id'] !== (int) $user['id']) {
                $this->json(['success' => false, 'message' => 'Phone number already in use.'], 409);
            }
            $data['phone'] = $formatted;
        }

        if (!empty($_FILES['avatar']['name'])) {
            $path = (new StorageService())->storeProfileImage($_FILES['avatar'], (int) $user['id']);
            if ($path) {
                $data['avatar'] = $path;
            }
        }

        User::update((int) $user['id'], $data);
        Session::set('user', User::safe(User::find((int) $user['id'])));
        $this->json(['success' => true, 'message' => 'Profile updated successfully.']);
    }

    private function redirectAfterLogin(?array $user): void
    {
        if ($user) {
            if ($user['role'] === 'admin') {
                $this->redirect('admin');
            } elseif ($user['role'] === 'staff') {
                $this->redirect('staff/dashboard');
            }
        }
        $this->redirect('dashboard');
    }
}
