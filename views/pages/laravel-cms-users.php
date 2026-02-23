<?php
declare(strict_types=1);
?>
<section>
    <h1 class="h3 mb-4">Daftar Laravel CMS Users</h1>

    <?php partial('partials/error-alert', ['error' => $error ?? null]); ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                </tr>
            </thead>
                <tbody>
                <?php if (!empty($laravelCmsUsers)): ?>
                    <?php foreach ($laravelCmsUsers as $laravelCmsUser): ?>
                            <tr>
                                <td class="font-monospace">
                                    <a class="link-primary text-decoration-none" href="/laravel-cms-users/<?= e($laravelCmsUser['id']) ?>">#<?= e($laravelCmsUser['id']) ?></a>
                            </td>
                                <td><?= e($laravelCmsUser['name']) ?></td>
                                <td><?= e($laravelCmsUser['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                            <td colspan="3" class="text-center py-5 text-body-secondary">Belum ada data Laravel CMS user.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</section>
