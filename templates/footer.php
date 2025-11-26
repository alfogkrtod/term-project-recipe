    </main>
    <footer>
        <p>&copy; 2024 레시피 검색</p>
    </footer>
    <?php
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir === '.' || $scriptDir === '/') {
            $scriptDir = '';
        }
        $jsPath = $scriptDir . '/public/assets/js/script.js';
    ?>
    <script src="<?php echo htmlspecialchars($jsPath, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>

