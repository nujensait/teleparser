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
    <!-- Import Materialize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <style>
        .loader {
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
            z-index: 1001; /* Higher than the overlay */
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .req {
            color: orangered;
            font-size: 20px;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
        }
        .blurred {
            filter: blur(5px);
            pointer-events: none; /* Disable clicks */
        }
    </style>
</head>
<body>
<div class="overlay" id="overlay"></div>
<div class="container" id="content">
    <h1 class="center-align">Парсер сайтов</h1>
    <form id="parseForm" method="post" action="index.php">
        <div class="input-field">
            <input type="text" id="url" name="url" required value="<?= (isset($_REQUEST['url']) ? $_REQUEST['url'] : 'https://www.zabbix.com/documentation/5.0/ru/manual') ?>"/>
            <label for="url">Адрес стартовой страницы (URL): <small class="req">*</small></label>
        </div>
        <div class="input-field">
            <input type="number" id="depth" name="depth" min="0" max="10" required value="<?= (isset($_REQUEST['depth']) ? $_REQUEST['depth'] : 1) ?>"/>
            <label for="depth">Глубина парсинга: <small class="req">*</small></label>
        </div>
        <div class="input-field">
            <input type="text" id="pattern" name="pattern" value="<?= (isset($_REQUEST['pattern']) ? $_REQUEST['pattern'] : 'documentation/5.0/ru/manual') ?>"/>
            <label for="depth">Шаблон ссылок для парсинга: </label>
        </div>
        <div class="input-field">
            <input type="text" id="div" name="div" value="<?= (isset($_REQUEST['div']) ? $_REQUEST['div'] : 'dokuwiki__content') ?>"/>
            <label for="depth">Парсить контент только внутри указанного div (id/class): </label>
        </div>
        <button class="btn waves-effect waves-light" type="submit" name="action">Запуск
            <i class="material-icons right">send</i>
        </button>
    </form>
    <div id="result" class="section">
        <!-- Links to downloaded files will be displayed here -->
    </div>
</div>
<div class="loader" id="loader"></div>
<script>
    document.getElementById('parseForm').addEventListener('submit', function() {
        document.getElementById('loader').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
        document.getElementById('content').classList.add('blurred');
    });
</script>
</body>
</html>

<?php require_once ("parse.php"); ?>
