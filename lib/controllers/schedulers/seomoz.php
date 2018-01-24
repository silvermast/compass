<?php

namespace controllers\schedulers;

use core;
use models;
use controllers\jobs;

use core\Debug;

/**
 *
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/18/17
 * @package BlackMast Tasks
 */
class SEOMoz extends core\Scheduler {

    /** @var string */
    protected static $jobType = 'controllers\\jobs\\SEOMoz';

    /**
     * Schedules the scrape jobs
     */
    public function schedule() {
        $offset  = 0;
        $domains = [];

        // 2 weeks
        $lookback = strtotime('-1 year');

        do {
            $results = models\Domain::findMulti([
                '$or' => [
                    ['moz.dateModified' => ['$eq' => null]],
                    ['moz.dateModified' => ['$lte' => $lookback]],
                ],
            ], ['skip' => $offset, 'limit' => 100]);

            foreach ($results as $domain) {
                $domains[] = $domain->name;
            }

            $offset += 100;

        } while (count($results));

        if (count($domains)) {
            // remove pending domains from the new job array
            $domains      = array_diff($domains, $this->getPendingDomains());
            $domainChunks = array_chunk($domains, 10);
            $timeDiff     = 10;
            foreach ($domainChunks as $domainChunk) {

                jobs\SEOMoz::queue(['domains' => $domainChunk], time() + $timeDiff);
                $timeDiff += 10;

            }
        }
    }

    /**
     * Checks the job queue for queued and in_progress domains and returns them as an array
     * @return string[]
     */
    private function getPendingDomains() {
        $domains = [];
        $offset  = 0;

        do {
            $jobs = models\Job::findMulti([
                'name'   => self::$jobType,
                'status' => ['$in' => ['queued', 'in_progress']],
            ], ['skip' => $offset, 'limit' => 100]);

            foreach ($jobs as $job)
                if (isset($job->params->domains) && is_array($job->params->domains))
                    $domains = array_merge($domains, $job->params->domains);

            $offset += 100;

        } while (count($jobs));

        return array_unique($domains);
    }

}