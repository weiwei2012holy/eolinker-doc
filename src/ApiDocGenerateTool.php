<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-06-10,22:56
 */

namespace Weiwei2012holy\EolinkerDoc\Models;

use Weiwei2012holy\EolinkerDoc\Models\EoApi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

error_reporting(E_ALL ^ E_NOTICE);

class ApiDocGenerateTool
{
    /**
     * 获取路由列表
     * $filters['name'] = (string)筛选路由名称
     * $filters['path'] = (string)筛选路由路径
     * $filters['method'] = (string)筛选路由请求方法
     * $filters['middleware'] = (string)筛选路由中间件
     * $filters['uri'] = (string)筛选uri
     *
     * @param array $filters
     *
     * @return array
     */
    public function filterRoute(array $filters)
    {
        if (!$routes = Route::getRoutes()) {
            return [];
        }
        $list = [];
        foreach ($routes as $route) {
            $route = [
                'host' => $route->domain(),
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => ltrim($route->getActionName(), '\\'),
                'middleware' => $this->getMiddleware($route),
            ];
            if (
                isset($filters['name']) && !Str::contains($route['name'], $filters['name']) ||
                isset($filters['path']) && !Str::contains($route['uri'], $filters['path']) ||
                isset($filters['uri']) && !Str::contains($route['uri'], $filters['uri']) ||
                isset($filters['action']) && !Str::contains($route['action'], $filters['action']) ||
                isset($filters['middleware']) && !Str::contains($route['middleware'], $filters['middleware']) ||
                isset($filters['method']) && !Str::contains($route['method'], strtoupper($filters['method']))
            ) {
                continue;
            }
            $list[] = $route;
        }
        return $list;
    }

    /**
     * @param $route
     *
     * @return string
     */
    protected function getMiddleware($route)
    {
        return collect($route->gatherMiddleware())->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode(',');
    }


