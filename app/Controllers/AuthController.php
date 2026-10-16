<?php
declare(strict_types=1);

namespace Hotel\Controllers;

use Hotel\Core\Controller;
use Hotel\Core\Request;
use Hotel\Core\Response;
use Hotel\Core\Validator;

/** Регистрация, вход и выход. */
final class AuthController extends Controller
{
    private const LABELS = ['name' => 'Имя', 'email' => 'Email', 'password' => 'Пароль'];

    public function registerForm(): Response
    {
        return $this->view('auth/register');
    }

    public function register(Request $request): Response
    {
        $data = $request->all();
        $v = Validator::make($data, [
            'name'     => 'required|min:2|max:100|name',
            'email'    => 'required|email|max:150',
            'password' => 'required|min:8|max:72|password|confirmed',
        ], self::LABELS);

        if (!$v->fails() && $this->app->users()->findByEmail($data['email']) !== null) {
            $v->addError('email', 'Пользователь с таким email уже зарегистрирован');
        }
        if ($v->fails()) {
            return $this->backWithErrors('register', $request, $v->errors());
        }

        $id = $this->app->users()->create($data['name'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT));
        $this->app->auth()->login($this->app->users()->find($id));
        $this->flash('success', 'Регистрация прошла успешно. Добро пожаловать!');

        return $this->redirect('rooms');
    }

    public function loginForm(): Response
    {
        return $this->view('auth/login');
    }

    public function login(Request $request): Response
    {
        $auth = $this->app->auth();

        if (($wait = $auth->lockedFor()) > 0) {
            return $this->backWithErrors('login', $request, ['form' => 'Слишком много неудачных попыток. Повторите через ' . ceil($wait / 60) . ' мин.']);
        }

        if ($auth->attempt($request->input('email'), $request->raw('password'))) {
            $auth->clearFailures();
            $this->flash('success', 'Вы вошли в систему');

            return $this->redirect($auth->isAdmin() ? 'admin' : 'rooms');
        }

        $auth->registerFailure();

        return $this->backWithErrors('login', $request, ['form' => 'Неверный email или пароль']);
    }

    public function logout(): Response
    {
        $this->app->auth()->logout();
        // Сессия уничтожена, поэтому сообщение кладём в новую.
        $this->app->session()->start();
        $this->flash('success', 'Вы вышли из системы');

        return $this->redirect('login');
    }
}
