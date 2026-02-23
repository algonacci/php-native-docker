<?php
declare(strict_types=1);
?>
<section>
    <h1 class="h3 mb-4">Daftar Assessment</h1>

    <?php partial('partials/error-alert', ['error' => $error ?? null]); ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Deskripsi</th>
                </tr>
            </thead>
                <tbody>
                <?php if (!empty($assessments)): ?>
                    <?php foreach ($assessments as $assessment): ?>
                            <tr>
                                <td class="font-monospace">
                                    <a class="link-primary text-decoration-none" href="/assessments/<?= e($assessment['id']) ?>">#<?= e($assessment['id']) ?></a>
                            </td>
                                <td><?= e($assessment['title']) ?></td>
                                <td><?= e($assessment['description'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                            <td colspan="3" class="text-center py-5 text-body-secondary">Belum ada data assessment.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</section>
