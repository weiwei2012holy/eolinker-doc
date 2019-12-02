<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-09-02,20:35
 */

namespace Weiwei2012holy\EolinkerDoc\Commands;

use Illuminate\Console\Command;
use Weiwei2012holy\EolinkerDoc\Models\ApiDocGenerateTool;

class CreateCode extends Command
{
    protected $signature = 'eolinker:create-code';

    protected $description = '创建生成api-doc状态码';

    public function handle()
    {
        if (!$codeGroup = $this->ask('状态码分组,默认:业务状态码')) {
            $codeGroup = '业务状态码';
        }
        $code = $this->ask('请输入状态码');
        if (!is_numeric($code)) {
            $this->error('状态码必须为数字');
            return;
        }
        $desc = $this->ask('请输入状态码描述');
        $codeData = [$code => $desc];
        if (empty($codeData)) {
            $this->error('缺少状态码数据:格式code=>desc数组');
            return;
        }
        if (empty($codeGroup)) {
            $this->error('缺少状态码分组信息');
            return;
        }
        $wxaApiRepository = (new ApiDocGenerateTool())->init(config('eolinker.account'), config('eolinker.project_id'));
        $wxaApiRepository->createCode($codeGroup, $codeData);
    }
}
