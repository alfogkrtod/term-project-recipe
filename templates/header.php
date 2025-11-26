<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>레시피 검색</title>
    <?php
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir === '.' || $scriptDir === '/') {
            $scriptDir = '';
        }
        $cssPath = $scriptDir . '/public/assets/css/style.css';
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <header>
        <nav>
            <h1>레시피 검색</h1>
        </nav>
    </header>
    <main>

