<?php
declare(strict_types=1);

$title = isset($pageTitle) ? (string) $pageTitle : 'PHP Native App';
$appName = isset($app['name']) ? (string) $app['name'] : 'PHP Native App';
$appVersion = isset($app['version']) ? (string) $app['version'] : 'dev';
$usersRoute = isset($app['routes']['users']) ? (string) $app['routes']['users'] : '/';
$assessmentsRoute = isset($app['routes']['assessments']) ? (string) $app['routes']['assessments'] : '/assessments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <header class="border-b border-gray-700">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between gap-6">
            <div class="text-gray-300">
                <span class="font-semibold"><?= e($appName) ?></span>
                <span class="text-gray-500 text-sm">v<?= e($appVersion) ?></span>
            </div>
            <nav class="flex gap-6">
                <a class="text-blue-400 hover:text-blue-300" href="<?= e($usersRoute) ?>">Users</a>
                <a class="text-blue-400 hover:text-blue-300" href="<?= e($assessmentsRoute) ?>">Assessments</a>
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-8">
        <?= $content ?>
    </main>
</body>
</html>
