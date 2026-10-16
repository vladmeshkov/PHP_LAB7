<?php
declare(strict_types=1);

namespace Hotel\Controllers\Admin;

use Hotel\Core\Controller;
use Hotel\Core\HttpException;
use Hotel\Core\Paginator;
use Hotel\Core\Request;
use Hotel\Core\Response;

/** Управление пользователями: список с поиском, смена роли, удаление. */
final class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->query('q'), 0, 60);
        $users = $this->app->users();
        $paginator = new Paginator($users->count($search), (int) config('per_page.admin'), (int) $request->query('page', '1'));

        return $this->view('admin/users/index', [
            'users'     => $users->page($search, $paginator->perPage, $paginator->offset()),
            'paginator' => $paginator,
            'search'    => $search,
        ]);
    }

    public function role(Request $request, int $id): Response
    {
        $target = $this->target($id);
        $role = $request->input('role');

        if (!in_array($role, ['user', 'admin'], true)) {
            $this->flash('danger', 'Недопустимая роль');
        } elseif ($this->isLastAdmin($target) && $role !== 'admin') {
            $this->flash('danger', 'Нельзя снять права у единственного администратора');
        } else {
            $this->app->users()->updateRole($id, $role);
            $this->flash('success', "Роль пользователя {$target['name']} изменена");
        }

        return $this->redirect('admin/users');
    }

    public function destroy(Request $request, int $id): Response
    {
        $target = $this->target($id);

        if ($id === $this->app->auth()->id()) {
            $this->flash('danger', 'Нельзя удалить самого себя');
        } elseif ($this->isLastAdmin($target)) {
            $this->flash('danger', 'Нельзя удалить единственного администратора');
        } else {
            $this->app->users()->delete($id);
            $this->flash('success', "Пользователь {$target['name']} удалён вместе с его бронированиями");
        }

        return $this->redirect('admin/users');
    }

    private function target(int $id): array
    {
        return $this->app->users()->find($id) ?? throw new HttpException(404);
    }

    private function isLastAdmin(array $user): bool
    {
        return $user['role'] === 'admin' && $this->app->users()->countAdmins() <= 1;
    }
}
