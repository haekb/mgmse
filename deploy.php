<?php
namespace Deployer;

require 'recipe/laravel.php';

inventory('hosts.yml');

// Project name
set('application', 'mgmse');

// Project repository
set('repository', 'https://github.com/haekb/mgmse.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', false);

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server
add('writable_dirs', []);

set('http_user', 'mr_mgmse');

// Hosts

host('prod')
    ->set('deploy_path', '/var/www/deployments/{{application}}');

// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

task('supervisor:restart', function () {
    // Remove from source.
    run('sudo supervisorctl restart all');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');

// Ok our deploy is done, restart supervisor
after('deploy', 'supervisor:restart');
