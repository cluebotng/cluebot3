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
     * This class is a deprecated wrapper class which allows legacy code written
     * for Wikipedia's query.php API to still work with wikipediaapi::.
     **/
class WikipediaQuery
{
    private $http;
    private $api;
    public $queryurl = 'https://en.wikipedia.org/w/query.php';

    /**
     * This is our constructor.
     **/
    public function __construct()
    {
        global $__wp__http;
        if (!isset($__wp__http)) {
            $__wp__http = new Http();
        }
        $this->http = &$__wp__http;
        $this->api = new WikipediaApi();
    }

    /**
     * Reinitializes the queryurl.
     *
     * @private
     **/
    private function checkurl()
    {
        $this->api->apiurl = str_replace('query.php', 'api.php', $this->queryurl);
    }

    /**
     * Gets the content of a page.
     *
     * @param $page The wikipedia page to fetch
     *
     * @return The wikitext for the page
     **/
    public function getpage($page)
    {
        $this->checkurl();
        $ret = $this->api->revisions($page, 1, 'older', true, null, true, false, false, false);

        return $ret[0]['slots']['main']['*'];
    }

    /**
     * Gets the page id for a page.
     *
     * @param $page The wikipedia page to get the id for
     *
     * @return The page id of the page
     **/
    public function getpageid($page)
    {
        $this->checkurl();
        $ret = $this->api->revisions($page, 1, 'older', false, null, true, false, false, false);

        return $ret['pageid'];
    }

    /**
     * Gets the number of contributions a user has.
     *
     * @param $user The username for which to get the edit count
     *
     * @return The number of contributions the user has
     **/
    public function contribcount($user)
    {
        $this->checkurl();
        $ret = $this->api->users($user, 1, null, true);
        if ($ret !== false) {
            return $ret[0]['editcount'];
        }

        return false;
    }
}
