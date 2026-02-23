<?php
declare(strict_types=1);

if (empty($error)) {
    return;
}
?>
<div class="alert alert-danger mb-4" role="alert">
    Error: <?= e($error) ?>
</div>
