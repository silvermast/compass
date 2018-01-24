<?php

namespace controllers\jobs;

use \Exception;
use core\CURL;
use core\Debug;
use models\Domain;

use PHPHtmlParser\Dom;

/**
 *
 * Error: test with { "domains": [ "michaelhyatt.com" ] }
 *
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/12/17
 * @package BlackMast Tasks
 */
class Scrape extends \core\Job {

    /** @var Domain[] */
    private $domains;

    /**
     * Main work function
     */
    protected function doWork() {
        set_time_limit(-1);

        if (!isset($this->params->domains) || !is_array($this->params->domains))
            throw new Exception(__METHOD__ . ": No Domains to pull");

        $this->params->domains = array_map("core\\Format::domain", $this->params->domains);

        $this->domains = Domain::findMulti(['name' => ['$in' => array_values($this->params->domains)]]);

        foreach ($this->domains as $domain) {

            Debug::info("Starting Scrape $domain->name");

            $this->parseHomepage($domain);
            $this->parseBlog($domain);

            try {
                $domain->scrape->dateModified = time();
                $domain->update([
                    'resolvedUrl'  => $domain->resolvedUrl,
                    'scrape'       => $domain->scrape,
                    'dateModified' => $domain->dateModified,
                ]);
            } catch (Exception $e) {
                Debug::error($e);
            }

            Debug::info("Finished Scrape $domain->name");
        }
    }

    /**
     * Pulls the main homepage down
     * Checks for copyright, and stores resolved URL
     * @param Domain $domain
     */
    private function parseHomepage(Domain $domain) {
        Debug::info(__METHOD__ . " $domain->name");

        $curl = CURL::new("http://$domain->name")->setOpt(CURLOPT_MAXREDIRS, 10);
        $page = $curl->exec();

        preg_match_all('/(©| C |&copy;|&#169;|copyright) *(\d{0,4}[ -]{0,3}\d{4})/mis', $page, $matches);
        $domain->resolvedUrl       = $curl->getResolvedURL();
        $domain->scrape->copyright = $matches[2][0] ?? null;

        // find the scraped lastUpdated time.
        $dates = $this->scrapeDates($page);

        $now = time();
        foreach ($dates as $time => $date) {
            if ($time < $now && $time > $domain->scrape->lastUpdate) {
                $domain->scrape->lastUpdate = $time;
                $domain->scrape->page = $curl->getResolvedURL();
            }
        }
    }

    /**
     * Pulls down blog page variants and checks for timestamps
     * @param Domain $domain
     */
    private function parseBlog(Domain $domain) {
        Debug::info(__METHOD__ . " $domain->name");
        $paths = [
            'blog',
            'news',
            'articles',
            'musings',
        ];

        $now = time();

        foreach ($paths as $path) {
            $curl = CURL::new("http://$domain->name/$path");
            $html = $curl->exec();

            if ($curl->code() < 400) {
                $domain->scrape->blogLink = $curl->getResolvedURL();

                $dates = $this->scrapeDates($html);
                if (count($dates)) {

                    foreach ($dates as $time => $date) {
                        if ($time < $now && $time > $domain->scrape->lastUpdate) {
                            $domain->scrape->lastUpdate = $time;
                            $domain->scrape->page       = $curl->getResolvedURL();
                        }
                    }

                }
            }
        }

        return [];
    }

    /**
     * Scrapes all date strings from the page
     * @param $html
     * @return string[]
     */
    private function scrapeDates($html) {

        $dates = [];

        $dom = new Dom();
        $dom->load($html, [
            'whitespaceTextNode' => false,
            'removeScripts'      => true,
            'removeStyles'       => true,
        ]);
        $html = $dom->innerHtml;
        unset($dom);

        $monthRegex = '(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|january|february|march|april|june|july|august|september|october|november|december)';

        $dateRexp = [
            "@$monthRegex \d+,?( \d\d\d\d)?@mis", // May 12, 2017 or May 12
            "@[1-3]?[0-9]+ $monthRegex \d+@mis", // 28 February 2017
            "@[0-1]?[0-9]/[1-3]?[0-9]/\d{2,4}@mis", // 12/31/88 or 12/31/1988
            "@\d{4}-[0-1][0-9]-[0-3][0-9]@mis", // 2017-07-14
        ];

        foreach ($dateRexp as $rexp) {
            if (preg_match_all($rexp, $html, $results)) {
                Debug::info("Dates: " . implode(', ', $results[0]));
                $dates = array_merge($dates, $results[0]);
            }
        }

        // convert dates into assoc array and sort based on timestamp, descending
        $dates = array_unique($dates);
        $dateMap = [];
        foreach ($dates as $date) {
            $dateMap[strtotime($date)] = $date;
        }
        krsort($dateMap);

        return $dateMap;

    }

}