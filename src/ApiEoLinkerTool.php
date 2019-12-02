<?php
/**
 * Desc: api接口 对接eolinker
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-03-02,09:16
 */

namespace Weiwei2012holy\EolinkerDoc\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Weiwei2012holy\EolinkerDoc\Exceptions\EolinkerException;

class ApiEoLinkerTool
{


    /**
     * 更新api基本信息
     *
     * @param $data
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public function saveApi($data)
    {
        return EoApi::query()->updateOrCreate([
            'apiURI' => $data['apiURI'],
            'projectID' => $data['projectID'],
            'apiRequestType' => $data['apiRequestType'],
        ], $data);
    }


    /**
     * 创建分组
     *
     * @param array $group
     * @param int   $projectID
     *
     * @return mixed
     * @throws IllegalDataFieldException
     * @throws \Throwable
     */
    public function createGroup(array $group, int $projectID)
    {
        if (empty($group)) {
            throw new \Exception('缺少api接口分组信息');
        }
        return \DB::connection(config('eolinker.connection'))->transaction(function () use ($group, $projectID) {
            $pid = 0;
            $detail = [];
            foreach ($group as $k => $g) {
                $detail = EoApiGroup::query()->firstOrCreate(['groupName' => $g, 'projectID' => $projectID, 'parentGroupID' => $pid], ['isChild' => $k]);
                $pid = $detail->groupID;
            }
            return $detail;
        });
    }


    /**
     * 创建接口缓存
     *
     * @param $apiID
     *
     * @return bool|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public function createCache($apiID)
    {
        if (!$api = EoApi::query()->find($apiID)->toArray()) {
            return false;
        }
        $cacheJson['baseInfo'] = Arr::only($api, ['apiName', 'apiURI', 'apiProtocol', 'apiSuccessMock', 'apiFailureMock', 'apiRequestType', 'apiStatus', 'starred', 'apiNoteType', 'apiNoteRaw', 'apiNote', 'apiRequestParamType', 'apiRequestRaw', 'apiUpdateTime', 'apiFailureStatusCode', 'apiSuccessStatusCode', 'beforeInject', 'afterInject']);
        $cacheJson['headerInfo'] = [];
        $cacheJson['mockInfo'] = [];
        $cacheJson['requestInfo'] = EoApiRequestParam::query()->where('apiId', $apiID)->get()->toArray();
        $cacheJson['resultInfo'] = EoApiResultParam::query()->where('apiId', $apiID)->get()->toArray();

        $cacheJson = json_encode($cacheJson, 256);

        return EoApiCache::query()->updateOrCreate([
            'projectID' => $api['projectID'],
            'apiID' => $apiID,
        ], [
            'groupID' => $api['groupID'],
            'apiJson' => $cacheJson,
            'starred' => 0,
            'updateUserID' => 18
        ]);
    }

    /**
     * 创建请求参数
     *
     * @param $data
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public function createRequestParam($data)
    {
        return EoApiRequestParam::query()->updateOrCreate([
            'apiID' => $data['apiID'],
            'paramKey' => $data['paramKey']
        ], $data);
    }

    /**
     * 创建返回参数
     *
     * @param $data
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public function createResultParam($data)
    {
        return EoApiResultParam::query()->updateOrCreate([
            'apiID' => $data['apiID'],
            'paramKey' => $data['paramKey']
        ], $data);
    }

    /**
     * 转换apidoc参数类型值到eolikner
     *
     * @param string $type
     *
     * @return mixed
     */

    public function parseParamType(string $type = null)
    {
        $type = strtoupper($type);
        //默认字符串类型
        $typeValue = EoApiRequestParam::PARAM_TYPE_STRING[EoApiRequestParam::ENUM_VALUE];
        //有数组标记 直接返回数组类型
        if (Str::contains($type, '[]')) {
            return EoApiRequestParam::PARAM_TYPE_ARRAY[EoApiRequestParam::ENUM_VALUE];
        }
        $allType = EoApiRequestParam::enumParamType();
        foreach ($allType as $item) {
            $item['name'] = strtoupper($item['name']);
            if (Str::contains($type, $item['name']) || Str::contains($item['name'], $type)) {
                $typeValue = $item['value'];
                break;
            }
        }
        return $typeValue;
    }

    /**
     * 创建状态码分组
     *
     * @param string $group
     * @param        $projectID
     *
     * @return mixed
     */
    public function createProjectStatusCodeGroup(string $group, int $projectID)
    {
        return EoProjectStatusCodeGroup::query()->firstOrCreate(['projectID' => $projectID, 'groupName' => $group], ['parentGroupID' => 0, 'isChild' => 0]);
    }


    /**
     * 创建状态码
     *
     * @param int    $code            状态码
     * @param string $codeDescription 状态码描述
     * @param int    $groupID         分组id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public function createProjectStatusCode(int $code, string $codeDescription, int $groupID)
    {
        return EoProjectStatusCode::query()->updateOrCreate(['code' => $code, 'groupID' => $groupID], ['codeDescription' => $codeDescription]);
    }


}
