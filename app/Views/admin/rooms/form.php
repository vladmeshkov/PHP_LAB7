<?php
/**
 * Форма создания и редактирования номера.
 * @var ?array $room  null при создании
 * @var array $classes
 * @var array $errors
 */
$isEdit = $room !== null;
$value = static fn (string $key, mixed $default = ''): string => old($key, $isEdit ? $room[$key] : $default);
?>
<?= $view->partial('admin_nav', ['request' => $request]) ?>

<div class="row justify-content-center"><div class="col-lg-7">
    <div class="card shadow-sm"><div class="card-body p-4">
        <h1 class="h4 mb-4"><?= $isEdit ? 'Изменение номера ' . e($room['number']) : 'Новый номер' ?></h1>

        <form method="post" action="<?= e(url($isEdit ? 'admin/rooms/' . $room['id'] : 'admin/rooms')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label" for="number">Номер</label>
                    <input class="form-control<?= isset($errors['number']) ? ' is-invalid' : '' ?>" id="number" name="number" maxlength="10" value="<?= $value('number') ?>" required>
                    <?php if (isset($errors['number'])): ?><div class="invalid-feedback"><?= e($errors['number']) ?></div><?php endif; ?>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="class">Класс</label>
                    <select class="form-select<?= isset($errors['class']) ? ' is-invalid' : '' ?>" id="class" name="class" required>
                        <?php foreach ($classes as $code => $label): ?>
                            <option value="<?= e($code) ?>"<?= $value('class', 'standard') === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['class'])): ?><div class="invalid-feedback"><?= e($errors['class']) ?></div><?php endif; ?>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="price">Цена за сутки, BYN</label>
                    <input class="form-control<?= isset($errors['price']) ? ' is-invalid' : '' ?>" id="price" name="price" inputmode="decimal" value="<?= $value('price') ?>" required>
                    <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= e($errors['price']) ?></div><?php endif; ?>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="capacity">Вместимость</label>
                    <input class="form-control<?= isset($errors['capacity']) ? ' is-invalid' : '' ?>" id="capacity" name="capacity" type="number" min="1" max="10" value="<?= $value('capacity', '2') ?>" required>
                    <?php if (isset($errors['capacity'])): ?><div class="invalid-feedback"><?= e($errors['capacity']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Описание</label>
                    <textarea class="form-control<?= isset($errors['description']) ? ' is-invalid' : '' ?>" id="description" name="description" rows="4" maxlength="1000"><?= $value('description') ?></textarea>
                    <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"<?= ($isEdit ? (int) $room['is_active'] === 1 : true) ? ' checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Доступен для бронирования</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Сохранить' : 'Добавить' ?></button>
                <a class="btn btn-outline-secondary" href="<?= e(url('admin/rooms')) ?>">Отмена</a>
            </div>
        </form>
    </div></div>
</div></div>
