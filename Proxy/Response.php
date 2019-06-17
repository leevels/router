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
 * (c) 2010-2019 http://queryphp.com All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Leevel\Router\Proxy;

use Leevel\Di\Container;
use Leevel\Http\ApiResponse;
use Leevel\Http\FileResponse;
use Leevel\Http\JsonResponse;
use Leevel\Http\RedirectResponse;
use Leevel\Http\Response as BaseResponse;
use Leevel\Router\IResponse as IBaseResponse;
use Leevel\Router\Response as RouterResponse;

/**
 * 代理 response.
 *
 * @author Xiangmin Liu <635750556@qq.com>
 *
 * @since 2017.06.10
 *
 * @version 1.0
 * @codeCoverageIgnore
 */
class Response implements IResponse
{
    /**
     * call.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        return self::proxy()->{$method}(...$args);
    }

    /**
     * 返回一个响应.
     *
     * @param mixed $content
     * @param int   $status
     * @param array $headers
     *
     * @return \Leevel\Http\Response
     */
    public static function make($content = '', $status = 200, array $headers = []): BaseResponse
    {
        return self::proxy()->make($content, $status, $headers);
    }

    /**
     * 返回视图响应.
     *
     * @param string      $file
     * @param array       $vars
     * @param null|string $ext
     * @param int         $status
     * @param array       $headers
     *
     * @return \Leevel\Http\Response
     */
    public static function view(string $file, array $vars = [], ?string $ext = null, int $status = 200, array $headers = []): BaseResponse
    {
        return self::proxy()->view($file, $vars, $ext, $status, $headers);
    }

    /**
     * 返回视图成功消息.
     *
     * @param string $message
     * @param string $url
     * @param int    $time
     * @param int    $status
     * @param array  $headers
     *
     * @return \Leevel\Http\Response
     */
    public static function viewSuccess(string $message, string $url = '', int $time = 1, int $status = 200, array $headers = []): BaseResponse
    {
        return self::proxy()->viewSuccess($message, $url, $time, $status, $headers);
    }

    /**
     * 返回视图失败消息.
     *
     * @param string $message
     * @param string $url
     * @param int    $time
     * @param int    $status
     * @param array  $headers
     *
     * @return \Leevel\Http\Response
     */
    public static function viewFail(string $message, string $url = '', int $time = 3, int $status = 404, array $headers = []): BaseResponse
    {
        return self::proxy()->viewFail($message, $url, $time, $status, $headers);
    }

    /**
     * 返回 JSON 响应.
     *
     * @param null|mixed $data
     * @param int        $status
     * @param array      $headers
     * @param bool       $json
     *
     * @return \Leevel\Http\JsonResponse
     */
    public static function json($data = null, int $status = 200, array $headers = [], bool $json = false): JsonResponse
    {
        return self::proxy()->json($data, $status, $headers, $json);
    }

    /**
     * 返回 JSONP 响应.
     *
     * @param string     $callback
     * @param null|mixed $data
     * @param int        $status
     * @param array      $headers
     * @param bool       $json
     *
     * @return \Leevel\Http\JsonResponse
     */
    public static function jsonp(string $callback, $data = null, int $status = 200, array $headers = [], bool $json = false): JsonResponse
    {
        return self::proxy()->jsonp($callback, $data, $status, $headers, $json);
    }

    /**
     * 返回下载响应.
     *
     * @param \SplFileInfo|\SplFileObject|string $file
     * @param null|string                        $name
     * @param int                                $status
     * @param array                              $headers
     * @param bool                               $autoEtag
     * @param bool                               $autoLastModified
     *
     * @return \Leevel\Http\FileResponse
     */
    public static function download($file, ?string $name = null, int $status = 200, array $headers = [], bool $autoEtag = false, bool $autoLastModified = true): FileResponse
    {
        return self::proxy()->download($file, $name, $status, $headers, $autoEtag, $autoLastModified);
    }

    /**
     * 返回文件响应.
     *
     * @param \SplFileInfo|\SplFileObject|string $file
     * @param int                                $status
     * @param array                              $headers
     * @param bool                               $autoEtag
     * @param bool                               $autoLastModified
     *
     * @return \Leevel\Http\FileResponse
     */
    public static function file($file, int $status = 200, array $headers = [], bool $autoEtag = false, bool $autoLastModified = true): FileResponse
    {
        return self::proxy()->file($file, $status, $headers, $autoEtag, $autoLastModified);
    }

