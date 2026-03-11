<?php

require_once __DIR__ . '/../src/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// Route definitions
switch (true) {
    // --- Login ---
    case $uri === '/' || $uri === '/login':
        if ($auth->isLoggedIn()) {
            header('Location: /game');
            exit;
        }
        if ($method === 'POST') {
            $result = $auth->login($_POST['username'] ?? '', $_POST['password'] ?? '');
            if ($result['success']) {
                header('Location: /game');
                exit;
            }
            $error = $result['error'];
        }
        require __DIR__ . '/../templates/login.php';
        break;

    // --- Logout ---
    case $uri === '/logout':
        $auth->logout();
        break;

    // --- Game (HTML page) ---
    case $uri === '/game':
        $auth->requireLogin();
        $playerName = $auth->currentUser();
        $data = $game->getInitialData($playerName);
        $state = $game->loadState($playerName);
        $classClient = 'clientDefault';
        $width = 33;
        $height = 17;
        require __DIR__ . '/../templates/game.php';
        break;

    // --- Game API (AJAX endpoint) ---
    case $uri === '/api/game':
        $auth->requireLogin();
        $playerName = $auth->currentUser();
        $state = $game->loadState($playerName);

        $action = $_GET['action'] ?? null;
        $reqX = $_GET['x'] ?? null;
        $reqY = $_GET['y'] ?? null;
        $posX = $_GET['position_x'] ?? null;
        $posY = $_GET['position_y'] ?? null;

        $data = $game->processAction($playerName, $state, $action, $reqX, $reqY, $posX, $posY);
        header('Content-Type: application/json');
        echo json_encode($data);
        break;

    // --- Spell cast ---
    case $uri === '/api/cast':
        $auth->requireLogin();
        $playerName = $auth->currentUser();
        $spellId = (int) ($_GET['cast'] ?? 0);
        $state = $game->loadState($playerName);
        $game->castSpell($playerName, $spellId, $state);
        header('Location: /game');
        exit;

    // --- Editor ---
    case $uri === '/editor':
        $auth->requireAdmin();
        $floor = max(1, (int) ($_GET['floor'] ?? 1));
        $levelFile = __DIR__ . '/levels/' . $floor . '-1.dat';
        if (!file_exists($levelFile)) {
            http_response_code(404);
            echo 'Map not found.';
            break;
        }
        $level = unserialize(file_get_contents($levelFile));
        $background = 'img/backgrounds/' . $floor . '-1.gif';
        if (!file_exists(__DIR__ . '/' . $background)) $background = 'img/world.gif';
        $editorData = ['floor' => $floor, 'level' => $level, 'background' => $background];
        require __DIR__ . '/../templates/editor.php';
        break;

    // --- Admin ---
    case $uri === '/admin':
        $auth->requireAdmin();
        $resetUser = preg_replace('/[^A-Za-z0-9]/', '', $_GET['User'] ?? '');
        $resetUser = substr($resetUser, 0, 3);
        $resetMessage = '';
        if ($resetUser !== '') {
            $game->warpPlayer($resetUser, 2, 27, 41);
            $resetMessage = "{$resetUser} was warped to Fauns Nest";
        }
        $leaderboard = $game->getLeaderboard();
        require __DIR__ . '/../templates/admin.php';
        break;

    // --- Static fallthrough ---
    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
