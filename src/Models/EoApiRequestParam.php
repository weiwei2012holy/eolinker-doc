<?php

namespace Weiwei2012holy\EolinkerDoc\Models;

use Illuminate\Database\Eloquent\Model;

class EoApiRequestParam extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('eolinker.connection');
    }

    protected $table = 'eo_api_request_param';

    public $timestamps = false;

    protected $primaryKey = 'paramID';
    public $guarded = ['paramID'];

    const ENUM_NAME = 'name';
    const ENUM_VALUE = 'value';


    /**
     * 参数类型定义
     */
    const PARAM_TYPE_STRING = [self::ENUM_NAME => 'string', self::ENUM_VALUE => 0];
    const PARAM_TYPE_FILE = [self::ENUM_NAME => 'file', self::ENUM_VALUE => 1];
    const PARAM_TYPE_JSON = [self::ENUM_NAME => 'json', self::ENUM_VALUE => 2];
    const PARAM_TYPE_INT = [self::ENUM_NAME => 'int', self::ENUM_VALUE => 3];
    const PARAM_TYPE_FLOAT = [self::ENUM_NAME => 'float', self::ENUM_VALUE => 4];
    const PARAM_TYPE_DOUBLE = [self::ENUM_NAME => 'double', self::ENUM_VALUE => 5];
    const PARAM_TYPE_DATE = [self::ENUM_NAME => 'date', self::ENUM_VALUE => 6];
    const PARAM_TYPE_DATETIME = [self::ENUM_NAME => 'datetime', self::ENUM_VALUE => 7];
    const PARAM_TYPE_BOOLEAN = [self::ENUM_NAME => 'boolean', self::ENUM_VALUE => 8];
    const PARAM_TYPE_BYTE = [self::ENUM_NAME => 'byte', self::ENUM_VALUE => 9];
    const PARAM_TYPE_SHORT = [self::ENUM_NAME => 'short', self::ENUM_VALUE => 10];
    const PARAM_TYPE_LONG = [self::ENUM_NAME => 'long', self::ENUM_VALUE => 11];
    const PARAM_TYPE_ARRAY = [self::ENUM_NAME => 'array', self::ENUM_VALUE => 12];
    const PARAM_TYPE_OBJ = [self::ENUM_NAME => 'obj', self::ENUM_VALUE => 13];
    const PARAM_TYPE_NUMBER = [self::ENUM_NAME => 'number', self::ENUM_VALUE => 14];


    /**
     * @param bool        $toKV
     * @param string|null $field
     *
     * @return array|false
     */
    public static function enumParamType($toKV = false, string $field = null)
    {
        $data = [
            self::PARAM_TYPE_STRING,
            self::PARAM_TYPE_FILE,
            self::PARAM_TYPE_JSON,
            self::PARAM_TYPE_INT,
            self::PARAM_TYPE_FLOAT,
            self::PARAM_TYPE_DOUBLE,
            self::PARAM_TYPE_DATE,
            self::PARAM_TYPE_DATETIME,
            self::PARAM_TYPE_BOOLEAN,
            self::PARAM_TYPE_BYTE,
            self::PARAM_TYPE_SHORT,
            self::PARAM_TYPE_LONG,
            self::PARAM_TYPE_ARRAY,
            self::PARAM_TYPE_OBJ,
            self::PARAM_TYPE_NUMBER,
        ];
        return $toKV ? self::toKV($data, $field) : $data;
    }
}
