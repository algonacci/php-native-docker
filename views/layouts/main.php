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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body class="bg-body-tertiary min-vh-100">
    <header class="border-bottom bg-white">
        <div class="container py-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="text-body-secondary">
                <span class="fw-semibold text-body-emphasis"><?= e($appName) ?></span>
                <span class="small">v<?= e($appVersion) ?></span>
            </div>
            <?php $currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH); ?>
            <?php if ($currentPath !== '/login' && app_is_authenticated()): ?>
                <nav class="d-flex align-items-center gap-3">
                    <a class="link-primary text-decoration-none" href="<?= e($usersRoute) ?>">Users</a>
                    <a class="link-primary text-decoration-none" href="/laravel-cms-users">Laravel CMS Users</a>
                    <a class="link-primary text-decoration-none" href="<?= e($assessmentsRoute) ?>">Assessments</a>
                    <form action="/logout" method="post" class="d-inline">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Logout (<?= e($_SESSION['user_name'] ?? '') ?>)</button>
                    </form>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="container py-4 py-md-5">
        <?= $content ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</body>
</html>
