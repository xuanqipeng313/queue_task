<?php

namespace Tests;

use QueueTask\Handler\JobHandler;
use QueueTask\Job\Job;

class TestHandler extends JobHandler
{

    /**
     * 失败回调方法
     * @param Job $job      任务
     * @param string $func     执行的方法
     * @param array $data     参数
     * @return mixed
     */
    public function failed($job, $func, $data)
    {
        \QueueTask\Log\WorkLog::info('failed run handler -- func: '.$func.' -- params: '.json_encode($data).',error:'.json_encode($job->getErrors()));
    }

    /**
     * 任务成功回调
     * @param Job $job      任务
     * @param string $func     执行的方法
     * @param array $data     参数
     * @return mixed
     */
    public function success($job, $func, $data)
    {
        \QueueTask\Log\WorkLog::info('success run handler -- func: '.$func.' -- params: '.json_encode($data).',error:'.json_encode($job->getErrors()));
    }


    public function test($job,$data)
    {
        \QueueTask\Log\WorkLog::info('run handler -- func: test -- params: '.json_encode($data). '; result : false');
        //$this->throwOnceFailure('error');
        $this->throwForceFailure('error');
        // 1/3几率成功
        if(rand(0,1) == 0) {
            $res = true;
            \QueueTask\Log\WorkLog::info('run handler -- func: test -- params: '.json_encode($data). '; result : true');
        } else {
            $res = false;
            \QueueTask\Log\WorkLog::info('run handler -- func: test -- params: '.json_encode($data). '; result : false');
            $this->throwOnceFailure('error ');
        }
    }

}