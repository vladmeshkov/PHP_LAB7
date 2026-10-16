// Небольшие улучшения интерфейса. Вся важная логика (проверки, расчёт цены, права) выполняется на сервере.
(function () {
    'use strict';

    // Подтверждение опасных действий: <form data-confirm="Текст вопроса">
    document.addEventListener('submit', function (event) {
        var message = event.target.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    // Предварительный расчёт стоимости на странице номера
    var form = document.getElementById('booking-form');
    if (!form) {
        return;
    }
    var price = parseFloat(form.getAttribute('data-price')) || 0;
    var from = document.getElementById('date_from');
    var to = document.getElementById('date_to');
    var nights = document.getElementById('nights');
    var total = document.getElementById('total');

    function recalc() {
        var a = Date.parse(from.value);
        var b = Date.parse(to.value);
        var n = Math.round((b - a) / 86400000);

        if (isNaN(n) || n < 1) {
            nights.textContent = '–';
            total.textContent = '–';
            return;
        }
        nights.textContent = n;
        total.textContent = (n * price).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' BYN';
    }

    from.addEventListener('change', function () {
        // Дата выезда не может быть раньше заезда
        if (from.value) { to.min = from.value; }
        recalc();
    });
    to.addEventListener('change', recalc);
    recalc();
}());
