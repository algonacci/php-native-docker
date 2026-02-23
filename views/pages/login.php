<section class="flex items-center justify-center min-h-[60vh]">
    <div class="w-full max-w-md px-6">
        <div class="bg-gray-800 rounded-lg shadow-xl p-8">
            <h1 class="text-2xl font-bold mb-6 text-center text-blue-400">Login</h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded-lg text-red-300">
                    <?= e($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="/login" method="post" class="space-y-6">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <div>
                    <label class="block text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-400 text-white placeholder-gray-400" placeholder="email@example.com" required>
                </div>

                <div>
                    <label class="block text-gray-300 mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-400 text-white placeholder-gray-400" placeholder="••••••••" required>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</section>
