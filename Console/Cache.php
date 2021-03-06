<?php

declare(strict_types=1);

namespace Leevel\Router\Console;

use Leevel\Console\Command;
use Leevel\Filesystem\Helper\create_file;
use function Leevel\Filesystem\Helper\create_file;
use Leevel\Kernel\IApp;
use Leevel\Router\RouterProvider;

/**
 * openapi 路由缓存.
 */
class Cache extends Command
{
    /**
     * 命令名字.
    */
    protected string $name = 'router:cache';

    /**
     * 命令行描述.
    */
    protected string $description = 'Annotations as the router';

    /**
     * 响应命令.
     */
    public function handle(IApp $app, RouterProvider $routerProvider): int
    {
        $this->line('Start to cache router.');
        $data = $routerProvider->getRouters();
        $cachePath = $app->routerCachedPath();
        $this->writeCache($cachePath, $data);
        $this->info(sprintf('Router cache successed at %s.', $cachePath));

        return 0;
    }

    /**
     * 写入缓存.
     */
    protected function writeCache(string $cachePath, array $data): void
    {
        $content = '<?php /* '.date('Y-m-d H:i:s').' */ ?>'.
            PHP_EOL.'<?php return '.var_export($data, true).'; ?>';
        create_file($cachePath, $content);
    }
}

// import fn.
class_exists(create_file::class);
