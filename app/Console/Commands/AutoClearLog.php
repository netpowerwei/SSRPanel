<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Models\Config;
use App\Http\Models\SsNodeInfo;
use App\Http\Models\SsNodeOnlineLog;
use App\Http\Models\SsNodeTrafficHourly;
use App\Http\Models\UserBanLog;
use App\Http\Models\UserTrafficLog;
use App\Http\Models\UserTrafficHourly;
use Log;

class AutoClearLog extends Command
{
    protected $signature = 'autoClearLog';
    protected $description = '自动清除日志';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $jobStartTime = microtime(true);

        $config = $this->systemConfig();

        if ($config['is_clear_log']) {
            // 自动清除30分钟以前的节点负载信息日志
            SsNodeInfo::query()->where('log_time', '<=', strtotime(date('Y-m-d H:i:s', strtotime("-30 minutes"))))->delete();

            // 自动清除1小时以前的节点在线用户数日志
            SsNodeOnlineLog::query()->where('log_time', '<=', strtotime(date('Y-m-d H:i:s', strtotime("-60 minutes"))))->delete();

            // 自动清除30天以前的用户流量日志
            UserTrafficLog::query()->where('log_time', '<=', strtotime(date('Y-m-d H:i:s', strtotime("-30 days"))))->delete();

            // 自动清除10天以前的用户每小时流量数据日志
            UserTrafficHourly::query()->where('created_at', '<=', date('Y-m-d H:i:s', strtotime('-10 days')))->delete();

            // 自动清除60天以前的节点每小时流量数据日志
            SsNodeTrafficHourly::query()->where('created_at', '<=', date('Y-m-d H:i:s', strtotime('-60 days')))->delete();

            // 自动清除30天以前用户封禁日志
            UserBanLog::query()->where('created_at', '<=', strtotime(date('Y-m-d H:i:s', strtotime("-30 days"))))->delete();
        }

        $jobEndTime = microtime(true);
        $jobUsedTime = round(($jobEndTime - $jobStartTime), 4);

        Log::info('执行定时任务【' . $this->description . '】，耗时' . $jobUsedTime . '秒');
    }

    // 系统配置
    private function systemConfig()
    {
        $config = Config::query()->get();
        $data = [];
        foreach ($config as $vo) {
            $data[$vo->name] = $vo->value;
        }

        return $data;
    }
}
