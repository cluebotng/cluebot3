<?php

    /*
     * Copyright (C) 2015 Jacobi Carter
     *
     * This file is part of ClueBot III.
     *
     * ClueBot III is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 2 of the License, or
     * (at your option) any later version.
     *
     * ClueBot III is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with ClueBot III.  If not, see <http://www.gnu.org/licenses/>.
     */

namespace ClueBot3;

    /**
     * This class is designed to provide a simplified interface to cURL which maintains cookies.
     *
     * @author Cobi
     **/
class Http
{
    private $ch;
    private $uid;
    public $postfollowredirs;
    public $getfollowredirs;

    /**
     * Our constructor function.  This just does basic cURL initialization.
     **/
    public function __construct()
    {
        global $proxyhost, $proxyport, $proxytype;
        $this->ch = curl_init();
        $this->uid = dechex(rand(0, 99999999));
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, '/tmp/cluewikibot.cookies.' . $this->uid . '.dat');
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, '/tmp/cluewikibot.cookies.' . $this->uid . '.dat');
        curl_setopt($this->ch, CURLOPT_MAXCONNECTS, 100);
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'ClueBot/1.1');
        if (isset($proxyhost) and isset($proxyport) and ($proxyport != null) and ($proxyhost != null)) {
            curl_setopt($this->ch, CURLOPT_PROXYTYPE, isset($proxytype) ? $proxytype : CURLPROXY_HTTP);
            curl_setopt($this->ch, CURLOPT_PROXY, $proxyhost);
            curl_setopt($this->ch, CURLOPT_PROXYPORT, $proxyport);
        }
        $this->postfollowredirs = 0;
        $this->getfollowredirs = 1;
    }

    /**
     * Post to a URL.
     *
     * @param $url The URL to post to
     * @param $data The post-data to post, should be an array of key => value pairs
     *
     * @return Data retrieved from the POST request
     **/
    public function post($url, $data)
    {
        global $logger;
        $time = microtime(1);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $this->postfollowredirs);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $data = curl_exec($this->ch);

        $logger->addDebug('POST: ' . $url . ' (' . (microtime(1) - $time) . ' s) (' . strlen($data) . ' b)');

        return $data;
    }

    /**
     * Get a URL.
     *
     * @param $url The URL to get
     *
     * @return Data retrieved from the GET request
     **/
    public function get($url)
    {
        global $logger;
        $time = microtime(1);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $this->getfollowredirs);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
        $data = curl_exec($this->ch);

        $logger->addDebug('GET: ' . $url . ' (' . (microtime(1) - $time) . ' s) (' . strlen($data) . ' b)');

        return $data;
    }

    /**
     * Our destructor.  Cleans up cURL and unlinks temporary files.
     **/
    public function __destruct()
    {
        curl_close($this->ch);
    }
}
