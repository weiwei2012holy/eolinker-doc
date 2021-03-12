<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-06-10,22:56
 */

namespace Weiwei2012holy\EolinkerDoc;

use Illuminate\Support\Arr;
use Weiwei2012holy\EolinkerDoc\Exceptions\EolinkerException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Weiwei2012holy\EolinkerDoc\Models\EoApi;
use Weiwei2012holy\EolinkerDoc\Models\EoApiRequestParam;
use Weiwei2012holy\EolinkerDoc\Models\EoConnProject;
use Weiwei2012holy\EolinkerDoc\Models\EoProject;
use Weiwei2012holy\EolinkerDoc\Models\EoUser;

error_reporting(E_ALL ^ E_NOTICE);

class ApiDocGenerateTool
{
    protected $userID;

    protected $project;


    /**
     * ApiDocGenerateTool constructor.
     *
     * @param string $account eolikner账户名称
     * @param int    $project 项目id,看接口文档地址栏projectID参数
     *
     * @return ApiDocGenerateTool
     * @throws EolinkerException
     */
    public function init(string $account, int $project)
    {
        if (!$user = $this->getUser($account)) {
            throw new EolinkerException('该账户不存在');
        }
        $this->checkoutAuth($user->getKey(), $project);
        $this->userID = $user->getKey();
        $this->project = $project;
        return $this;
    }


    /**
     * 校验当前用户是否可以操作该项目
     *
     * @param int $userID
     * @param int $projectID
     *
     * @return bool
     * @throws EolinkerException
     */
    protected function checkoutAuth(int $userID, int $projectID)
    {
        if (!in_array($projectID, $this->getUserProject($userID)->pluck('projectID')->toArray())) {
            throw new EolinkerException('caution !!! 正在操作非法的项目!');
        }
        return true;
    }

