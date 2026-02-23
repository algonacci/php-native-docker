<?php
declare(strict_types=1);
?>
<section>
    <h1 class="h3 mb-4">
        Database: <?= e($databaseName ?? '-') ?>
    </h1>

    <?php partial('partials/error-alert', ['error' => $error ?? null]); ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                <tr>
                        <th>ID</th>
                        <th>Email</th>
                </tr>
            </thead>
                <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="font-monospace">
                                    <a href="/users/<?= e($user['id']) ?>" class="link-primary text-decoration-none">#<?= e($user['id']) ?></a>
                            </td>
                                <td><?= e($user['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                            <td colspan="2" class="text-center py-5 text-body-secondary">Belum ada data user.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</section>
