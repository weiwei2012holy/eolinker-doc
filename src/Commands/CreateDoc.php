<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-09-02,20:35
 */

namespace Weiwei2012holy\EolinkerDoc\Commands;

use Illuminate\Console\Command;
use Weiwei2012holy\EolinkerDoc\Models\ApiDocGenerateTool;

class CreateDoc extends Command
{
    protected $signature = 'eolinker:create-doc';

    protected $description = '创建生成api-doc文档';

    public function handle()
    {

        if (!$filters['action'] = $this->ask('Action过滤,默认%App\Http\Controllers%')) {
            $filters['action'] = 'App\Http\Controllers';
        }
        $filters['path'] = $this->ask('请输入接口名称过滤条件,默认不匹配');
        $filters['method'] = $this->ask('请输入请求方式过滤,默认不匹配');
        $filters['name'] = $this->ask('请输入请求名称匹配,默认不匹配');
        $filters = array_filter($filters);
        $wxaApiRepository = new ApiDocGenerateTool();
        $apiList = $wxaApiRepository->filterRoute($filters);

        $total = count($apiList);
        if ($total > 0) {
            if ($this->confirm('本次匹配到路由个数:' . $total . ',是否继续?')) {
                foreach ($apiList as $api) {
//                    try {
                        //微信小程序-自动生成  写入到这个项目
                        $wxaApiRepository->createEoliknerDoc($api, 107);
                        $this->info('[' . $api['method'] . '](' . $api['uri'] . ')==>done');
//                    } catch (\Exception $e) {
//                        $this->error('[' . $api['method'] . '](' . $api['uri'] . ')==>' . $e->getMessage());
//                    }
                }
                $this->info('已处理完毕');
            } else {
                $this->info('已取消');
            }
        } else {
            $this->error('本次没有匹配到合适的路由');
        }
    }
}
