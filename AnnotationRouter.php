<?php

declare(strict_types=1);

namespace Leevel\Router;

use Leevel\Kernel\Utils\ClassParser;
use Leevel\Support\Arr\Normalize;
use Leevel\Support\Type\Arr;
use Symfony\Component\Finder\Finder;

/**
 * 注解路由.
 */
class AnnotationRouter
{
    /**
     * 路由中间件分析器.
     */
    protected MiddlewareParser $middlewareParser;

    /**
     * 扫描目录.
     */
    protected array $scanDirs = [];

    /**
     * 支持的方法.
     */
    protected static array $methods = [
        'get',
        'delete',
        'post',
        'put',
        'delete',
        'options',
        'head',
        'patch',
    ];

    /**
     * 支持的路由字段.
     */
    protected static array $routerField = [
        'attributes',
        'bind',
        'middlewares',
    ];

    /**
     * 匹配基础路径.
     */
    protected array $basePaths = [];

    /**
     * 匹配分组.
     */
    protected array $groups = [];

    /**
     * 控制器相对目录.
     *
     * - 斜杠分隔多层目录
     * - 目录风格
     */
    protected string $controllerDir = 'Controller';

    /**
     * 构造函数.
     */
    public function __construct(MiddlewareParser $middlewareParser, array $basePaths = [], array $groups = [])
    {
        $this->middlewareParser = $middlewareParser;

        if ($groups) {
            $this->groups = $this->parseGroups(array_keys($groups));
            foreach ($groups as $k => $v) {
                $basePaths[$k.'*'] = $v;
            }
        }

        if ($basePaths) {
            $this->basePaths = $this->parseBasePaths($basePaths);
        }
    }

    /**
     * 添加一个扫描目录.
     *
     * @throws \InvalidArgumentException
     */
    public function addScandir(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(sprintf('Annotation routing scandir %s is not exits.', $dir));
        }

