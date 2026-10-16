<?php
declare(strict_types=1);

namespace Hotel\Core;

/** Базовый класс контроллеров: общие помощники для ответов. */
abstract class Controller
{
    public function __construct(protected Application $app)
    {
    }

    /** Страница из шаблона. Общие данные (пользователь, сообщения, ошибки формы) добавляются автоматически. */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $session = $this->app->session();
        $common = [
            'user'    => $this->app->auth()->user(),
            'flashes' => $session->pullFlash(),
            'errors'  => $session->errors(),
            'request' => $this->app->request(),
            'view'    => $this->app->view(),
        ];

        $html = $this->app->view()->render($template, $data + $common);
        $session->clearTransient();

        return new Response($html, $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    protected function flash(string $type, string $message): void
    {
        $this->app->session()->flash($type, $message);
    }

    /** Возврат к форме с ошибками и введёнными значениями. */
    protected function backWithErrors(string $path, Request $request, array $errors): Response
    {
        $this->app->session()->flashInput($request->all());
        $this->app->session()->flashErrors($errors);

        return $this->redirect($path);
    }
}
