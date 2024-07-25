<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Parser</title>
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
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="center-align">Парсер сайта</h1>
    <form id="parseForm" method="post" action="index.php">
        <div class="input-field">
            <input type="text" id="url" name="url" required value="<?= ($_REQUEST['url'] ?: '') ?>"/>
            <label for="url">Адрес стартовой страницы (URL): </label>
        </div>
        <div class="input-field">
            <input type="number" id="depth" name="depth" min="1" required value="<?= ($_REQUEST['depth'] ?: '') ?>"/>
            <label for="depth">Глубина парсинга: </label>
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
    });
</script>
</body>
</html>
<?php

require_once ("parse.php");

?>