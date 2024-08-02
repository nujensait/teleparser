/**
 * Fill form by predefined values
 * @param templateData
 */
function fillForm(templateData) {
    for (const [key, value] of Object.entries(templateData)) {
        document.getElementById(key).value = value;
        document.getElementById(key).focus();
    }
}

/**
 * Load form template
 */
function loadTemplate() {
    const templateSelect = document.getElementById('templateSelect');
    const selectedTemplate = templateSelect.options[templateSelect.selectedIndex].value;
    if (selectedTemplate) {
        const templateData = JSON.parse(templateSelect.dataset.config);
        fillForm(templateData[selectedTemplate].data);
    }
}

/**
 * Show loader on parsing form submit
 */
document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('select');
    var instances = M.FormSelect.init(elems);

    document.getElementById('parseForm').addEventListener('submit', function() {
        document.getElementById('loader').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
        document.getElementById('content').classList.add('blurred');
    });

    // Инициализация вкладок
    var tabs = document.querySelectorAll('.tabs');
    M.Tabs.init(tabs);
});

/**
 * Reset form
 */
document.getElementById('resetButton').addEventListener('click', function() {
    // Получаем форму по ID
    var form = document.getElementById('parseForm');
    // Сбрасываем значения всех полей формы
    form.reset();
    // Убираем классы active у всех label
    var labels = form.getElementsByTagName('label');
    for (var i = 0; i < labels.length; i++) {
        labels[i].classList.remove('active');
    }
});

// copy results to clipboard
document.getElementById('copyButton').addEventListener('click', function() {
    var textarea = document.getElementById('wiki');
    textarea.select();
    document.execCommand('copy');
    M.toast({html: 'Результат скопирова в буфер обмена.'}); // Показать уведомление
});
