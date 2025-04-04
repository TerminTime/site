<?php
// Определяем имя страницы из параметра или берем из URL
$page = isset($_GET['page']) ? basename($_GET['page']) : basename($_SERVER['PHP_SELF'], '.php');
$dataFile = "counter_data_{$page}.json";

// Получаем текущие данные или инициализируем новые
if (file_exists($dataFile)) {
    $data = json_decode(file_get_contents($dataFile), true);
} else {
    $data = [
        'online' => 0,
        'today' => 0,
        'month' => 0,
        'record' => 0,
        'lastUpdate' => 0,
        'dailyGoal' => ($page === 'index' ? 500 : 
                       ($page === 'page1' ? 1000 : 
                       ($page === 'page2' ? 3000 : 
                       ($page === 'page3' ? 7000 : 10000)))),
        'onlineUsers' => []
    ];
}

// Текущая дата и время
$now = time();
$today = strtotime('today');

// Если день сменился, сбрасываем счетчик сегодняшних посещений
if (date('Y-m-d', $data['lastUpdate']) != date('Y-m-d', $now)) {
    $data['today'] = 0;
}

// Если месяц сменился, сбрасываем счетчик месячных посещений
if (date('Y-m', $data['lastUpdate']) != date('Y-m', $now)) {
    $data['month'] = 0;
}

// Уникальный идентификатор пользователя
$userKey = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $page);

// Обновляем счетчики
if (!isset($data['onlineUsers'][$userKey])) {
    $data['today']++;
    $data['month']++;
}

// Обновляем онлайн пользователей (активны в последние 5 минут)
$data['onlineUsers'][$userKey] = $now;
foreach ($data['onlineUsers'] as $key => $time) {
    if ($now - $time > 300) { // 5 минут
        unset($data['onlineUsers'][$key]);
    }
}

// Обновляем рекорд
if ($data['today'] > $data['record']) {
    $data['record'] = $data['today'];
}

// Обновляем время последнего изменения
$data['lastUpdate'] = $now;

// Сохраняем данные
file_put_contents($dataFile, json_encode($data));

// Возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode([
    'online' => count($data['onlineUsers']),
    'today' => $data['today'],
    'month' => $data['month'],
    'record' => $data['record'],
    'dailyGoal' => $data['dailyGoal'],
    'progress' => min(100, ($data['today'] / $data['dailyGoal']) * 100)
]);
?>