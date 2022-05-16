<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Deployer\Task\Common;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use Symfony\Component\Process\Exception\ProcessFailedException;

use function Deployer\get;
use function Deployer\runLocally;
use function Deployer\set;
use function Deployer\task;
use function Deployer\testLocally;
use function Deployer\writeln;

class PrepareSshTaskGlobal implements TaskInterface
{
    private const BITBUCKET_KEY_PATH = '/opt/atlassian/pipelines/agent/ssh/id_rsa';

    public function configure(Configuration $config): void
    {
        set('ssh_key_file', function () {
            if (testLocally('[ -f ' . self::BITBUCKET_KEY_PATH . ' ]')) {
                return self::BITBUCKET_KEY_PATH;
            }
            return '~/.ssh/id_rsa';
        });

        task('prepare:ssh', function () {
            $this->configureKey();

            if (testLocally('ssh-add -l | grep -q "no identities"')) {
                try {
                    runLocally('ssh-add -k {{ssh_key_file}}');
                } catch (ProcessFailedException $e) {
                    writeln('Failed to add key to ssh agent.');
                    writeln('Trying key {{ssh_key_file}}');
                    try {
                        $keyMd5 = runLocally('md5sum {{ssh_key_file}}');
                        writeln("With MD5 $keyMd5");
                    } catch (ProcessFailedException $e) {
                        writeln('Failed to get keyfile MD5 ' . $e);
                    }
                    throw $e;
                }
            }

            writeln('Listing loaded SSH keys in MD5 format');
            writeln(runLocally('ssh-add -l -E md5'));

            writeln('Listing loaded SSH keys in SHA256 format');
            writeln(runLocally('ssh-add -l -E sha256'));
        });
    }

    /**
     * Initialize private key if set
     */
    private function configureKey(): void
    {
        $key = \getenv('SSH_PRIVATE_KEY');
        if ($key === false) {
            return;
        }

        if (get('ssh_key_file') === self::BITBUCKET_KEY_PATH) {
            writeln('!!!!! WARNING !!!!! Not using private variable SSH_PRIVATE_KEY in Bitbucket configuration. Use the SSH key configuration from Bitbucket.');
            return;
        }

        if (strpos($key, 'BEGIN RSA PRIVATE KEY') === false) {
            runLocally('echo "$SSH_PRIVATE_KEY" | base64 -d > {{ssh_key_file}}');
        } else {
            runLocally('echo "$SSH_PRIVATE_KEY" > {{ssh_key_file}}');
        }

        runLocally('chmod 600 ~/.ssh/id_rsa');
    }
}
