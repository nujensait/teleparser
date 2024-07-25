<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Parser</title>
</head>
<body>
<h1>Site Parser</h1>
<form id="parseForm" method="post" action="parse.php">
    <label for="url">URL:</label>
    <input type="text" id="url" name="url" required>
    <br>
    <label for="depth">DEPTH:</label>
    <input type="number" id="depth" name="depth" min="1" required>
    <br>
    <button type="submit">Парсить</button>
</form>
<div id="result">
    <!-- Здесь будут отображаться ссылки на скачанные файлы -->
</div>
</body>
</html>