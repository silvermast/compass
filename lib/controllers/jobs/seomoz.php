<?php

namespace controllers\jobs;

use \Exception;
use core\Debug;
use core\Config;
use models\Domain;

/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/11/17
 * @package BlackMast Tasks
 */
class SEOMoz extends \core\Job {

    public static $cols = [
        'uu'   => 4, // Canonical URL
        'ueid' => 32, // External Equity Links
        'uid'  => 2048, // Links
        'upa'  => 34359738368, // Page Authority
        'pda'  => 68719476736, // Domain Authority
        'ulc'  => 144115188075855872, // Time Last Crawled
    ];

    /**
     * Main work function
     */
    protected function doWork() {

        if (!isset($this->params->domains) || !is_array($this->params->domains))
            throw new Exception(__METHOD__ . ": No Domains to pull");

        $this->params->domains = array_map("core\\Format::domain", $this->params->domains);

        $domainsToFetch = Domain::findMulti(['name' => ['$in' => array_values($this->params->domains)]]);

        Debug::info("Found " . count($domainsToFetch) . " domains to fetch from seoMoz");

        $domainsToFetch = array_chunk($domainsToFetch, 10, true);

        foreach ($domainsToFetch as $domainChunk) {
            if (!$data = self::pullData($domainChunk))
                continue;

            foreach ($domainChunk as $domain) {
                self::applyDomainResult($domain, $data);

                try {
                    $domain->moz->dateModified = time();
                    $domain->update([
                        'moz'          => $domain->moz,
                        'dateModified' => time(),
                    ]);
                } catch (Exception $e) {
                    Debug::error($e);
                }
            }

            sleep(10); // 10 seconds between API calls
        }

    }

    /**
     * @param $domains
     * @return array|mixed
     * @throws Exception
     */
    private static function pullData($domains) {
        if (empty($domains))
            return false;

        $domainArray = [];
        foreach ($domains as $domain)
            $domainArray[] = $domain->name;

        $domainArray = array_filter($domainArray, 'trim');
        if (!count($domainArray))
            return false;

        $config = Config::init();

        if (!isset($config->SEOMoz->accessId, $config->SEOMoz->secret))
            throw new Exception('Missing SEOMoz "accessId" or "secret"');

        $accessId     = $config->SEOMoz->accessId;
        $accessSecret = $config->SEOMoz->secret;
        $expires      = time() + 300;
        $sig          = base64_encode(hash_hmac('sha1', "$accessId\n$expires", $accessSecret, true));

        $query = http_build_query([
            'Cols'      => self::$cols['uu'] + self::$cols['pda'] + self::$cols['ueid'],
            'AccessID'  => $accessId,
            'Expires'   => $expires,
            'Signature' => $sig,
        ]);

        Debug::info("Pulling domain authority for urls " . json_encode($domainArray));

        $ch = curl_init("https://lsapi.seomoz.com/linkscape/url-metrics/?$query");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($domainArray),
        ]);

        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code != 200)
            throw new Exception($result);
        else
            return json_decode($result);
    }

    /**
     * Parses an SEOMoz result and returns the data matching the domain
     * @param $result
     */
    private static function applyDomainResult(Domain $domain, $result) {
        $domain->moz->domainAuthority = $domain->moz->domainAuthority ?? 1;
        foreach ($result as $row) {
            if ($row->uu === "$domain->name/") {

                if ($domain->moz->domainAuthority === 1)
                    $domain->moz->domainAuthority = $row->pda;

                $domain->moz->externalEquityLinks = $row->ueid;

                return;

            }
        }
    }
}