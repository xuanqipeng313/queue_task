<?php

namespace QueueTask\Job;

use QueueTask\Exception\TaskException;
use QueueTask\Handler\JobHandler;
use QueueTask\Queue\Queue;

class Job
{
    /**
     * @var string 随机字符串(防止队列唯一)
     */
    public $checkid = '';

    /**
     * @var JobHandler job handler
     */
    protected $handler;
    /**
     * @var String 执行的方法
     */
    protected $func;
    /**
     * @var array 执行方法的参数
     */
    protected $param;
    /**
     * @var bool 是否强制失败(强制失败不会重试)
     */
    protected $forceFailed = false;
    /**
     * @var boolean 当前是否执行成功
     */
    protected $isexec;
    /**
     * @var int 当前已经执行次数
     */
    protected $attempts;
    /**
     * @var string[] 异常信息数组
     */
    protected $errorArr = [];

    /**
     * @param JobHandler $handler 回调类
     * @param String $func        回调类中的回调方法名
     * @param array $param        该回调方法需要的参数数组
     */
    public function __construct(JobHandler $handler , $func , array $param)
    {
        $this->checkid = $this->generateCheckId();

        $this->handler = $handler;
        $this->func = $func;
        $this->param = $param;

        $this->init();
    }

    /**
     * 生成checkId
     * @return string
     */
    public function generateCheckId()
    {
        return md5(uniqid(rand(0,9999).microtime(true),true));
    }

    /**
     * 初始化默认任务参数
     */
    public function init()
    {
        $this->isexec = false;
        $this->attempts = 0;
    }

    /**
     * 该任务已经执行的次数
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }


    /**
     * 任务失败回调
     * @return void
     */
    public function failed()
    {
        $this -> handler -> failed($this,$this->func,$this->param);
    }

    /**
     * 任务成功回调
     * @return void
     */
    public function success()
    {
        $this -> handler -> success($this,$this->func,$this->param);
    }

    /**
     * 执行任务
     * @return void
     */
    public function execute()
    {
        $this -> attempts ++;
        try{

            //执行handler回调
            $this->handler->handler($this,$this->func,$this->param);

            $this->isexec = true;

        }catch (TaskException $e){

            if ($e->getCode() == TaskException::FORCE_FAILED) {
                // 强制失败，不会重试
                $this->forceFailed = true;
            }

            $this -> isexec = false;

            $this->errorArr[] = $e->getMessage();
        }

    }


    /**
     * 任务是否执行成功
     * @return boolean
     */
    public function isExec()
    {
        return $this->isexec;
    }


    /**
     * 获取任务失败的异常信息数组
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errorArr;
    }

    /**
     * 是否需要重试该任务
     * @param int $maxAttempt
     * @return bool
     */
    public function isRelease(int $maxAttempt)
    {
        if ($this->isExec()) {
            // 执行成功不需要重试
            return false;
        } else {
            if (
                // 判断是否强制失败，如果强制失败，则任务不需要重试
                ($this->forceFailed) ||
                // 判断当前是否超出指定执行次数，如果超过最大限制，则任务不需要重试
                ($maxAttempt > 0 && $this->getAttempts() >= $maxAttempt)
            ) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 重试该任务
     * @param Queue $queue 队列
     * @param string $queueName 队列名
     * @param int $delay 延迟秒数
     * @return boolean
     */
    public function release(Queue $queue, $queueName, $delay = 0)
    {
        return $queue->laterPush($delay, $this, $queueName);
    }


    /**
     * 序列化对象
     * @param Job $job
     * @return string
     */
    public static function Encode(Job $job)
    {
        return base64_encode(serialize($job));
    }


    /**
     * 反序列化对象
     * @param string $jobStr
     * @return Job
     */
    public static function Decode($jobStr)
    {
        return unserialize(base64_decode($jobStr));
    }

}
