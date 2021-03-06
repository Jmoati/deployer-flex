<?php

namespace Deployer;

require_once 'recipe/common.php';

// Symfony build set
set('symfony_env', 'prod');

// Symfony shared dirs
set('shared_dirs', ['app/logs']);

// Symfony shared files
set('shared_files', ['app/config/parameters.yml']);

// Symfony writable dirs
set('writable_dirs', ['app/cache', 'app/logs']);

// Clear paths
set('clear_paths', ['web/app_*.php', 'web/config.php']);

// Assets
set('assets', ['web/css', 'web/images', 'web/js']);

// Requires non symfony-core package `kriswallsmith/assetic` to be installed
set('dump_assets', false);

// Adding support for the Symfony3 directory structure
set('bin_dir', 'app');
set('var_dir', 'app');

// Php bin
set('bin/php', function () {
    return 'docker run -e uid={{uid}} --rm -i -v /srv:/srv --link mysql:mysql dockenizer/dev ';
});

// Symfony console bin
set('bin/composer', function () {
    return '{{bin/php}} cd {{release_path}} && composer ';
});

// Symfony console opts
set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

/**
 * Create cache dir
 */
task('deploy:create_cache_dir', function () {
    // Set cache dir
    set('cache_dir', '{{release_path}}/' . trim(get('var_dir'), '/') . '/cache');

    // Remove cache dir if it exist
    run('if [ -d "{{cache_dir}}" ]; then rm -rf {{cache_dir}}; fi');

    // Create cache dir
    run('mkdir -p {{cache_dir}}');

    // Set rights
    run("chmod -R g+w {{cache_dir}}");
})->desc('Create cache dir');


/**
 * Normalize asset timestamps
 */
task('deploy:assets', function () {
    $assets = implode(' ', array_map(function ($asset) {
        return "{{release_path}}/$asset";
    }, get('assets')));

    run(sprintf('find %s -exec touch -t %s {} \';\' &> /dev/null || true', $assets, date('Ymdhi.s')));
})->desc('Normalize asset timestamps');


/**
 * Install assets from public dir of bundles
 */
task('deploy:assets:install', function () {
//    run('{{bin/php}} {{bin/console}} assets:install {{console_options}} {{release_path}}/web');
})->desc('Install bundle assets');


/**
 * Dump all assets to the filesystem
 */
task('deploy:assetic:dump', function () {
    if (get('dump_assets')) {
        run('{{bin/php}} {{bin/console}} assetic:dump {{console_options}}');
    }
})->desc('Dump assets');

/**
 * Clear Cache
 */
task('deploy:cache:clear', function () {
    return 'make cache-clear';
})->desc('Clear cache');

/**
 * Warm up cache
 */
task('deploy:cache:warmup', function () {
    return 'make cache-warmup';
})->desc('Warm up cache');


/**
 * Migrate database
 */
task('database:migrate', function () {
//    run('{{bin/php}} {{bin/console}} doctrine:migrations:migrate {{console_options}} --allow-no-migration');
})->desc('Migrate database');

task('deploy:vendors', function () {
    run('{{bin/php}} "cd {{release_path}} && composer  {{composer_options}}"');
});


/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:copy_dirs',
    'deploy:clear_paths',
    'deploy:create_cache_dir',
    'deploy:shared',
    'deploy:assets',
    'deploy:vendors',
    'deploy:assets:install',
    'deploy:assetic:dump',
    'deploy:cache:clear',
    'deploy:cache:warmup',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

// Display success message on completion
after('deploy', 'success');
