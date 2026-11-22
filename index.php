<?php
$dataFile = __DIR__ . '/data.json';

function loadTasks($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveTasks($file, $tasks) {
    file_put_contents($file, json_encode(array_values($tasks), JSON_PRETTY_PRINT), LOCK_EX);
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$tasks = loadTasks($dataFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (string)($_POST['id'] ?? '');

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        if ($title !== '') {
            $tasks[] = [
                'id' => bin2hex(random_bytes(6)),
                'title' => $title,
                'done' => false,
                'created' => date('Y-m-d H:i')
            ];
        }
    } elseif ($action === 'toggle' || $action === 'delete') {
        foreach ($tasks as $key => &$task) {
            if ($task['id'] === $id) {
                if ($action === 'toggle') $task['done'] = !$task['done'];
                if ($action === 'delete') unset($tasks[$key]);
                break;
            }
        }
        unset($task);
    } elseif ($action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        foreach ($tasks as &$task) {
            if ($task['id'] === $id && $title !== '') {
                $task['title'] = $title;
                break;
            }
        }
        unset($task);
    }

    saveTasks($dataFile, $tasks);
    header('Location: index.php');
    exit;
}

$total = count($tasks);
$completed = count(array_filter($tasks, fn($task) => $task['done']));
$pending = $total - $completed;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskList</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 40px 16px;
            background: #f4f6fb;
            color: #1c2740;
            font: 15px Arial, sans-serif;
        }
        main { max-width: 680px; margin: auto; }
        h1 { margin-bottom: 6px; }
        .muted { color: #778196; }
        .stats { display: flex; gap: 12px; margin: 24px 0; }
        .stats div, .panel {
            background: white;
            border: 1px solid #e5e9f2;
            border-radius: 12px;
            padding: 18px;
        }
        .stats div { flex: 1; }
        .stats strong {
            display: block;
            font-size: 25px;
            margin-top: 6px;
        }
        form.add { display: flex; gap: 8px; margin-bottom: 18px; }
        input[type=text] {
            min-width: 0;
            flex: 1;
            padding: 12px;
            border: 1px solid #d8deeb;
            border-radius: 8px;
            font-size: 15px;
        }
        button {
            border: 0;
            border-radius: 8px;
            padding: 11px 14px;
            background: #3157d5;
            color: white;
            cursor: pointer;
        }
        .task {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 0;
            border-bottom: 1px solid #edf0f5;
        }
        .task:last-child { border-bottom: 0; }
        .task-title { flex: 1; overflow-wrap: anywhere; }
        .done { color: #929aaa; text-decoration: line-through; }
        .inline { display: inline; }
        .small { padding: 7px 9px; font-size: 12px; }
        .delete { background: #e9edf5; color: #34415b; }
        .edit { background: #edf1ff; color: #3157d5; }
        .empty { text-align: center; padding: 28px; color: #778196; }
        @media (max-width: 480px) {
            body { padding-top: 24px; }
            .stats { gap: 7px; }
            .stats div { padding: 12px; }
            .task { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
<main>
    <h1>TaskList</h1>
    <p class="muted">A simple place to keep track of your work.</p>

    <section class="stats">
        <div><span class="muted">All tasks</span><strong><?= $total ?></strong></div>
        <div><span class="muted">Pending</span><strong><?= $pending ?></strong></div>
        <div><span class="muted">Completed</span><strong><?= $completed ?></strong></div>
    </section>

    <section class="panel">
        <form class="add" method="post">
            <input type="hidden" name="action" value="add">
            <input type="text" name="title"
                   placeholder="What needs to be done?" required>
            <button type="submit">Add task</button>
        </form>

        <?php if (!$tasks): ?>
            <div class="empty">No tasks yet. Add your first task above.</div>
        <?php else: ?>
            <?php foreach (array_reverse($tasks) as $task): ?>
                <div class="task">
                    <form class="inline" method="post">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= h($task['id']) ?>">
                        <button class="small" type="submit">
                            <?= $task['done'] ? 'Undo' : 'Done' ?>
                        </button>
                    </form>

                    <span class="task-title <?= $task['done'] ? 'done' : '' ?>">
                        <?= h($task['title']) ?>
                    </span>

                    <form class="inline" method="post"
                          onsubmit="return renameTask(this)">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= h($task['id']) ?>">
                        <input type="hidden" name="title">
                        <button class="small edit" type="submit">Edit</button>
                    </form>

                    <form class="inline" method="post"
                          onsubmit="return confirm('Delete this task?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= h($task['id']) ?>">
                        <button class="small delete" type="submit">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <p class="muted" style="font-size:12px;margin-top:16px">
        TaskList · PHP
    </p>
</main>

<script>
function renameTask(form) {
    const title = prompt('Edit task title:');
    if (title === null || !title.trim()) return false;
    form.querySelector('[name="title"]').value = title.trim();
    return true;
}
</script>
</body>
</html>
