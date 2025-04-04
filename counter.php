<?php
// Включим вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Определяем имя страницы
$page = isset($_GET['page']) ? basename($_GET['page']) : 'index';
$dataFile = "counter_data_$page.json";

// Проверяем права на запись
if (!is_writable('.')) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'No write permissions']));
}

// Базовые данные
$defaultData = [
    'online' => 0,
    'today' => 0,
    'month' => 0,
    'record' => 0,
    'lastUpdate' => 0,
    'dailyGoal' => match($page) {
        'index' => 500,
        'page1' => 1000,
        'page2' => 3000,
        'page3' => 7000,
        'page4' => 10000,
        default => 1000
    },
    'onlineUsers' => [],
    'todayDate' => date('Y-m-d'),
    'monthDate' => date('Y-m')
];

try {
    // Загружаем или создаем данные
    if (file_exists($dataFile)) {
        $data = json_decode(file_get_contents($dataFile), true);
        
        // Проверяем новый день
        if (date('Y-m-d') != $data['todayDate']) {
            $data['today'] = 0;
            $data['todayDate'] = date('Y-m-d');
            $data['onlineUsers'] = [];
        }
        
        // Проверяем новый месяц
        if (date('Y-m') != $data['monthDate']) {
            $data['month'] = 0;
            $data['monthDate'] = date('Y-m');
        }
    } else {
        $data = $defaultData;
    }

    // Уникальный идентификатор пользователя
    $userKey = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].$page.$_SERVER['HTTP_ACCEPT_LANGUAGE']);

    // Обновляем счетчики
    if (!isset($data['onlineUsers'][$userKey])) {
        $data['today']++;
        $data['month']++;
    }

    // Обновляем онлайн пользователей
    $now = time();
    $data['onlineUsers'][$userKey] = $now;
    
    // Удаляем неактивных (более 5 минут)
    foreach ($data['onlineUsers'] as $key => $time) {
        if ($now - $time > 300) {
            unset($data['onlineUsers'][$key]);
        }
    }

    // Обновляем рекорд
    if ($data['today'] > $data['record']) {
        $data['record'] = $data['today'];
    }

    $data['lastUpdate'] = $now;

    // Сохраняем данные
    if (file_put_contents($dataFile, json_encode($data)) === false) {
        throw new Exception("Failed to write data file");
    }

    // Возвращаем данные
    header('Content-Type: application/json');
    echo json_encode([
        'online' => count($data['onlineUsers']),
        'today' => $data['today'],
        'month' => $data['month'],
        'record' => $data['record'],
        'dailyGoal' => $data['dailyGoal'],
        'progress' => min(100, ($data['today'] / $data['dailyGoal']) * 100)
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
