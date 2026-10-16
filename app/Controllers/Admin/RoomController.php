<?php
declare(strict_types=1);

namespace Hotel\Controllers\Admin;

use Hotel\Core\Controller;
use Hotel\Core\HttpException;
use Hotel\Core\Paginator;
use Hotel\Core\Request;
use Hotel\Core\Response;
use Hotel\Core\Validator;
use Hotel\Repositories\RoomRepository;

/** Управление номерами: список с фильтрами и постраничным выводом, создание, изменение, удаление. */
final class RoomController extends Controller
{
    private const LABELS = ['number' => 'Номер', 'class' => 'Класс', 'price' => 'Цена за сутки', 'capacity' => 'Вместимость', 'description' => 'Описание'];

    public function index(Request $request): Response
    {
        $class = $request->query('class');
        $filters = ['search' => $request->query('q')];
        if (isset(RoomRepository::CLASSES[$class])) {
            $filters['class'] = $class;
        } else {
            $class = '';
        }

        $rooms = $this->app->rooms();
        $paginator = new Paginator($rooms->count($filters), (int) config('per_page.admin'), (int) $request->query('page', '1'));

        return $this->view('admin/rooms/index', [
            'rooms'     => $rooms->page($filters, $request->query('sort', 'number'), $paginator->perPage, $paginator->offset()),
            'paginator' => $paginator,
            'filters'   => ['q' => $request->query('q'), 'class' => $class, 'sort' => $request->query('sort', 'number')],
            'classes'   => RoomRepository::CLASSES,
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin/rooms/form', ['room' => null, 'classes' => RoomRepository::CLASSES]);
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validate($data);
        if ($errors === [] && $this->app->rooms()->findByNumber($data['number']) !== null) {
            $errors['number'] = 'Номер с таким обозначением уже существует';
        }
        if ($errors !== []) {
            return $this->backWithErrors('admin/rooms/create', $request, $errors);
        }

        $this->app->rooms()->create($data);
        $this->flash('success', "Номер {$data['number']} добавлен");

        return $this->redirect('admin/rooms');
    }

    public function edit(Request $request, int $id): Response
    {
        return $this->view('admin/rooms/form', ['room' => $this->room($id), 'classes' => RoomRepository::CLASSES]);
    }

    public function update(Request $request, int $id): Response
    {
        $this->room($id);
        $data = $request->all();
        $errors = $this->validate($data);

        $same = $errors === [] ? $this->app->rooms()->findByNumber($data['number']) : null;
        if ($same !== null && (int) $same['id'] !== $id) {
            $errors['number'] = 'Номер с таким обозначением уже существует';
        }
        if ($errors !== []) {
            return $this->backWithErrors("admin/rooms/{$id}/edit", $request, $errors);
        }

        $this->app->rooms()->update($id, $data);
        $this->flash('success', "Номер {$data['number']} обновлён");

        return $this->redirect('admin/rooms');
    }

    public function destroy(Request $request, int $id): Response
    {
        $room = $this->room($id);

        // История бронирований важнее: такой номер нельзя удалить, но можно снять с продажи (флажок «активен»).
        if ($this->app->bookings()->hasAnyForRoom($id)) {
            $this->flash('danger', "Номер {$room['number']} нельзя удалить: по нему есть бронирования. Снимите флажок «Доступен для бронирования».");
        } else {
            $this->app->rooms()->delete($id);
            $this->flash('success', "Номер {$room['number']} удалён");
        }

        return $this->redirect('admin/rooms');
    }

    private function room(int $id): array
    {
        return $this->app->rooms()->find($id) ?? throw new HttpException(404);
    }

    private function validate(array $data): array
    {
        return Validator::make($data, [
            'number'      => 'required|max:10',
            'class'       => 'required|in:' . implode(',', array_keys(RoomRepository::CLASSES)),
            'price'       => 'required|decimal|min_value:1',
            'capacity'    => 'required|between:1,10',
            'description' => 'max:1000',
        ], self::LABELS)->errors();
    }
}
