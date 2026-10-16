# Лабораторная работа 7. Отель (вариант 5)

Веб-приложение на PHP 8 и MySQL/MariaDB: каталог номеров, бронирование, личный кабинет клиента и админ-панель.
Доступ к базе – через PDO, защита от SQL-инъекций, XSS и CSRF, интерфейс на Bootstrap 5.

## Быстрый запуск

Нужны PHP 8.1+ (расширения pdo_mysql, mbstring) и MySQL или MariaDB.

```
cd lab7
php bin/install.php         # спросит логин и пароль MySQL, создаст базу hotel, таблицы и тестовые данные, сохранит .env
php -S localhost:8000 -t public public/index.php
```

Если у MySQL есть пароль (например, логин `root` и свой пароль), его можно ввести на вопрос скрипта, передать в команде
(`php bin/install.php --user=root --password=ваш_пароль`) или вписать вручную в файл `.env` (скопируйте `.env.example`):
`DB_USER=root`, `DB_PASS=ваш_пароль`. Если в пароле есть пробелы или `#`, возьмите его в кавычки: `DB_PASS='ваш пароль'`.

Откройте http://localhost:8000/. Под Apache/XAMPP положите папку в `htdocs` и откройте `http://localhost/lab7/public/`
(`public/.htaccess` направляет все адреса в `index.php`, нужен включённый mod_rewrite).

| Роль | Email | Пароль |
|---|---|---|
| Администратор | admin@hotel.by | Admin123 |
| Клиент | aleksandr.ivanov@example.by (и ещё 10 клиентов, см. таблицу users) | Client123 |

`php bin/install.php --fresh` пересоздаёт таблицы с нуля. Тесты: `php tests/run.php` (работают только с отдельной базой `hotel_test` и не трогают рабочие данные).

## Сценарии (Use Case) варианта «Отель»

| № | Сценарий | Где |
|---|---|---|
| 1 | Регистрация клиента | `/register` |
| 2 | Просмотр доступных номеров | `/rooms` (с датами показываются только свободные номера) |
| 3 | Бронирование номера | `/rooms/{id}` – форма бронирования, расчёт стоимости |
| 4 | Отмена брони | `/bookings` – кнопка «Отменить» (до дня заезда), оплата помечается возвращённой |
| 5 | История бронирований | `/bookings` с постраничным выводом |
| 6 | Админ-панель: управление номерами, статистика | `/admin` (статистика), `/admin/rooms` (CRUD), `/admin/bookings`, `/admin/users` |
| 7 | Фильтрация номеров по классу | `/rooms?class=suite` (также по датам, вместимости, цене) |

## База данных

`database/schema.sql`. Связи: `users` 1—N `bookings`, `rooms` 1—N `bookings`, `bookings` 1—1 `payments`.

```
users(id, name, email*, password_hash, role, created_at)
rooms(id, number*, class, price, capacity, description, is_active)
bookings(id, user_id→users, room_id→rooms, date_from, date_to, guests, status, created_at)
payments(id, booking_id*→bookings, amount, paid_at, method, status)
```

Тестовые данные: 12 пользователей, 60 номеров и около 300 бронирований с оплатами (даты считаются от текущего дня).

## Архитектура

```
public/index.php          единственная точка входа, assets/ (Bootstrap, css, js)
config/                   app.php, database.php, routes.php (таблица маршрутов)
app/Core/                 Application, Router, Request, Response, View, Session, Csrf, Auth, Validator, Paginator, Database
app/Controllers/          клиентская часть и Admin/ (только тонкие контроллеры)
app/Services/             BookingService – правила бронирования и транзакции
app/Repositories/         единственное место с SQL: UserRepository, RoomRepository, BookingRepository, PaymentRepository
app/Views/                шаблоны (layouts, partials, страницы)
app/Database/Installer    создание таблиц и тестовые данные
bin/install.php           установка БД,  tests/run.php – автоматические проверки
```

Запрос проходит так: `index.php` → `Application::run()` → `Router` находит контроллер → проверка доступа (`guest/auth/admin`) и CSRF →
контроллер вызывает сервис или репозиторий → шаблон. Классы подключаются автозагрузчиком PSR-4 (`Hotel\Core\Router` → `app/Core/Router.php`).

## Защита (задачи 3–5 методички)

- **SQL-инъекции.** Все значения передаются только параметрами подготовленных запросов PDO (`ATTR_EMULATE_PREPARES = false`, режим исключений).
  Порядок сортировки выбирается из белого списка, спецсимволы `%` и `_` в поиске экранируются, `LIMIT/OFFSET` привязываются как числа.
- **XSS.** Любой вывод идёт через `e()` (`htmlspecialchars`), пользовательская разметка не вставляется; дополнительно заголовок
  `Content-Security-Policy` разрешает скрипты и стили только с самого сайта.
- **CSRF.** Токен в сессии (`random_bytes`), скрытое поле в каждой форме, проверка `hash_equals` для всех POST-запросов (иначе страница 419),
  изменяющие действия выполняются только методом POST; кука сессии с флагами `HttpOnly` и `SameSite=Lax`.
- **Пароли и вход.** `password_hash` / `password_verify`, смена идентификатора сессии после входа, одинаковое сообщение
  при неверном email и пароле, блокировка входа после 5 неудачных попыток на 5 минут.
- **Права доступа.** Админ-страницы закрыты проверкой роли на сервере; клиент видит и отменяет только свои брони;
  нельзя удалить самого себя или единственного администратора.
- **Целостность данных.** Бронь и оплата создаются в одной транзакции, строка номера блокируется (`FOR UPDATE`),
  поэтому двойное бронирование одних дат невозможно; внешние ключи и CHECK-ограничения в схеме.
- **Ошибки.** Подробности исключений пишутся в `storage/logs/app.log`, пользователю показывается общая страница (подробности – только при `APP_DEBUG=true`).

## Тестирование

`php tests/run.php` – 59 проверок: валидация, маршрутизатор, расчёты, а на отдельной базе `hotel_test` – бронирование,
пересечения дат, отмена, права, каскадное удаление и попытки SQL-инъекций.