    /**
     * 获取用户可操作的项目,避免有人乱写
     *
     * @param int $userID
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    protected function getUserProject(int $userID)
    {
        return EoProject::query()
            ->whereIn('projectID', EoConnProject::query()
                ->where('userID', $userID)
                ->pluck('projectID'))->get();
    }


    /**
     * 获取用户信息
     *
     * @param string $account
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|EoUser|null
     */
    protected function getUser(string $account)
    {
        return EoUser::query()->where('userName', $account)->first();
    }

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
                //                'middleware' => $this->getMiddleware($route),
            ];
            if (
                isset($filters['name']) && !Str::contains($route['name'], $filters['name']) ||
                isset($filters['path']) && !Str::contains($route['uri'], $filters['path']) ||
                isset($filters['uri']) && !Str::contains($route['uri'], $filters['uri']) ||
                isset($filters['action']) && !Str::contains($route['action'], $filters['action']) ||
                //                isset($filters['middleware']) && !Str::contains($route['middleware'], $filters['middleware']) ||
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
    protected function parseDoc($action)
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
     * 生成文档
     *
     * @param array $api
     *
     * @return  bool
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function createEoliknerDoc(array $api)
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
        //处理目录，只支持三级目录
        $parseApiName = array_filter(explode('.', $api['name']));
        $levelDir = 4;
        if (count($parseApiName) > $levelDir) {
            $apiGroupInfo = array_slice($parseApiName, 0, $levelDir);
            $apiGroupInfo[$levelDir - 1] .= '.' . implode('.', array_slice($parseApiName, $levelDir));
        } else {
            $apiGroupInfo = $parseApiName;
        }

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
        $group = $eolikner->createGroup($apiGroupInfo, $this->project);
        $apiInfo['projectID'] = $this->project;
        $apiInfo['apiURI'] = $api['uri'];
        $apiInfo['apiRequestType'] = $requestMethod[$method];
        $apiInfo['groupID'] = $group->groupID;
        $apiInfo['apiStatus'] = $apiStatus[EoApi::ENUM_VALUE];
        $apiInfo['apiProtocol'] = 1;//请求协议 0=http,1=https

        if ($parseInfo['api_success_example']['content']) {
            if ($parseInfo['api_success_example']['type'] == 'json') {
                $apiInfo['apiSuccessMock'] = $parseInfo['api_success_example']['content_json'];
                $apiInfo['apiSuccessMockType'] = 1;
            } else {
                $apiInfo['apiSuccessMock'] = $parseInfo['api_success_example']['content'];
                $apiInfo['apiSuccessMockType'] = 0;
            }
        }

        if ($parseInfo['api_error_example']['content']) {
            if ($parseInfo['api_error_example']['type'] == 'json') {
                $apiInfo['apiErrorMock'] = $parseInfo['api_error_example']['content_json'];
                $apiInfo['apiErrorMockType'] = 1;
            } else {
                $apiInfo['apiErrorMock'] = $parseInfo['api_error_example']['content'];
                $apiInfo['apiErrorMockType'] = 0;
            }
        }

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
                $requestParam['paramKey'] = $this->formatKey($param['field']);
                $requestParam['apiID'] = $apiRes->apiID;
                $requestParam['paramValue'] = $param['default_value'];
                $requestParam['paramType'] = $eolikner->parseParamType($param['type']);//参数类型
                $requestParam['paramLimit'] = $param['size'] ? 'size:' . $param['size'] : '';
                $requestParam['paramNotNull'] = $param['optional'] ? 1 : 0;//参数类型
                if ($param['model']) {
                    $pClass = get_parent_class($param['model']);
                    switch ($pClass) {
                        case 'Wxa\Fast\Enums\Enum';
                            $requestParam['paramName'] = $param['description'] ?: $param['field'];
                            $requestParam['paramName'] = $this->formatEnum($param['model'], $requestParam['paramName']);
                            break;
                        //                        case Model::class;
                        case (new $pClass) instanceof Model;
                            $fKey = $requestParam['paramKey'];
                            $model = new $param['model'];
                            $cols = Arr::dot($model->getTableFullColumns());
                            $modelKeyOwner = [];
                            $modelParam = [
                                'apiID' => $requestParam['apiID'],
                                'paramLimit' => $requestParam['paramLimit'],
                                'paramNotNull' => $requestParam['paramNotNull'],
                                'paramType' => EoApiRequestParam::PARAM_TYPE_STRING[EoApiRequestParam::ENUM_VALUE],
                                'paramValue' => $requestParam['paramValue']
                            ];
                            foreach ($cols as $field => $desc) {
                                //暂时不支持值类型
                                $requestParam['paramType'] = EoApiRequestParam::PARAM_TYPE_STRING[EoApiRequestParam::ENUM_VALUE];
                                //限定字段,只需要获取指定字段即可
                                if ($param['model_field'] && !in_array($field, $param['model_field'])) {
                                    continue;
                                }
                                $explode = explode('.', $field);
                                $explodeTotal = count($explode);
                                //处理数组
                                if ($explodeTotal > 1) {
                                    $temp = [];
                                    for ($i = 0; $i < $explodeTotal - 1; $i++) {
                                        $temp[] = $explode[$i];
                                        $newKey = implode('.', $temp);
                                        if (!in_array($newKey, $modelKeyOwner)) {
                                            $newKeyFull = $fKey . '.' . $newKey;
                                            $modelKeyOwner[] = $newKeyFull;
                                            $modelParam['paramKey'] = $this->formatKey($newKeyFull);
                                            $modelParam['paramName'] = '';
                                            $modelParam['paramType'] = EoApiRequestParam::PARAM_TYPE_OBJ[EoApiRequestParam::ENUM_VALUE];
                                            $eolikner->createRequestParam($modelParam);
                                        }
                                    }
                                }
                                $modelParam['paramKey'] = $this->formatKey($fKey . '.' . $field);
                                $modelParam['paramName'] = $desc;
                                $eolikner->createRequestParam($modelParam);
                            }
                            break;
                        default:
                            throw new \Exception('un support model type:' . $pClass);

                    }
                }

                $eolikner->createRequestParam($requestParam);
            }
        }
        //处理返回值
        if ($parseInfo['success']) {
            foreach ($parseInfo['success'] as $param) {
                $resultParam['paramKey'] = $this->formatKey($param['field']);
                $resultParam['paramName'] = ($param['description'] ?: $param['field']) . ($param['allowed_values'] ? ',可选值:' . $param['allowed_values'] : '') . ',类型:' . $param['type'];
                $resultParam['apiID'] = $apiRes->apiID;
                $resultParam['paramNotNull'] = $param['optional'] ? 1 : 0;//参数类型
                //如果绑定了model 批量插入

                if ($param['model']) {
                    $pClass = get_parent_class($param['model']);
                    switch ($pClass) {
                        case 'Wxa\Fast\Enums\Enum';
                            $resultParam['paramName'] = $param['description'] ?: $param['field'];
                            $resultParam['paramName'] = $this->formatEnum($param['model'], $resultParam['paramName']);
                            break;
                        //                        case Model::class;
                        case (new $pClass) instanceof Model;
                            $fKey = $resultParam['paramKey'];
                            $model = new $param['model'];
                            $cols = Arr::dot($model->getTableFullColumns());
                            $modelKeyOwner = [];
                            $resultModelParam = [
                                'apiID' => $resultParam['apiID'],
                                'paramNotNull' => $resultParam['paramNotNull']
                            ];
                            foreach ($cols as $field => $desc) {
                                //限定字段,只需要获取指定字段即可
                                if ($param['model_field'] && !in_array($field, $param['model_field'])) {
                                    continue;
                                }
                                $explode = explode('.', $field);
                                $explodeTotal = count($explode);
                                //处理数组
                                if ($explodeTotal > 1) {
                                    $temp = [];
                                    for ($i = 0; $i < $explodeTotal - 1; $i++) {
                                        $temp[] = $explode[$i];
                                        $newKey = implode('.', $temp);
                                        if (!in_array($newKey, $modelKeyOwner)) {
                                            $newKeyFull = $fKey . '.' . $newKey;
                                            $modelKeyOwner[] = $newKeyFull;
                                            $resultModelParam['paramKey'] = $this->formatKey($newKeyFull);
                                            $resultModelParam['paramName'] = '';
                                            $eolikner->createResultParam($resultModelParam);
                                        }
                                    }
                                }
                                $resultModelParam['paramKey'] = $this->formatKey($fKey . '.' . $field);
                                $resultModelParam['paramName'] = $desc;
                                $eolikner->createResultParam($resultModelParam);
                            }
                            break;
                        default:
                            throw new \Exception('un support model type:' . $pClass);


                    }
                }
                $eolikner->createResultParam($resultParam);
            }
        }
        //生成缓存
        $eolikner->createCache($apiRes->apiID);
        return true;
    }

    /**
     * 格式化输出枚举
     *
     * @param string $className
     * @param string $paramName
     *
     * @return string
     */
    protected function formatEnum(string $className, string $paramName)
    {
        $values = $className::options();
        $paramName .= '(可选值:';
        foreach ($values as $v => $d) {
            $paramName .= $v . '=' . $d . ',';
        }
        $paramName = substr($paramName, 0, -1);
        $paramName .= ')';
        return $paramName;
    }

    /**
     * 将.语法转换成eolikner对象语法>>
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function formatKey(string $key)
    {
        return str_replace('.', '>>', $key);
    }


    /**
     * 创建状态码
     *
     * @param int   $groupName
     * @param array $codeData
     *
     * @return bool
     */
    public function createCode($groupName, array $codeData)
    {
        $tool = new ApiEoLinkerTool();
        $codeGroup = $tool->createProjectStatusCodeGroup($groupName, $this->project);
        foreach ($codeData as $code => $desc) {
            $tool->createProjectStatusCode($code, $desc, $codeGroup->getKey());
        }
        return true;
    }

}
