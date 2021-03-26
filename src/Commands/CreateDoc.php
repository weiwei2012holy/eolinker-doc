<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-09-02,20:35
 */

namespace Weiwei2012holy\EolinkerDoc\Commands;

use Illuminate\Console\Command;
use Weiwei2012holy\EolinkerDoc\ApiDocGenerateTool;

class CreateDoc extends Command
{
    protected $signature = 'eolinker:create-doc';

    protected $description = '创建生成api-doc文档';

    public function handle()
    {
        if (!$filters['action'] = $this->ask('Action过滤,默认%App\Http\Controllers%')) {
            $filters['action'] = 'App\Http\Controllers';
        }
        $filters['path'] = $this->ask('path 过滤条件,默认不匹配');
        $filters['method'] = $this->ask('method 过滤,默认不匹配');
        $filters['name'] = $this->ask('名称 过滤,默认不匹配');
        $filters = array_filter($filters);
        //清楚缓存 避免找不到路由
        $this->call('route:clear');
        $wxaApiRepository = (new ApiDocGenerateTool())->init(config('eolinker.account'), config('eolinker.project_id'));
        $apiList = $wxaApiRepository->filterRoute($filters);

        $total = count($apiList);
        if ($total > 0) {
            if ($this->confirm('本次匹配到路由个数:' . $total . ',是否继续?')) {
                foreach ($apiList as $api) {
                    try {
                        $wxaApiRepository->createEoliknerDoc($api);
                        $this->info('[' . $api['method'] . '](' . $api['uri'] . ')==>done');
                    } catch (\Exception $e) {
                        $this->error('[' . $api['method'] . '](' . $api['uri'] . ')==>' . $e->getMessage());
                    }
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
