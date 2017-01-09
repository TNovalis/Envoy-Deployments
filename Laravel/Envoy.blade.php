@servers(['web' => '127.0.0.1'])

@setup
    if(!isset($path)) {
        throw new Exception('Please specify a --path');
    }

    if(!isset($repo)) {
        throw new Exception('Please specify a --repo');
    }

    $migrate = (isset($migrate) && (boolval($migrate) == true)) ? true : false;
    $branch = (isset($branch)) ? $branch : 'master';
    $env = (isset($env)) ? $env : 'staging';
    $stage_hash = md5(date('YmdHis'));

    // directories
    $base_dir = rtrim($path, '/');
    $current_dir = $base_dir . '/current';
    $releases_dir = $base_dir . '/releases';
    $storage_dir = $base_dir . '/storage';
    $staging_dir = $releases_dir . '/' . $stage_hash;
@endsetup

@macro('deploy')
    prep
    update-links
    run-composer
    optimise
    update-perms
    link-current
    migrate
    purge-old
@endmacro

@task('prep')
    echo "prep:start"

    # Create directories that may not exist.
    [ ! -d {{ $base_dir }} ] && mkdir -p {{ $base_dir }}
    [ ! -d {{ $releases_dir }} ] && mkdir -p {{ $releases_dir }}

    # Clone the repo
    git clone {{ $repo }} -q --depth 1 {{ $staging_dir }}

    # Copy directories for new setups
    [ ! -f {{ $base_dir }}/.env ] && cp -R {{ $staging_dir }}/.env.example {{ $base_dir }}/.env
    [ ! -d {{ $storage_dir }} ] && cp -R {{ $staging_dir }}/storage {{ $storage_dir }}

    echo "prep:end"
@endtask

@task('update-links')
    echo "update-links:start"

    # Link storage dir
    rm -rf {{ $staging_dir }}/storage
    ln -nfs {{ $storage_dir }} {{ $staging_dir }}/storage

    # Link env
    ln -nfs {{ $base_dir }}/.env {{ $staging_dir }}/.env

    echo "update-links:end"
@endtask

@task('run-composer')
    echo "run-composer:start"

    # Install dependencies
    cd {{ $staging_dir }}
    composer install --prefer-dist --no-scripts -q -o --no-dev

    echo "run-composer:end"
@endtask

@task('optimise')
    echo "optimise:start"

    # Aggressive caching of Laravel applications
    cd {{ $staging_dir }}

    # Optimise the Laravel application
    php artisan clear-compiled -q --env={{ $env }} > /dev/null
    php artisan optimize -q --env={{ $env }} --force > /dev/null

    # Cache config for faster lookup
    php artisan -q config:cache > /dev/null

    # Cache routes for faster routing
    php artisan -q route:cache > /dev/null

    echo "optimise:end"
@endtask

@task('update-perms')
    echo "update-perms:start"

    cd {{ $staging_dir }}
    find . -type d -exec chmod 775 {} \;
    find . -type f -exec chmod 664 {} \;

    echo "update-perms:end"
@endtask

@task('link-current')
    echo "link-current:start"

    ln -nfs {{ $staging_dir }} {{ $current_dir }}

    echo "link-current:end"
@endtask

@task('migrate')
    echo "migrate:start"

    cd {{ $current_dir }}

    @if($migrate)
        php artisan -q down --message="Database Matienence" --retry=60 > /dev/null

        php artisan -q migrate --env={{ $env }} --force > /dev/null

        php artisan -q up > /dev/null
    @else
        echo "migrate:skip"
    @endif

    echo "migrate:end"
@endtask

@task('purge-old')
    echo "purge-old:start"

    cd {{ $releases_dir }}
    to_purge=$(ls -tr | head -n -4)
    [ "$to_purge" != "" ] && rm -rf $to_purge

    echo "purge-old:end"
@endtask