        $this->scanDirs[] = $dir;
    }

    /**
     * 设置控制器相对目录.
     */
    public function setControllerDir(string $controllerDir): void
    {
        $controllerDir = str_replace('\\', '/', $controllerDir);
        $this->controllerDir = $controllerDir;
    }

    /**
     * 返回控制器相对目录.
     */
    public function getControllerDir(): string
    {
        return $this->controllerDir;
    }

    /**
     * 处理注解路由.
     */
    public function handle(): array
    {
        $routers = $this->parseControllerAnnotationRouters();
        $routers = $this->parseRouters($routers);
        $routers = $this->normalizeFastRoute($routers);

        return $this->packageRouters($routers);
    }

    /**
     * 查找控制器文件.
     */
    protected function findFiles(array $paths): Finder
    {
        $finder = (new Finder())
            ->in($paths)
            ->exclude(['vendor', 'node_modules'])
            ->followLinks()
            ->name('*.php')
            ->sortByName()
            ->files()
        ;
        if ($this->controllerDir) {
            $finder->path($this->controllerDir);
        }

        return $finder;
    }

    /**
     * 打包路由解析数据.
     */
    protected function packageRouters(array $routers): array
    {
        return [
            'base_paths' => $this->basePaths,
            'groups' => $this->groups,
            'routers' => $routers,
        ];
    }

    /**
     * 分析控制器注解路由.
     */
    protected function parseControllerAnnotationRouters(): array
    {
        $finder = $this->findFiles($this->scanDirs);
        $classParser = new ClassParser();
        $routers = [];
        foreach ($finder as $file) {
            // PHAR模式，getRealPath 总是返回为false，所以使用 getPathname
            $filePath = $file->getRealPath() ?: $file->getPathname();
            $content = file_get_contents($filePath) ?: '';
            if (str_contains($content, '#[')
                && preg_match('/\#\[\\s*Route\\s*\((.*)\)\\s*\]/s', $content)) {
                $controllerClassName = $classParser->handle($filePath);
                $this->parseEachControllerAnnotationRouters($routers, $controllerClassName);
            }
        }

        return $routers;
    }

    /**
     * 分析每一个控制器注解路由.
     *
     * @throws \Exception
     */
    protected function parseEachControllerAnnotationRouters(array &$routers, string $controllerClassName): void
    {
        /** @phpstan-ignore-next-line */
        $ref = new \ReflectionClass($controllerClassName);
        foreach ($ref->getMethods() as $method) {
            if ($routeAttributes = $method->getAttributes(Route::class)) {
                foreach ($routeAttributes as $routeAttribute) {
                    $router = $routeAttribute->getArguments();
                    if (empty($router['path'])) {
                        throw new \Exception(sprintf('The %s::%s() method annotation routing path cannot be empty.', $controllerClassName, $method->getName()));
                    }
                    $this->normalizeAnnotationRouterData($router, $controllerClassName.'@'.$method->getName());
                    $routers[$router['method']][] = $router;
                }
            }
        }
    }

    /**
     * 整理注解路由数据.
     */
    protected function normalizeAnnotationRouterData(array &$router, string $defaultControllerBind): void
    {
        if (empty($router['method'])) {
            $router['method'] = 'get';
        }
        $router['method'] = strtolower($router['method']);

        if (empty($router['bind'])) {
            $router['bind'] = $defaultControllerBind;
        }
        $router['bind'] = '\\'.trim($router['bind'], '\\');
    }

    /**
     * 解析路由.
     */
    protected function parseRouters(array $result): array
    {
        $routers = [];
        foreach ($result as $httpMethod => $items) {
            $this->parseHttpMethodAnnotationRouters($routers, $httpMethod, $items);
        }

        return $routers;
    }

    /**
     * 解析 HTTP 不同类型请求路由.
     */
    protected function parseHttpMethodAnnotationRouters(array &$routers, string $httpMethod, array $annotationRouters): void
    {
        if (!\in_array($httpMethod, static::$methods, true)) {
            return;
        }

        foreach ($annotationRouters as $router) {
            // 忽略特殊路由
            if ($this->isRouterIgnore($sourceRouterPath = $router['path'])) {
                continue;
            }

            // 支持的自定义路由字段
            $router = $this->parseRouterField($router);

            // 解析中间件
            $this->parseRouterMiddlewares($router);

            // 解析基础路径
            [$prefix, $groupPrefix, $routerPath] = $this->parseRouterPath($sourceRouterPath, $this->groups);

            // 解析路由正则
            if ($this->isStaticRouter($routerPath)) {
                ksort($router);
                $routers[$httpMethod]['static'][$routerPath] = $router;
            } else {
                $router = $this->parseRouterRegex($routerPath, $router);
                ksort($router);
                $routers[$httpMethod][$prefix][$groupPrefix][$routerPath] = $router;
            }
        }
    }

    /**
     * 判断是否为忽略路由.
     *
     * - 首页 `/` 默认提供 Home::index 需要过滤
     */
    protected function isRouterIgnore(string $path): bool
    {
        return '//' === $this->normalizePath($path);
    }

    /**
     * 解析自定义路由字段.
     */
    protected function parseRouterField(array $method): array
    {
        $result = [];
        foreach (static::$routerField as $f) {
            if (\array_key_exists($f, $method)) {
                $result[$f] = $method[$f];
            }
        }

        return $result;
    }

    /**
     * 解析基础路径和分组.
     *
     * - 基础路径如 /api/v1、/web/v2 等等.
     * - 分组例如 goods、orders.
     */
    protected function parseRouterPath(string $path, array $groups): array
    {
        $routerPath = $this->normalizePath($path);
        $groupPrefix = '_';
        foreach ($groups as $g) {
            if (str_starts_with($routerPath, $g)) {
                $groupPrefix = $g;

                break;
            }
        }

        return [$routerPath[1], $groupPrefix, $routerPath];
    }

    /**
     * 解析中间件.
     */
    protected function parseRouterMiddlewares(array &$router): void
    {
        if (!empty($router['middlewares'])) {
            $router['middlewares'] = $this->middlewareParser->handle(
                Normalize::handle($router['middlewares'])
            );
        }
    }

    /**
     * 是否为静态路由.
     */
    protected function isStaticRouter(string $router): bool
    {
        return !str_contains($router, '{');
    }

    /**
     * 解析路由正则.
     */
    protected function parseRouterRegex(string $path, array $router): array
    {
        [$router['regex'], $router['var']] = $this->ruleRegex($path);

        return $router;
    }

    /**
     * 格式化路径.
     */
    protected function normalizePath(string $path): string
    {
        return '/'.trim($path, '/').'/';
    }

    /**
     * 路由正则分组合并.
     */
    protected function normalizeFastRoute(array $routers): array
    {
        // 合并路由匹配规则提高匹配效率，10 个一分组
        // http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
        foreach ($routers as &$first) {
            foreach ($first as $firstKey => &$second) {
                if ('static' === $firstKey) {
                    continue;
                }

                foreach ($second as &$three) {
                    $groups = $this->parseToGroups($three);
                    foreach ($groups as $groupKey => $groupThree) {
                        [$three['regex'][$groupKey], $three['map'][$groupKey]] =
                            $this->parseGroupRegex($groupThree);
                    }
                }
            }
        }

        return $routers;
    }

    /**
     * 将路由进行分组.
     */
    protected function parseToGroups(array &$routers): array
    {
        $groups = [];
        $groupIndex = 0;
        foreach ($routers as $key => &$item) {
            $groups[(int) ($groupIndex / 10)][$key] = $item;
            unset($item['regex']);
            ++$groupIndex;
        }

        return $groups;
    }

    /**
     * 解析分组路由正则.
     */
    protected function parseGroupRegex(array $routers): array
    {
        $minCount = $this->computeMinCountVar($routers);
        $regex = $ruleMap = [];
        $ruleKey = 0;
        $regex[] = '~^(?';
        foreach ($routers as $key => $router) {
            $countVar = $minCount + $ruleKey;
            $emptyMatche = $countVar - \count($router['var']);
            $ruleMap[$countVar + 1] = $key;
            $regex[] = '|'.$router['regex'].($emptyMatche ? str_repeat('()', $emptyMatche) : '');
            ++$ruleKey;
        }
        $regex[] = ')$~x';

        return [implode('', $regex), $ruleMap];
    }

    /**
     * 计算初始最低的增长变量数量.
     */
    protected function computeMinCountVar(array $routers): int
    {
        $minCount = 1;
        foreach ($routers as $item) {
            if (($curCount = \count($item['var'])) > $minCount) {
                $minCount = $curCount;
            }
        }

        return $minCount;
    }

    /**
     * 格式化正则.
     */
    protected function ruleRegex(string $rule): array
    {
        $routerVar = [];
        $mapRegex = [
            'find' => [],
            'replace' => [],
        ];

        $rule = (string) preg_replace_callback('/{(.+?)}/', function ($matches) use (&$routerVar, &$mapRegex) {
            if (str_contains($matches[1], ':')) {
                $colonPos = (int) strpos($matches[1], ':');
                $routerVar[] = substr($matches[1], 0, $colonPos);
                $regex = substr($matches[1], $colonPos + 1);
            } else {
                $routerVar[] = $matches[1];
                $regex = IRouter::DEFAULT_REGEX;
            }

            $regex = '('.$regex.')';
            $regexEncode = '`'.md5($regex).'`';
            $mapRegex['find'][] = $regexEncode;
            $mapRegex['replace'][] = $regex;

            return $regexEncode;
        }, $rule);

        $rule = preg_quote($rule);

        if ($mapRegex['find']) {
            $rule = str_replace($mapRegex['find'], $mapRegex['replace'], $rule);
        }

        return [$rule, $routerVar];
    }

    /**
     * 分析基础路径.
     *
     * @throws \InvalidArgumentException
     */
    protected function parseBasePaths(array $basePathsSource): array
    {
        if (!Arr::handle($basePathsSource, ['string:array'])) {
            throw new \InvalidArgumentException('Router base paths must be array:string:array.');
        }

        $basePaths = [];
        foreach ($basePathsSource as $key => $value) {
            if (!empty($value['middlewares'])) {
                $value['middlewares'] = $this->middlewareParser->handle(
                    Normalize::handle($value['middlewares'])
                );
            }

            $this->filterBasePath($value);
            if (empty($value)) {
                continue;
            }

            // 值为 * 表示所有路径，其它带有的 * 为通配符
            $key = (string) $key;
            $key = '*' !== $key ? '/'.trim($key, '/') : $key;
            $key = '*' === $key ? '*' : $this->prepareRegexForWildcard($key.'/');

            $basePaths[$key] = $value;
        }

        return $basePaths;
    }

    /**
     * 过滤基础路径数据.
     */
    protected function filterBasePath(array &$basePath): void
    {
        if (empty($basePath)) {
            return;
        }

        if (isset($basePath['middlewares'])) {
            if (empty($basePath['middlewares']['handle'])) {
                unset($basePath['middlewares']['handle']);
            }
            if (empty($basePath['middlewares']['terminate'])) {
                unset($basePath['middlewares']['terminate']);
            }
        }

        if (empty($basePath['middlewares'])) {
            unset($basePath['middlewares']);
        }
    }

    /**
     * 分析分组标签.
     */
    protected function parseGroups(array $groupsSource): array
    {
        return array_map(fn (string $v): string => '/'.ltrim($v, '/'), $groupsSource);
    }

    /**
     * 通配符正则.
     */
    protected function prepareRegexForWildcard(string $regex): string
    {
        $regex = preg_quote($regex, '/');

        return '/^'.str_replace('\*', '(\S*)', $regex).'$/';
    }
}
