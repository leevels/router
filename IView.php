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

namespace Leevel\Router;

use Leevel\View\IView as IViews;

/**
 * IView 接口.
 */
interface IView
{
    /**
     * 切换视图.
     */
    public function switchView(IViews $view): void;

    /**
     * 变量赋值.
     */
    public function setVar(array|string $name, mixed $value = null): void;

    /**
     * 获取变量赋值.
     *
     * @return mixed
     */
    public function getVar(?string $name = null): mixed;

    /**
     * 删除变量值.
     */
    public function deleteVar(array $name): void;

    /**
     * 清空变量值.
     */
    public function clearVar(): void;

    /**
     * 加载视图文件.
     */
    public function display(string $file, array $vars = [], ?string $ext = null): string;
}
