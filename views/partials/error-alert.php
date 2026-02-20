<?php
declare(strict_types=1);

if (empty($error)) {
    return;
}
?>
<div class="bg-red-500 text-white p-4 rounded mb-4">
    Error: <?= e($error) ?>
</div>