    /**
     * @param $action
     *
     * @return string|string[]|null
     * @throws \ReflectionException
     */
    public function parseDoc($action)
    {
        list($className, $fn) = explode('@', $action);
        $class = new \ReflectionClass($className);
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            if ($method->class != $className) {
                continue;
            }
            if ($method->name == $fn) {
                $doc = $method->getDocComment();
                return (new ApiDocParseTool())->parseDoc($doc);
            }
        }
    }


    /**
     * @param array $api
     * @param int   $projectId
     *
     * @throws Exception
     * @throws \App\Exceptions\IllegalDataFieldException
     * @throws \ReflectionException
     * @return  bool
     */
    public function createEoliknerDoc(array $api, int $projectId = 107)
    {
        $eolikner = new ApiEoLinkerTool();
        $requestMethod = EoApi::enumApiRequestType(false);
        $requestMethod = array_combine(array_column($requestMethod, 'name'), array_column($requestMethod, 'value'));

        $level = substr_count($api['name'], '.');

        if ($level < 1) {
            throw new \Exception('接口需要缺少name,命名规则:应用.模块.功能');
        }
        $method = strtolower(explode('|', $api['method'])[0] ?: 'post');

        $parseInfo = $this->parseDoc($api['action']);
        $apiGroupInfo = array_filter(explode('.', $api['name']));
        if ($level >= count($apiGroupInfo)) {
            throw new \Exception('接口需要缺少name');
        }
        //构造api
        $apiName = array_pop($apiGroupInfo);
        //        $apiName = $parseInfo['api_name'] ?: $apiName;
        //api/admin命名规则 标记为后台接口
        if (Str::contains($api['uri'], 'api/admin')) {
            $apiName = '后台-' . $apiName;
        }
        //获取api状态
        $apiStatus = EoApi::getApiStatus($parseInfo['status'] ?: '');
        $apiInfo['apiName'] = $apiStatus['emoji'] . $apiName;
        //创建层级菜单
        $group = $eolikner->createGroup($apiGroupInfo, $projectId);
        $apiInfo['projectID'] = $projectId;
        $apiInfo['apiURI'] = $api['uri'];
        $apiInfo['apiRequestType'] = $requestMethod[$method];
        $apiInfo['groupID'] = $group->groupID;
        $apiInfo['apiStatus'] = $apiStatus[EoApi::ENUM_VALUE];
        $apiInfo['apiProtocol'] = 1;//请求协议 0=http,1=https
        $apiInfo['apiFailureMock'] = '';
        $apiInfo['apiSuccessMock'] = '';
        $apiInfo['apiNote'] = "&lt;blockquote&gt;&lt;p&gt;最后更新时间:" . Carbon::now()->toDateTimeString() . "&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;" . $parseInfo['api_description'];//详细说明 文本
        $apiInfo['apiNoteRaw'] = '&gt; 最后更新时间:' . Carbon::now()->toDateTimeString() . PHP_EOL . PHP_EOL;
        $apiInfo['apiNoteRaw'] .= "#### 可用请求方式:" . PHP_EOL;
        foreach (explode('|', $api['method']) as $m) {
            $apiInfo['apiNoteRaw'] .= '- ' . $m . PHP_EOL;
        }
        $apiInfo['apiNoteRaw'] .= PHP_EOL . "#### 详细说明:" . PHP_EOL;
        $apiInfo['apiNoteRaw'] .= $parseInfo['api_description'];
        $apiInfo['apiNoteType'] = 1;//详细说明 文本
        //处理api数据
        $apiRes = $eolikner->saveApi($apiInfo);
        //处理请求参数
        if ($parseInfo['params']) {
            foreach ($parseInfo['params'] as $param) {
                $requestParam['paramName'] = ($param['description'] ?: $param['field']) . ($param['allowed_values'] ? ',可选值:' . $param['allowed_values'] : '') . ',类型:' . $param['type'];
                $requestParam['paramKey'] = str_replace('.', '>>', $param['field']);
                $requestParam['apiID'] = $apiRes->apiID;
                $requestParam['paramValue'] = $param['default_value'];
                $requestParam['paramType'] = $eolikner->parseParamType($param['type']);//参数类型
                $requestParam['paramLimit'] = $param['size'] ? 'size:' . $param['size'] : '';
                $requestParam['paramNotNull'] = $param['optional'] ? 1 : 0;//参数类型
                $eolikner->createRequestParam($requestParam);
                //如果绑定了model 批量插入
                if ($param['model']) {
                    //暂时不支持值类型
                    $requestParam['paramType'] = 0;
                    $fKey = $requestParam['paramKey'];
                    $model = new $param['model'];
                    $cols = $model->getTableFullColumns();
                    foreach ($cols as $field => $desc) {
                        //限定字段,只需要获取指定字段即可
                        if ($param['model_field'] && !in_array($field, $param['model_field'])) {
                            continue;
                        }
                        $requestParam['paramKey'] = $fKey . '>>' . $field;
                        $requestParam['paramName'] = $desc;
                        $eolikner->createRequestParam($requestParam);
                    }
                }
            }
        }
        //处理返回值
        if ($parseInfo['success']) {
            foreach ($parseInfo['success'] as $param) {
                $resultParam['paramKey'] = str_replace('.', '>>', $param['field']);
                $resultParam['paramName'] = ($param['description'] ?: $param['field']) . ($param['allowed_values'] ? ',可选值:' . $param['allowed_values'] : '') . ',类型:' . $param['type'];
                $resultParam['apiID'] = $apiRes->apiID;
                $resultParam['paramNotNull'] = $param['optional'] ? 1 : 0;//参数类型
                $eolikner->createResultParam($resultParam);
                //如果绑定了model 批量插入
                if ($param['model']) {
                    $fKey = $resultParam['paramKey'];
                    $model = new $param['model'];
                    $cols = $model->getTableFullColumns();
                    foreach ($cols as $field => $desc) {
                        //限定字段,只需要获取指定字段即可
                        if ($param['model_field'] && !in_array($field, $param['model_field'])) {
                            continue;
                        }
                        $resultParam['paramKey'] = $fKey . '>>' . $field;
                        $resultParam['paramName'] = $desc;
                        $eolikner->createResultParam($resultParam);
                    }
                }
            }
        }
        //生成缓存
        $eolikner->createCache($apiRes->apiID);
        return true;
    }

}
