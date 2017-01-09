@servers(['web' => '127.0.0.1'])

@setup
    if(!isset($path)) {
        throw new Exception('Please specify a --path');
    }

    if(!isset($repo)) {
        throw new Exception('Please specify a --repo');
    }

    $stage_hash = md5(date('YmdHis'));

    // directories
    $base_dir = rtrim($path, '/');
    $current_dir = $base_dir . '/current';
    $releases_dir = $base_dir . '/releases';
    $staging_dir = $releases_dir . '/' . $stage_hash;
@endsetup

@macro('deploy')
    prep
    update-perms
    link-current
    purge-old
@endmacro

@task('prep')
    echo "prep:start"

    # Create directories and files that may not exists.
    [ ! -d {{ $base_dir }} ] && mkdir -p {{ $base_dir }}
    [ ! -d {{ $releases_dir }} ] && mkdir -p {{ $releases_dir }}
    [ ! -d {{ $storage_dir }} ] && mkdir -p {{ $storage_dir }}

    # Clone the repo
    git clone {{ $repo }} -q --depth 1 {{ $staging_dir }}

    echo "prep:end"
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

@task('purge-old')
    echo "purge-old:start"

    cd {{ $releases_dir }}
    $to_purge = $(ls -tr | head -n -4)
    [ -z $to_purge ] && echo $to_purge

    echo "purge-old:end"
@endtask
