<?php
/**
 * Heimdall on Railway: finish first-run setup and give user 1 a password.
 *
 * Stock Heimdall seeds user 1 ("admin") with a NULL password, and its
 * CheckAllowed middleware lets every request through while that is so: an
 * unmodified container on a public URL is an open, editable dashboard. This
 * runs before nginx and php-fpm start, so that window never exists.
 *
 * Env: HEIMDALL_ADMIN_USERNAME, HEIMDALL_ADMIN_PASSWORD,
 *      RAILWAY_APPLY_ADMIN=1 to (re)write the credentials; otherwise they are
 *      written only while user 1 has no password.
 */
$base = '/app/www';
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Booting the providers runs Heimdall's own first-run setup (SQLite file,
// migrations, seeders, app catalogue download). A catalogue download failure
// must not stop the password write, so only warn here.
try {
    $kernel->bootstrap();
} catch (Throwable $e) {
    fwrite(STDERR, '[railway] warning: boot: ' . $e->getMessage() . "\n");
}

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

try {
    if (! Schema::hasTable('users') || ! Schema::hasTable('settings')) {
        fwrite(STDERR, "[railway] running migrations\n");
        Artisan::call('migrate', ['--path' => 'database/migrations', '--force' => true, '--seed' => true]);
    }
    if (App\User::count() === 0) {
        Artisan::call('db:seed', ['--force' => true]);
    }
} catch (Throwable $e) {
    fwrite(STDERR, '[railway] error: database setup failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$user = App\User::find(1) ?: App\User::orderBy('id')->first();
if (! $user) {
    fwrite(STDERR, "[railway] error: no Heimdall user after migrating\n");
    exit(1);
}

$username = (string) getenv('HEIMDALL_ADMIN_USERNAME');
$password = (string) getenv('HEIMDALL_ADMIN_PASSWORD');
if ($username === '' || $password === '') {
    fwrite(STDERR, "[railway] error: HEIMDALL_ADMIN_USERNAME or HEIMDALL_ADMIN_PASSWORD is empty\n");
    exit(1);
}

$passwordless = empty($user->password);
if (getenv('RAILWAY_APPLY_ADMIN') !== '1' && ! $passwordless) {
    echo "[railway] admin credentials unchanged\n";
    exit(0);
}

$user->username = $username;
$user->password = Hash::make($password);
if ($passwordless) {
    $user->public_front = 0;
}
$user->save();

$check = App\User::find($user->id);
if (empty($check->password) || ! Hash::check($password, $check->password)) {
    fwrite(STDERR, "[railway] error: the admin password did not persist\n");
    exit(1);
}
echo '[railway] admin password set for user ' . $check->id . ' (' . $check->username . ")\n";
exit(0);