    /**
     * 返回一个 URL 生成跳转响应.
     *
     * @param string           $url
     * @param array            $params
     * @param string           $subdomain
     * @param null|bool|string $suffix
     * @param int              $status
     * @param array            $headers
     *
     * @return \Leevel\Http\RedirectResponse
     */
    public static function redirect(string $url, array $params = [], string $subdomain = 'www', $suffix = null, int $status = 302, array $headers = []): RedirectResponse
    {
        return self::proxy()->redirect($url, $params, $subdomain, $suffix, $status, $headers);
    }

    /**
     * 返回一个跳转响应.
     *
     * @param string $url
     * @param int    $status
     * @param array  $headers
     *
     * @return \Leevel\Http\RedirectResponse
     */
    public static function redirectRaw(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return self::proxy()->redirectRaw($url, $status, $headers);
    }

    /**
     * 请求成功
     * 一般用于GET与POST请求: 200.
     *
     * @param mixed       $content
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiOk($content = '', ?string $text = null): ApiResponse
    {
        return self::proxy()->apiOk($content, $text);
    }

    /**
     * 已创建
     * 成功请求并创建了新的资源: 201.
     *
     * @param null|string $location
     * @param mixed       $content
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiCreated(?string $location = '', $content = ''): ApiResponse
    {
        return self::proxy()->apiCreated($location, $content);
    }

    /**
     * 已接受
     * 已经接受请求，但未处理完成: 202.
     *
     * @param null|string $location
     * @param mixed       $content
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiAccepted(?string $location = null, $content = ''): ApiResponse
    {
        return self::proxy()->apiAccepted($location, $content);
    }

    /**
     * 无内容
     * 服务器成功处理，但未返回内容: 204.
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiNoContent(): ApiResponse
    {
        return self::proxy()->apiNoContent();
    }

    /**
     * 错误请求
     * 服务器不理解请求的语法: 400.
     *
     * @param string      $message
     * @param int         $statusCode
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiError(string $message, int $statusCode, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiError($message, $statusCode, $text);
    }

    /**
     * 错误请求
     * 服务器不理解请求的语法: 400.
     *
     * @param null|string $message
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiBadRequest(?string $message = null, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiBadRequest($message, $text);
    }

    /**
     * 未授权
     * 对于需要登录的网页，服务器可能返回此响应: 401.
     *
     * @param null|string $message
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiUnauthorized(?string $message = null, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiUnauthorized($message, $text);
    }

    /**
     * 禁止
     * 服务器拒绝请求: 403.
     *
     * @param null|string $message
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiForbidden(?string $message = null, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiForbidden($message, $text);
    }

    /**
     * 未找到
     * 用户发出的请求针对的是不存在的记录: 404.
     *
     * @param null|string $message
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiNotFound(?string $message = null, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiNotFound($message, $text);
    }

    /**
     * 方法禁用
     * 禁用请求中指定的方法: 405.
     *
     * @param null|string $message
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiMethodNotAllowed(?string $message = null, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiMethodNotAllowed($message, $text);
    }

    /**
     * 无法处理的实体
     * 请求格式正确，但是由于含有语义错误，无法响应: 422.
     *
     * @param null|array  $errors
     * @param null|string $message
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiUnprocessableEntity(?array $errors = null, ?string $message = null, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiUnprocessableEntity($errors, $message, $text);
    }

    /**
     * 太多请求
     * 用户在给定的时间内发送了太多的请求: 429.
     *
     * @param null|string $message
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiTooManyRequests(?string $message = null, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiTooManyRequests($message, $text);
    }

    /**
     * 服务器内部错误
     * 服务器遇到错误，无法完成请求: 500.
     *
     * @param null|string $message
     * @param null|string $text
     *
     * @return \Leevel\Http\ApiResponse
     */
    public static function apiInternalServerError(?string $message = null, ?string $text = null): ApiResponse
    {
        return self::proxy()->apiInternalServerError($message, $text);
    }

    /**
     * 设置视图正确模板
     *
     * @param string $template
     *
     * @return \Leevel\Router\Response
     */
    public static function setViewSuccessTemplate(string $template): IBaseResponse
    {
        return self::proxy()->setViewSuccessTemplate($template);
    }

    /**
     * 设置视图错误模板
     *
     * @param string $template
     *
     * @return \Leevel\Router\Response
     */
    public static function setViewFailTemplate(string $template): IBaseResponse
    {
        return self::proxy()->setViewFailTemplate($template);
    }

    /**
     * 代理服务
     *
     * @return \Leevel\Router\Response
     */
    public static function proxy(): RouterResponse
    {
        return Container::singletons()->make('response');
    }
}
