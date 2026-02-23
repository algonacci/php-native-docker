<section class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="w-100" style="max-width: 420px;">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 text-center mb-4">Login</h1>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= e($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="/login" method="post">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="********" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
