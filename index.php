<?php

/**
 * Show parsing form (html)
 */

// Подключение конфигурационного файла
$config = include 'config/forms.php';

// Обработка выбора шаблона
$selectedTemplate = $_GET['template'] ?? null;
$templateData = [];
if ($selectedTemplate && isset($config[$selectedTemplate])) {
    $templateData = $config[$selectedTemplate]['data'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Парсер сайтов</title>
    <!-- Import Materialize CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <!-- Import Google Icon Font -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Import Custom CSS -->
    <link href="/assets/styles.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon"></head>
<body>
<div class="overlay" id="overlay"></div>
<div class="container" id="content">
    <h1 class="center-align">Парсер сайтов</h1>

    <!-- Вкладки -->
    <ul class="tabs">
        <li class="tab col s3"><a class="active" href="#parse-tab">Парсинг сайта</a></li>
        <li class="tab col s3"><a href="#dokuwiki-tab">Конвертация в DokuWiki</a></li>
        <li class="tab col s3"><a href="#history-tab">История парсинга</a></li>
        <li class="tab col s3"><a href="#help-tab">Справка</a></li>
    </ul>

    <!-- Вкладка Парсинг сайта -->
    <div id="parse-tab" class="col s12">
        <form id="parseForm" method="post" action="index.php#parse-tab">

            <input type="hidden" name="todo" value="parsing" />

            <div class="input-field">
                <select id="templateSelect" onchange="loadTemplate()" data-config='<?= json_encode($config); ?>'>
                    <option value="">-- Выберите шаблон --</option>
                    <?php foreach ($config as $templateKey => $template) : ?>
                        <option value="<?= $templateKey; ?>"><?= $template['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="templateSelect">Готовые шаблоны:</label>
            </div>

            <div class="input-field">
                <input type="text" id="url" name="url" required value="<?= $templateData['url'] ?? (isset($_REQUEST['url']) ? $_REQUEST['url'] : '') ?>"/>
                <label for="url">Адрес стартовой страницы (URL): <small class="req">*</small></label>
            </div>

            <div class="input-field">
                <input type="number" id="depth" name="depth" min="0" max="10" required value="<?= $templateData['depth'] ?? (isset($_REQUEST['depth']) ? $_REQUEST['depth'] : 0) ?>"/>
                <label for="depth">Глубина парсинга (как глубоко уходить от стартовой страницы): <small class="req">*</small></label>
            </div>

            <div class="input-field">
                <input type="number" id="limit" name="limit" min="0" max="10000" required value="<?= $templateData['limit'] ?? (isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 0) ?>"/>
                <label for="depth">Лимит парсинга (кол-во страниц, 0 - без ограничений): <small class="req">*</small></label>
            </div>

            <div class="input-field">
                <input type="text" id="pattern" name="pattern" value="<?= $templateData['pattern'] ?? (isset($_REQUEST['pattern']) ? $_REQUEST['pattern'] : '') ?>"/>
                <label for="pattern">Шаблон ссылок для парсинга (необязательно): </label>
            </div>

            <div class="input-field">
                <input type="text" id="div" name="div" value="<?= $templateData['div'] ?? (isset($_REQUEST['div']) ? $_REQUEST['div'] : '') ?>"/>
                <label for="div">Парсить контент только внутри указанного div (id/class): </label>
            </div>

            <button class="btn waves-effect waves-light" type="submit" name="action" style="margin-right:50px;">Запуск
                <i class="material-icons right">send</i>
            </button>

            <button class="btn waves-effect waves-light" type="button" id="resetButton">Сброс
                <i class="material-icons right">refresh</i>
            </button>
        </form>

        <?php if (isset($_POST['todo']) && $_POST['todo']=='parsing')  { require_once("parse.php"); } ?>

    </div>

    <!-- Вкладка Конвертация в DokuWiki -->
    <div id="dokuwiki-tab" class="col s12">
        <!-- Форма для конвертации будет добавлена здесь позже -->
        <form id="convForm" method="post" action="index.php#dokuwiki-tab">

            <input type="hidden" name="todo" value="convert" />

            <div class="input-field">
                <select id="templateSelect" onchange="loadTemplate()" data-config='<?= json_encode($config); ?>'>
                    <option value="">-- Выберите шаблон --</option>
                    <?php foreach ($config as $templateKey => $template) : ?>
                        <option value="<?= $templateKey; ?>"><?= $template['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="templateSelect">Готовые шаблоны:</label>
            </div>

            <div class="input-field">
                <label for="html">Код html: <small class="req">*</small></label>
                <textarea id="html" name="html" required value="<?= isset($_REQUEST['html']) ? htmlspecialchars($_REQUEST['html'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '' ?>" class="codeText"></textarea>
            </div>

            <div class="input-field">
                <input type="text" id="divid" name="divid" value="<?= $templateData['div'] ?? (isset($_REQUEST['divid']) ? $_REQUEST['divid'] : '') ?>"/>
                <label for="div">Парсить контент только внутри указанного div (id/class): </label>
            </div>

            <button class="btn waves-effect waves-light" type="submit" name="action" style="margin-right:50px;">Конвертировать
                <i class="material-icons right">send</i>
            </button>

            <button class="btn waves-effect waves-light" type="button" id="resetButton">Сброс
                <i class="material-icons right">refresh</i>
            </button>
        </form>

        <?php if (isset($_POST['todo']) && $_POST['todo']=='convert')  { require_once("parse.php"); } ?>

    </div>

    <!-- Вкладка История парсинга -->
    <div id="history-tab" class="col s12">
        <!-- Форма для истории парсинга будет добавлена здесь позже -->
    </div>

    <!-- Вкладка Справка -->
    <div id="help-tab" class="col s12">
        <!-- Форма для справки будет добавлена здесь позже -->
    </div>

    <div id="result" class="section">
        <!-- Links to downloaded files will be displayed here -->
    </div>

</div>

<div class="loader" id="loader"></div>
<!-- Import Materialize JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<!-- Import Custom JS -->
<script src="/assets/scripts.js"></script>

<footer class="page-footer teal">
    <div class="footer-copyright teal">
        <div class="container teal">
            © <?= date('Y') ?>
            <a class="grey-text text-lighten-4 right" href="#!">#</a>
        </div>
    </div>
</footer>

</body>
</html>

