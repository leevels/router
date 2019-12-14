<?php

declare(strict_types=1);

/*
 * This file is part of the ************************ package.
 * _____________                           _______________
 *  ______/     \__  _____  ____  ______  / /_  _________
 *   ____/ __   / / / / _ \/ __`\/ / __ \/ __ \/ __ \___
 *    __/ / /  / /_/ /  __/ /  \  / /_/ / / / / /_/ /__
 *      \_\ \_/\____/\___/_/   / / .___/_/ /_/ .___/
 *         \_\                /_/_/         /_/
 *
 * The PHP Framework For Code Poem As Free As Wind. <Query Yet Simple>
 * (c) 2010-2020 http://queryphp.com All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Leevel\Router\Match;

use Leevel\Http\IRequest;
use Leevel\Router\IRouter;

/**
 * 路由匹配抽象类.
 */
abstract class Match
{
    /**
     * Router.
     *
     * @var \Leevel\Router\IRouter
     */
    protected IRouter $router;

    /**
     * HTTP Request.
     *
     * @var \Leevel\Http\IRequest
     */
    protected IRequest $request;

    /**
     * 匹配中间件.
     *
     * @var array
     */
    protected array $middlewares = [];

    /**
     * 设置路由和请求.
     */
    protected function setRouterAndRequest(IRouter $router, IRequest $request): void
    {
        $this->request = $request;
        $this->router = $router;
    }

    /**
     * 匹配 PathInfo.
     */
    protected function matchePathInfo(): string
    {
        $pathInfo = $this->getPathInfo();

        // 匹配基础路径
        $middlewares = $this->matcheBasePaths($pathInfo);

        // 匹配分组路径
        list($pathInfo, $this->middlewares) = $this->matcheGroupPaths($pathInfo, $middlewares);

        return $pathInfo;
    }

    /**
     * 取得 PathInfo.
     */
    protected function getPathInfo(): string
    {
        return rtrim($this->request->getPathInfo(), '/').'/';
    }

    /**
     * 匹配基础路径.
     */
    protected function matcheBasePaths(string $pathInfo): array
    {
        $middlewares = [];
        foreach ($this->router->getBasePaths() as $item => $option) {
            if ('*' === $item) {
                if (isset($option['middlewares'])) {
                    $middlewares = $option['middlewares'];
                }
            } elseif (preg_match($item, $pathInfo, $matches)) {
                if (isset($option['middlewares'])) {
                    $middlewares = $this->mergeMiddlewares($middlewares, $option['middlewares']);
                }

                break;
            }
        }

        return $middlewares;
    }

    /**
     * 匹配分组路径.
     */
    protected function matcheGroupPaths(string $pathInfo, array $middlewares): array
    {
        foreach ($this->router->getGroupPaths() as $item => $option) {
            if (0 === strpos($pathInfo, $item)) {
                $pathInfo = substr($pathInfo, strlen($item));
                if (isset($option['middlewares'])) {
                    $middlewares = $this->mergeMiddlewares($middlewares, $option['middlewares']);
                }

                break;
            }
        }

        return [$pathInfo, $middlewares];
    }

    /**
     * 合并中间件.
     */
    protected function mergeMiddlewares(array $middlewares, array $newMiddlewares): array
    {
        return $this->router->mergeMiddlewares($middlewares, $newMiddlewares);
    }
}
