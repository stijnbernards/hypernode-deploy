<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\run;
use function Deployer\task;
use function Deployer\test;

class LinkTask implements TaskInterface
{
    public function configure(Configuration $config): void
    {
        task('deploy:link', [
            'deploy:symlink',
            'deploy:public_link',
        ])->onRoles(ServerRole::APPLICATION);

        // Symlink public_html folder
        task('deploy:public_link', function () {
            if (test('[ -e /data/web/public ]')) {
                // If the current public directory is not empty we do nothing
                if (!test('[ -z "$(ls -A /data/web/public)" ]')) {
                    return;
                } else {
                    run('rmdir /data/web/public');
                }
            }

            run('ln -s {{deploy_path}}/current/{{public_folder}} /data/web/public');
        })->onRoles(ServerRole::APPLICATION);
    }
}