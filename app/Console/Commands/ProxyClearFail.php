<?php

namespace App\Console\Commands;

use App\Models\Proxy;
use App\Spiders\Tester;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProxyClearFail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proxy:clear-fail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '失败代理清洗';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tester = Tester::getInstance();
        Proxy::where('fail_times', '>', 0)
            ->orderBy('updated_at')
            ->chunk(200, function ($proxies) use ($tester) {
                $proxies->each(function ($proxy) use ($tester) {
                    $proxy_ip = $proxy->protocol . '://' . $proxy->ip . ':' . $proxy->port;
                    if ($speed = $tester->handle($proxy_ip)) {
                        $proxy->speed = $speed;
                        $proxy->fail_times = 0;//连续失败次数重置
                        $proxy->succeed_times = ++$proxy->succeed_times;
                        $proxy->last_checked_at = Carbon::now();
                    } else {
                        $proxy->fail_times = ++$proxy->fail_times;
                        $proxy->last_checked_at = Carbon::now();
                    }
                    $proxy->update();
                });
            });
    }
}
