<?php

namespace core;

use models;

/**
 *
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/12/17
 * @package BlackMast Tasks
 */
abstract class Job {

    /** @var models\Job */
    public $job;

    /** @var object|\stdClass */
    public $params;

    /**
     * Job constructor.
     * @param mixed $params
     */
    public function __construct($params = null) {
        set_time_limit(-1);

        $this->params = !$params ? new \stdClass() : (object)$params;

        $this->job = new models\Job([
            'name'      => get_class($this),
            'params'    => $this->params,
        ]);
    }

    /**
     * @param models\Job $job
     */
    public function setJob(models\Job $job) {
        $this->params = $job->params;
        $this->job    = $job;
    }

    /**
     * Runs the job
     */
    public function run() {
        $this->doWork();
    }

    protected abstract function doWork();

    /**
     * Schedules a job
     * @param null $params
     * @param null $scheduledTime
     * @return Model
     */
    public static function queue($params = null, $scheduledTime = null) {
        $scheduledTime = $scheduledTime ?? time();

        return models\Job::new([
            'name'          => get_called_class(),
            'params'        => $params,
            'scheduledTime' => $scheduledTime,
            'status'        => 'queued',
        ])->save();
    }

}