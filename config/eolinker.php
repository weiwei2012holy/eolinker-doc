<?php
/**
 * Desc:eoliner 配置
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-08-01,18:17
 */

return [
    //eolinker 数据库链接
    'connection' => env('EOLINKER_DB_CONN', 'eolinker'),
    //文档生成者用户id
    'account' => env('EOLINKER_DEFAULT_ACCOUNT'),
    //文档生成到具体某个项目id
    'project_id'=>env('EOLINKER_DEFAULT_PROJECT_ID')

];