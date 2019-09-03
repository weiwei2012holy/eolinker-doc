<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-09-02,19:14
 */

namespace Weiwei2012holy\EolinkerDoc\Traits;


trait ModelInfo
{

    public static function bootModelInfo()
    {

    }
    /**
     * 返回表每一列的描述,用于生成doc文档,子类可以继承重写方法实现自定义字段描述
     * 数据返回格式 ['field'=>'introduction]
     * @return array
     */
    public function getTableFullColumns(): array
    {
        $dataBases = $this->getTableFullColumnsDatabase();
        $custom = $this->getTableFullColumnsCustom();
        return array_merge($dataBases, $custom);
    }

    /**
     * 自定义字段 K>V定义
     * @return array
     */
    public function getTableFullColumnsCustom(): array
    {
        return [];
    }

    /**
     * 从数据库读取字段配置,不想用或者不可用,可以重写覆盖
     * @return array
     */
    public function getTableFullColumnsDatabase(): array
    {
        $res = [];
        $data = $this->getConnection()->select("SHOW FULL COLUMNS FROM " . $this->getFullTableNameFromDbConnection());
        foreach ($data as $item) {
            $res[$item->Field] = ($item->Comment ?: $item->Field) . ':' . $item->Type ;
        }
        return $res;
    }

    /**
     * 获取带前缀的表名
     *
     * @return string
     */
    public function getFullTableNameFromDbConnection()
    {
        return \DB::connection($this->getConnectionName())->getTablePrefix() . $this->getTable();
    }

}