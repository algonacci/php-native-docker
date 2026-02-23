<?php
declare(strict_types=1);

$title = isset($pageTitle) ? (string) $pageTitle : 'PHP Native App';
$appName = isset($app['name']) ? (string) $app['name'] : 'PHP Native App';
$appVersion = isset($app['version']) ? (string) $app['version'] : 'dev';
$usersRoute = isset($app['routes']['users']) ? (string) $app['routes']['users'] : '/users';
$navigationItems = isset($app['navigation']) && is_array($app['navigation']) ? $app['navigation'] : [];
$currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$currentPath = Router::normalizePath(is_string($currentPath) ? $currentPath : '/');
$isLoginPage = $currentPath === '/login';
$showNavigation = app_is_authenticated() && !$isLoginPage;
$userName = (string) ($_SESSION['user_name'] ?? '');
$userRole = (string) ($_SESSION['user_role'] ?? 'Super Admin');
$desktopHeaderOffset = '56px';

$renderNavigation = static function (array $items, string $activePath): void {
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $label = (string) ($item['label'] ?? '');
        $path = Router::normalizePath((string) ($item['path'] ?? ''));

        if ($label === '' || $path === '') {
            continue;
        }

        $isActive = $path === '/'
            ? $activePath === '/'
            : $activePath === $path || str_starts_with($activePath, $path . '/');

        $class = $isActive ? 'nav-link active' : 'nav-link link-body-emphasis';
        ?>
        <li class="nav-item">
            <a class="<?= e($class) ?>" href="<?= e($path) ?>"><?= e($label) ?></a>
        </li>
        <?php
    }
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= e($appVersion) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<body class="bg-body-tertiary min-vh-100">
<?php if ($showNavigation): ?>
    <header class="navbar navbar-expand-lg border-bottom bg-white sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar">
                Menu
            </button>
            <a class="navbar-brand app-brand mb-0 h1" href="<?= e($usersRoute) ?>"><?= e($appName) ?></a>
            <div class="d-flex align-items-center gap-2">
                <button class="theme-toggle" type="button" aria-label="Toggle dark mode" title="Toggle dark mode">
                    <i class="fas fa-moon"></i>
                </button>
                <span class="text-body-secondary small d-none d-md-inline">v<?= e($appVersion) ?></span>
                <span class="text-body-secondary small d-none d-sm-inline"><?= e($userName) ?></span>
                <form action="/logout" method="post" class="d-inline">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container-fluid px-0">
        <div class="row g-0">
            <aside class="col-lg-3 col-xl-2 d-none d-lg-block border-end bg-white" style="position: sticky; top: <?= e($desktopHeaderOffset) ?>; height: calc(100vh - <?= e($desktopHeaderOffset) ?>);">
                <div class="px-3 px-lg-4 py-3 h-100 d-flex flex-column">
                    <div class="small text-uppercase text-body-secondary fw-semibold mb-2">Navigation</div>
                    <ul class="nav nav-pills flex-column gap-1">
                        <?php $renderNavigation($navigationItems, $currentPath); ?>
                    </ul>
                    <div class="mt-auto pt-3 border-top">
                        <div class="small text-body-secondary">v<?= e($appVersion) ?></div>
                        <div class="small fw-semibold"><?= e($userRole) ?></div>
                        <div class="small text-body-secondary">Masuk sebagai <?= e($userName) ?></div>
                    </div>
                </div>
            </aside>
            <main class="col-lg-9 col-xl-10 px-3 px-md-4 px-xl-5 py-4 py-md-5">
                <?= $content ?>
            </main>
        </div>
    </div>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="appSidebarLabel"><?= e($appName) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav nav-pills flex-column gap-1">
                <?php $renderNavigation($navigationItems, $currentPath); ?>
            </ul>
            <div class="pt-3 mt-3 border-top">
                <div class="small text-body-secondary">v<?= e($appVersion) ?></div>
                <div class="small fw-semibold"><?= e($userRole) ?></div>
                <div class="small text-body-secondary">Masuk sebagai <?= e($userName) ?></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <header class="border-bottom bg-white">
        <div class="container py-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="text-body-secondary app-brand">
                <span class="fw-semibold text-body-emphasis"><?= e($appName) ?></span>
                <span class="small">v<?= e($appVersion) ?></span>
            </div>
            <button class="theme-toggle" type="button" aria-label="Toggle dark mode" title="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </header>
    <main class="container py-4 py-md-5">
        <?= $content ?>
    </main>
<?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <script src="/js/darkMode.js"></script>
    <script src="/js/networkGuard.js"></script>
</body>
</html>
