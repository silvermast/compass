<?php

namespace core;

/**
 *
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/12/17
 * @package BlackMast Tasks
 */
class CURL {
    use Singleton;

    /** @var curl handle $ch */
    private $ch;

    /**
     * cURL constructor.
     * @param null $url
     */
    public function __construct($url = null) {
        $this->ch = curl_init($url);
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 10,
        ]);
    }

    /**
     * @param $opt
     * @param $val
     * @return self
     */
    public function setOpt($opt, $val) {
        curl_setopt($this->ch, $opt, $val);
        return $this;
    }

    /**
     * @param array $postdata
     * @return mixed
     */
    public function post($postdata = []) {
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $postdata);
        return $this->exec();
    }

    /**
     * @return mixed
     */
    public function exec() {
        return curl_exec($this->ch);
    }

    /**
     * @return mixed
     */
    public function code() {
        return $this->info(CURLINFO_HTTP_CODE);
    }

    /**
     * Gets the url resolved after following redirects
     */
    public function getResolvedURL() {
        return $this->info(CURLINFO_EFFECTIVE_URL);
    }

    /**
     * @param null $key
     * @return mixed
     */
    public function info($key = null) {
        return curl_getinfo($this->ch, $key);
    }


}