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
class Scrape extends core\Scheduler {

    /** @var string */
    protected static $jobType = 'controllers\\jobs\\Scrape';

    /**
     * Schedules the scrape jobs
     */
    public function schedule() {
        $offset  = 0;
        $domains = [];

        // 1 week
        $lookback = strtotime('-1 year');

        do {
            $results = models\Domain::findMulti([
                '$or' => [
                    ['scrape.dateModified' => ['$eq' => null]],
                    ['scrape.dateModified' => ['$lte' => $lookback]],
                ],
            ], ['skip' => $offset, 'limit' => 100]);

            foreach ($results as $domain) {
                $domains[] = $domain->name;
            }

            $offset += 100;

        } while (count($results));

        if (count($domains)) {
            // remove pending domains from the new job array
            $domains = array_diff($domains, $this->getPendingDomains()) ?? [];

            foreach ($domains as $domain)
                jobs\Scrape::queue(['domains' => [$domain]]);
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
                'status' => ['$in' => ['queued', 'in_progress', 'failed']],
            ], ['skip' => $offset, 'limit' => 100]);

            foreach ($jobs as $job)
                if (isset($job->params->domains) && is_array($job->params->domains))
                    $domains = array_merge($domains, $job->params->domains);

            $offset += 100;

        } while (count($jobs));

        return array_unique($domains);
    }

}