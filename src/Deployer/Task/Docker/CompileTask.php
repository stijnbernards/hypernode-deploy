<?php

namespace Hypernode\Deploy\Deployer\Task\Docker;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use function Deployer\run;
use function Deployer\task;

class CompileTask implements TaskInterface
{
    /**
     * @var ImageNameHelper
     */
    private $imageHelper;

    /**
     * @param ImageNameHelper $imageHelper
     */
    public function __construct(ImageNameHelper $imageHelper)
    {
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param Configuration $config
     */
    public function configure(Configuration $config)
    {
        task('docker:compile', function () use ($config) {
            $this->build('build/Dockerfile.php', $this->imageHelper->getDockerImage($config, 'php'));
            $this->build('build/Dockerfile.nginx', $this->imageHelper->getDockerImage($config, 'nginx'));
        })->onStage('build');
    }

    /**
     * @param string $file
     * @param string $tag
     */
    private function build(string $file, string $tag): void
    {
        $file = escapeshellarg($file);
        $tag = escapeshellarg($tag);
        run("docker build --pull -f {$file} -t {$tag} ./build", ['timeout' => 3600]);
    }
}
