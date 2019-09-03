<?php

namespace Weiwei2012holy\EolinkerDoc\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EoApi extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('eolinker.connection');
    }

    protected $table = 'eo_api';

    public $timestamps = false;

    protected $primaryKey = 'apiID';

    public $guarded = ['apiID'];

    const ENUM_NAME = 'name';
    const ENUM_VALUE = 'value';

    /**
     * 请求方式
     */
    const API_REQUEST_TYPE_POST = [self::ENUM_NAME => 'post', self::ENUM_VALUE => 0];
    const API_REQUEST_TYPE_GET = [self::ENUM_NAME => 'get', self::ENUM_VALUE => 1];
    const API_REQUEST_TYPE_PUT = [self::ENUM_NAME => 'put', self::ENUM_VALUE => 2];
    const API_REQUEST_TYPE_DELETE = [self::ENUM_NAME => 'delete', self::ENUM_VALUE => 3];
    const API_REQUEST_TYPE_HEAD = [self::ENUM_NAME => 'head', self::ENUM_VALUE => 4];
    const API_REQUEST_TYPE_OPTION = [self::ENUM_NAME => 'option', self::ENUM_VALUE => 5];
    const API_REQUEST_TYPE_PATCH = [self::ENUM_NAME => 'patch', self::ENUM_VALUE => 6];

    /**
     * 接口状态
     */
    const API_STATUS_WORKING = [self::ENUM_NAME => '启用', self::ENUM_VALUE => 0, 'emoji' => '✅'];
    const API_STATUS_MAINTAIN = [self::ENUM_NAME => '维护中', self::ENUM_VALUE => 1, 'emoji' => '✴️️'];
    const API_STATUS_DEPRECATED = [self::ENUM_NAME => '废弃', self::ENUM_VALUE => 2, 'emoji' => '❌'];


    /**
     * @param bool        $toKV
     * @param string|null $field
     *
     * @return array|false
     */
    public static function enumApiRequestType($toKV = false, string $field = null)
    {
        $data = [
            self::API_REQUEST_TYPE_POST,
            self::API_REQUEST_TYPE_GET,
            self::API_REQUEST_TYPE_PUT,
            self::API_REQUEST_TYPE_DELETE,
            self::API_REQUEST_TYPE_HEAD,
            self::API_REQUEST_TYPE_OPTION,
            self::API_REQUEST_TYPE_PATCH,
        ];
        return $toKV ? self::toKV($data, $field) : $data;
    }

    /**
     * 获取接口定义
     *
     * @param string $string
     *
     * @return array
     */
    public static function getApiStatus(string $string)
    {
        $string = strtolower($string);
        if (Str::contains($string, ['on', 'working'])) {
            return self::API_STATUS_WORKING;
        } elseif (Str::contains($string, ['todo', 'maintain'])) {
            return self::API_STATUS_MAINTAIN;
        } elseif (Str::contains($string, ['down', 'deprecated'])) {
            return self::API_STATUS_DEPRECATED;
        } else {
            return self::API_STATUS_WORKING;
        }
    }
}
