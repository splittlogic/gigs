<?php

namespace splittlogic\gigs\Console;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use App\Models\User;

class install extends Command
{

  protected $signature = 'gigs:install';

  protected $description = 'Installation of the GIGS package';

  public function handle()
  {


    // Declare variables
    $files = null;
    $file = null;
    $contents = null;
    $step = 1;
    $totalSteps = 9;


    // Update create users migration file
    $this->info('Updating users migration file');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    $files = scandir(database_path('migrations'));
    foreach ($files as $f)
    {
      if (str_contains($f, 'create_users_table'))
      {
        $file = $f;
      }
    }
    if (is_null($file))
    {
      $file = '2014_10_12_000000_create_users_table.php';
    }
    $path = base_path('vendor/splittlogic/gigs/install');
    $contents = file_get_contents($path . '/create_users_table.php');
    file_put_contents(database_path('migrations/') . $file, $contents);
    $step++;


    // Run database migrations
    $this->info('Migrating Databases');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    \Artisan::call('migrate');
    $step++;


    // Run ui bootstrap for auth
    $this->info('Installing ui Bootstrap');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    \Artisan::call('ui bootstrap -n --auth');
    $this->info($step . ' of ' . $totalSteps . ' - Bootstrap ui ran');
    $step++;


    // npm install
    $this->info('Installing npm.  This will take some time...');
    $process = new Process(['npm', 'install']);
    $process->setTimeout(240);
    $process->run();
      // executes after the command finishes
      if (!$process->isSuccessful()) {
          throw new ProcessFailedException($process);
      }
      $this->info('   1 of 3 - npm install');
      $process = new Process(['npm', 'install', 'resolve-url-loader@^5.0.0', '--save-dev', '--legacy-peer-deps']);
      $process->run();
      // executes after the command finishes
      if (!$process->isSuccessful()) {
          throw new ProcessFailedException($process);
      }
      $this->info('   2 of 3 - npm install');
      $process = new Process(['npm', 'run', 'dev']);
      $process->run();
      // executes after the command finishes
      if (!$process->isSuccessful()) {
          throw new ProcessFailedException($process);
      }
      $this->info('   3 of 3 - npm install');
      $this->info('   Step ' . $step . ' of ' . $totalSteps);
      $step++;


    // Update User.php model file
    $this->info('Updating User model');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    $contents = file_get_contents($path . '/User.php');
    file_put_contents(app_path('Models/') . 'User.php', $contents);
    $step++;


    // Update Admin middleware
    $this->info('Updating Admin middleware');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    $contents = file_get_contents($path . '/Admin.php');
    file_put_contents(app_path('Http/Middleware') . '/Admin.php', $contents);
    $step++;


    // Update Kernel
    $this->info('Updating Kernel');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    $contents = file_get_contents($path . '/Kernel.php');
    file_put_contents(app_path('Http') . '/Kernel.php', $contents);
    $step++;


    // Update LoginController
    $this->info('Updating Login Controller');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    $contents = file_get_contents($path . '/LoginController.php');
    file_put_contents(app_path('Http/Controllers/Auth') . '/LoginController.php', $contents);
    $step++;


    // AdminLTE
    $this->info('Installing AdminLTE');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    \Artisan::call(
      'adminlte:install',
      [
        '--force' => true
      ]
    );
    $step++;


    // Create Default Admin
    $this->info('Creating Admin User');
    $this->info('   Step ' . $step . ' of ' . $totalSteps);
    $admin = User::where('email', 'admin@email.com')->first();
    if (is_null($admin))
    {
      $user = new User();
      $user->password = Hash::make('password');
      $user->email = 'admin@email.com';
      $user->name = 'Default Admin';
      $user->is_admin = 1;
      $user->save();
    }
    $step++;


  }

}
