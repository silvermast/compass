<?php

namespace controllers\jobs;

use \Exception;
use core\Format;
use core\CURL;
use core\Debug;
use models\Domain;

use PHPHtmlParser\Dom;

/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/12/17
 * @package BlackMast Tasks
 */
class SERPScrape extends \core\Job {

    /** @var string[] */
    private $domainNames = [];

    /**
     * Main work function
     */
    protected function doWork() {
        set_time_limit(60);

        if (!isset($this->params->keyword))
            throw new Exception("No Keyword to search.");

        $this->scrapeDuckDuckGo();

        Debug::info("Scraped " . count($this->domainNames) . " domains");
        Domain::addMulti($this->domainNames);
    }

    /**
     * Pulls a DDG serp and scrapes the domains
     */
    private function scrapeDuckDuckGo() {
        $i = 0;
        $p = 0;
        $postfields = [
            'q'  => $this->params->keyword,
            'kl' => 'us-en',
        ];

        do {
            Debug::info(__METHOD__ . ' Page ' . ++$p);

            $hasMore = false; // assume we are not pulling any more
            $serp    = CURL::new("http://duckduckgo.com/html/")->post($postfields);

            $dom = new Dom();
            $dom->load($serp, [
                'whitespaceTextNode' => false,
                'removeScripts'      => true,
                'removeStyles'       => true,
            ]);

            /** @var Dom\AbstractNode $a */
            foreach ($dom->find('.result__url') as $a) {
                $href   = $a->getAttribute('href');
                $domain = Format::domain($href);
                Debug::info(__METHOD__ . ' ' . ++$i . " $href\n");
                $this->domainNames[$domain] = $domain;
            }

            // check for the next button, and convert the form into
            $nextLink = $dom->find('.nav-link');
            if ($nextLink && count($nextLink)) {
                $hasMore = true;

                foreach ($nextLink->find('input') as $input)
                    if ($iName = $input->getAttribute('name'))
                        $postfields[$iName] = $input->getAttribute('value');

                sleep(mt_rand(1, 3)); // random sleep for good measure
            }


//        print_r($this->domainNames);
        } while ($i < 100 && $hasMore);
    }

}