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
     * This class is for interacting with Wikipedia's api.php API.
     **/
class WikipediaApi
{
    private $http;
    private $user;
    private $pass;
    public $apiurl = 'https://en.wikipedia.org/w/api.php';

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
    }

    /**
     * This function takes a username and password and logs you into wikipedia.
     *
     * @param $user Username to login as
     * @param $pass Password that corrisponds to the username
     **/
    public function login($user, $pass)
    {
        global $logger;
        $this->user = $user;
        $this->pass = $pass;
        $x = $this->http->post(
            $this->apiurl . '?action=login&format=php',
            array('lgname' => $user, 'lgpassword' => $pass)
        );
        $logger->addDebug($x);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('login API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if ($x['login']['result'] == 'Success') {
            return true;
        }
        if ($x['login']['result'] == 'NeedToken') {
            $x = $this->http->post(
                $this->apiurl . '?action=login&format=php',
                array('lgname' => $user,
                      'lgpassword' => $pass,
                      'lgtoken' => $x['login']['token'])
            );
            $logger->addDebug($x);
            $x = unserialize($x);

            if (isset($x['warnings'])) {
                $logger->addWarning('login API returned warnings: ' .
                                    var_export($x['warnings'], true));
            }

            if ($x['login']['result'] == 'Success') {
                return true;
            }
        }

        return false;
    }

    /**
     * This function returns the CSRF token for a certain page.
     *
     * @param $title Page to get the tokens for.
     *
     * @return A CSRF token for the page
     **/
    public function gettoken($title)
    {
        global $logger;

        $x = $this->http->get(
            $this->apiurl . '?rawcontinue=1&format=php' .
            '&action=query&meta=tokens&type=csrf&titles=' .
            urlencode($title)
        );
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('gettoken API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        return $x['query']['tokens']['csrftoken'];
    }

    /**
     * This function returns the recent changes for the wiki.
     *
     * @param $count The number of items to return. (Default 10)
     * @param $namespace The namespace ID to filter items on. Null for no filtering. (Default null)
     * @param $dir The direction to pull items.  "older" or "newer".  (Default 'older')
     * @param $ts The timestamp to start at.  Null for the beginning/end (depending on direction).  (Default null)
     *
     * @return Associative array of recent changes metadata
     **/
    public function recentchanges($count = 10, $namespace = null, $dir = 'older', $ts = null)
    {
        global $logger;

        $append = '';
        if ($ts !== null) {
            $append .= '&rcstart=' . urlencode($ts);
        }
        $append .= '&rcdir=' . urlencode($dir);
        if ($namespace !== null) {
            $append .= '&rcnamespace=' . urlencode($namespace);
        }
        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&' .
                              'list=recentchanges&rcprop=user|comment|flags|' .
                              'timestamp|title|ids|sizes&format=php&rclimit=' .
                              $count . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('recentchanges API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        return $x['query']['recentchanges'];
    }

    /**
     * This function returns search results from Wikipedia's internal search engine.
     *
     * @param $search The query string to search for
     * @param $limit The number of results to return. (Default 10)
     * @param $offset The number to start at.  (Default 0)
     * @param $namespace The namespace ID to filter by.  Null means no filtering.  (Default 0)
     * @param $what What to search, 'text' or 'title'.  (Default 'text')
     * @param $redirs Whether or not to list redirects.  (Default false)
     *
     * @return Associative array of search result metadata
     **/
    public function search($search, $limit = 10, $offset = 0, $namespace = 0, $what = 'text', $redirs = false)
    {
        global $logger;

        $append = '';
        if ($limit != null) {
            $append .= '&srlimit=' . urlencode($limit);
        }
        if ($offset != null) {
            $append .= '&sroffset=' . urlencode($offset);
        }
        if ($namespace != null) {
            $append .= '&srnamespace=' . urlencode($namespace);
        }
        if ($what != null) {
            $append .= '&srwhat=' . urlencode($what);
        }
        if ($redirs == true) {
            $append .= '&srredirects=1';
        } else {
            $append .= '&srredirects=0';
        }
        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1' .
                              '&list=search&format=php&srsearch=' .
                              urlencode($search) . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('search API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        return $x['query']['search'];
    }

    /**
     * Retrieve entries from the WikiLog.
     *
     * @param $user Username who caused the entry.  Null means anyone.  (Default null)
     * @param $title Object to which the entry refers.  Null means anything.  (Default null)
     * @param $limit Number of entries to return.  (Default 50)
     * @param $type Type of logs.  Null means any type.  (Default null)
     * @param $start Date to start enumerating logs.  Null means beginning/end depending on $dir.  (Default null)
     * @param $end Where to stop enumerating logs.  Null means whenever limit is satisfied or there are no more logs.
     *        (Default null)
     * @param $dir Direction to enumerate logs.  "older" or "newer".  (Default 'older')
     *
     * @return Associative array of logs metadata
     **/
    public function logs(
        $user = null,
        $title = null,
        $limit = 50,
        $type = null,
        $start = null,
        $end = null,
        $dir = 'older'
    ) {
        global $logger;

        $append = '';
        if ($user != null) {
            $append .= '&leuser=' . urlencode($user);
        }
        if ($title != null) {
            $append .= '&letitle=' . urlencode($title);
        }
        if ($limit != null) {
            $append .= '&lelimit=' . urlencode($limit);
        }
        if ($type != null) {
            $append .= '&letype=' . urlencode($type);
        }
        if ($start != null) {
            $append .= '&lestart=' . urlencode($start);
        }
        if ($end != null) {
            $append .= '&leend=' . urlencode($end);
        }
        if ($dir != null) {
            $append .= '&ledir=' . urlencode($dir);
        }
        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&' .
                              'format=php&list=logevents&leprop=ids|title|type' .
                              '|user|timestamp|comment|details' . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('logs API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        return $x['query']['logevents'];
    }

    /**
     * Retrieves metadata about a user's contributions.
     *
     * @param $user Username whose contributions we want to retrieve
     * @param $count Number of entries to return.  (Default 50)
     * @param[in,out] $continue Where to continue enumerating if part of a larger, split request.
     *                This is filled with the next logical continuation value.  (Default null)
     *
     * @param $dir Which direction to enumerate from, "older" or "newer".  (Default 'older')
     *
     * @return Associative array of contributions metadata
     **/
    public function usercontribs($user, $count = 50, &$continue = null, $dir = 'older')
    {
        global $logger;

        if ($continue != null) {
            $append = '&ucstart=' . urlencode($continue);
        } else {
            $append = '';
        }
        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&' .
                              'format=php&list=usercontribs&ucuser=' . urlencode($user) .
                              '&uclimit=' . urlencode($count) . '&ucdir=' . urlencode($dir) . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('usercontribs API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if (
            is_array($x) && array_key_exists('query-continue', $x) &&
            array_key_exists('usercontribs', $x['query-continue']) &&
            array_key_exists('ucstart', $x['query-continue']['usercontribs'])
        ) {
            $continue = $x['query-continue']['usercontribs']['ucstart'];
        }

        return $x['query']['usercontribs'];
    }

    /**
     * Returns revision data (meta and/or actual).
     *
     * @param $page Page for which to return revision data for
     * @param $count Number of revisions to return. (Default 1)
     * @param $dir Direction to start enumerating multiple revisions from, "older" or "newer". (Default 'older')
     * @param $content Whether to return actual revision content, true or false.  (Default false)
     * @param $revid Revision ID to start at.  (Default null)
     * @param $wait Whether or not to wait a few seconds for the specific revision to become available.  (Default true)
     * @param $getrbtok Whether or not to retrieve a rollback token for the revision.  (Default false)
     * @param $dieonerror Whether or not to kill the process with an error if an error occurs.  (Default false)
     * @param $redirects Whether or not to follow redirects.  (Default false)
     *
     * @return Associative array of revision data
     **/
    public function revisions(
        $page,
        $count = 1,
        $dir = 'older',
        $content = false,
        $revid = null,
        $wait = true,
        $getrbtok = false,
        $dieonerror = false,
        $redirects = false
    ) {
        global $logger;
        $url = $this->apiurl . '?action=query&rawcontinue=1&prop=revisions&titles=' .
               urlencode($page) . '&rvlimit=' . urlencode($count) . '&rvprop=timestamp|ids|user|comment' .
               (($content) ? '|content' : '') . '&format=php&meta=userinfo&rvdir=' . urlencode($dir) .
               (($revid !== null) ? '&rvstartid=' . urlencode($revid) : '') .
               (($getrbtok == true) ? '&rvtoken=rollback' : '') . (($redirects == true) ? '&redirects' : '');
        $x = $this->http->get($url);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('revisions API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if ($revid !== null) {
            $found = false;
            if (!isset($x['query']['pages']) or !is_array($x['query']['pages'])) {
                if ($dieonerror == true) {
                    $logger->addError('No such page: ' . $url);
                    die();
                } else {
                    return false;
                }
            }
            foreach ($x['query']['pages'] as $data) {
                if (!isset($data['revisions']) or !is_array($data['revisions'])) {
                    if ($dieonerror == true) {
                        $logger->addError('No such page: ' . $url);
                        die();
                    } else {
                        return false;
                    }
                }
                foreach ($data['revisions'] as $data2) {
                    if ($data2['revid'] == $revid) {
                        $found = true;
                    }
                }
                unset($data, $data2);
                break;
            }

            if ($found == false) {
                if ($wait == true) {
                    sleep(1);

                    return $this->revisions($page, $count, $dir, $content, $revid, false, $getrbtok, $dieonerror);
                } else {
                    if ($dieonerror == true) {
                        $logger->addError('Revision error: ' . $url);
                        die();
                    }
                }
            }
        }
        if ($x['query'] != null && array_key_exists('pages', $x['query'])) {
            foreach ($x['query']['pages'] as $key => $data) {
                $continue = null;
                if (
                    is_array($x) && array_key_exists('query-continue', $x) &&
                    array_key_exists('revisions', $x['query-continue']) &&
                    array_key_exists('rvstartid', $x['query-continue']['revisions'])
                ) {
                    $continue = $x['query-continue']['revisions']['rvstartid'];
                }
                if (array_key_exists('ns', $data)) {
                    $data['revisions']['ns'] = $data['ns'];
                }
                if (array_key_exists('title', $data)) {
                    $data['revisions']['title'] = $data['title'];
                }
                $data['revisions']['currentuser'] = $x['query']['userinfo']['name'];
                $data['revisions']['continue'] = $continue;
                $data['revisions']['pageid'] = $key;

                return $data['revisions'];
            }
        }
    }

    /**
     * Enumerates user metadata.
     *
     * @param $start The username to start enumerating from.  Null means from the beginning.  (Default null)
     * @param $limit The number of users to enumerate.  (Default 1)
     * @param $group The usergroup to filter by.  Null means no filtering.  (Default null)
     * @param $requirestart Whether or not to require that $start be a valid username.  (Default false)
     * @param[out] $continue This is filled with the name to continue from next query.  (Default null)
     *
     * @return Associative array of user metadata
     **/
    public function users($start = null, $limit = 1, $group = null, $requirestart = false, &$continue = null)
    {
        global $logger;

        $append = '';
        if ($start != null) {
            $append .= '&aufrom=' . urlencode($start);
        }
        if ($group != null) {
            $append .= '&augroup=' . urlencode($group);
        }
        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&list=allusers' .
                              '&format=php&auprop=blockinfo|editcount|registration|groups&aulimit=' .
                              urlencode($limit) . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('users API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if (
            is_array($x) &&
            array_key_exists('query-continue', $x) &&
            array_key_exists('allusers', $x['query-continue']) &&
            array_key_exists('aufrom', $x['query-continue']['allusers'])
        ) {
            $continue = $x['query-continue']['allusers']['aufrom'];
        }

        if (($requirestart == true) and ($x['query']['allusers'][0]['name'] != $start)) {
            return false;
        }

        return $x['query']['allusers'];
    }

    /**
     * Get members of a category.
     *
     * @param $category Category to enumerate from
     * @param $count Number of members to enumerate.  (Default 500)
     * @param[in,out] $continue Where to continue enumerating from.
     *                          This is automatically filled in when run.
     *                          (Default null)
     *
     * @return Associative array of category member metadata
     **/
    public function categorymembers($category, $count = 500, &$continue = null)
    {
        global $logger;

        if ($continue != null) {
            $append = '&cmcontinue=' . urlencode($continue);
        } else {
            $append = '';
        }
        $category = 'Category:' . str_ireplace('category:', '', $category);
        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&list=categorymembers&cmtitle=' .
                              urlencode($category) . '&format=php&cmlimit=' . $count . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('categorymembers API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if (
            is_array($x) &&
            array_key_exists('query-continue', $x) &&
            array_key_exists('categorymembers', $x['query-continue']) &&
            array_key_exists('cmcontinue', $x['query-continue']['categorymembers'])
        ) {
            $continue = $x['query-continue']['categorymembers']['cmcontinue'];
        }

        return $x['query']['categorymembers'];
    }

    /**
     * Enumerate all categories.
     *
     * @param[in,out] $start Where to start enumerating.
     *                This is updated automatically with the value to continue from.
    *                 (Default null)
     *
     * @param $limit Number of categories to enumerate.  (Default 50)
     * @param $dir Direction to enumerate in.  'ascending' or 'descending'.  (Default 'ascending')
     * @param $prefix Only enumerate categories with this prefix.  (Default null)
     *
     * @return Associative array of category list metadata
     **/
    public function listcategories(&$start = null, $limit = 50, $dir = 'ascending', $prefix = null)
    {
        global $logger;

        $append = '';
        if ($start != null) {
            $append .= '&acfrom=' . urlencode($start);
        }
        if ($limit != null) {
            $append .= '&aclimit=' . urlencode($limit);
        }
        if ($dir != null) {
            $append .= '&acdir=' . urlencode($dir);
        }
        if ($prefix != null) {
            $append .= '&acprefix=' . urlencode($prefix);
        }

        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&' .
                              'list=allcategories&acprop=size&format=php' . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('listcategories API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if (
            is_array($x) &&
            array_key_exists('query-continue', $x) &&
            array_key_exists('allcategories', $x['query-continue']) &&
            array_key_exists('acfrom', $x['query-continue']['allcategories'])
        ) {
            $start = $x['query-continue']['allcategories']['acfrom'];
        }

        return $x['query']['allcategories'];
    }

    /**
     * Enumerate all backlinks to a page.
     *
     * @param $page Page to search for backlinks to
     * @param $count Number of backlinks to list.  (Default 500)
     * @param[in,out] $continue Where to start enumerating from.  This is automatically filled in.  (Default null)
     *
     * @param $filter Whether or not to include redirects.
     *                Acceptible values are 'all', 'redirects', and 'nonredirects'.  (Default null)
     *
     * @return Associative array of backlink metadata
     **/
    public function backlinks($page, $count = 500, &$continue = null, $filter = null)
    {
        global $logger;

        if ($continue != null) {
            $append = '&blcontinue=' . urlencode($continue);
        } else {
            $append = '';
        }
        if ($filter != null) {
            $append .= '&blfilterredir=' . urlencode($filter);
        }

        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&list=backlinks&bltitle=' .
                              urlencode($page) . '&format=php&bllimit=' . $count . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('backlinks API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if (
            is_array($x) &&
            array_key_exists('query-continue', $x) &&
            array_key_exists('backlinks', $x['query-continue']) &&
            array_key_exists('blcontinue', $x['query-continue']['backlinks'])
        ) {
            $continue = $x['query-continue']['backlinks']['blcontinue'];
        }

        return $x['query']['backlinks'];
    }

    /**
     * Gets a list of transcludes embedded in a page.
     *
     * @param $page Page to look for transcludes in
     * @param $count Number of transcludes to list.  (Default 500)
     * @param[in,out] $continue Where to start enumerating from.
     *                          This is automatically filled in.  (Default null)
     *
     * @return Associative array of transclude metadata
     **/
    public function embeddedin($page, $count = 500, &$continue = null)
    {
        global $logger;

        if ($continue != null) {
            $append = '&eicontinue=' . urlencode($continue);
        } else {
            $append = '';
        }
        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&list=embeddedin&eititle=' .
                              urlencode($page) . '&format=php&eilimit=' . $count . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('embeddedin API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if (
            is_array($x) &&
            array_key_exists('query-continue', $x) &&
            array_key_exists('embeddedin', $x['query-continue']) &&
            array_key_exists('eicontinue', $x['query-continue']['embeddedin'])
        ) {
            $continue = $x['query-continue']['embeddedin']['eicontinue'];
        }

        return $x['query']['embeddedin'];
    }

    /**
     * Gets a list of pages with a common prefix.
     *
     * @param $prefix Common prefix to search for
     * @param $namespace Numeric namespace to filter on.  (Default 0)
     * @param $count Number of pages to list.  (Default 500)
     * @param[in,out] $continue Where to start enumerating from.  This is automatically filled in.  (Default null)
     *
     * @return Associative array of page metadata
     **/
    public function listprefix($prefix, $namespace = 0, $count = 500, &$continue = null)
    {
        global $logger;

        $append = '&apnamespace=' . urlencode($namespace);
        if ($continue != null) {
            $append .= '&apfrom=' . urlencode($continue);
        }
        $x = $this->http->get($this->apiurl . '?action=query&rawcontinue=1&list=allpages&apprefix=' .
                              urlencode($prefix) . '&format=php&aplimit=' . $count . $append);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('listprefix API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if (
            is_array($x) &&
            array_key_exists('query-continue', $x) &&
            array_key_exists('allpages', $x['query-continue']) &&
            array_key_exists('apfrom', $x['query-continue']['allpages'])
        ) {
            $continue = $x['query-continue']['allpages']['apfrom'];
        }
        if (!array_key_exists('query', $x)) {
            return;
        }

        return $x['query']['allpages'];
    }

    /**
     * Edits a page.
     *
     * @param $page Page name to edit
     * @param $data Data to post to page
     * @param $summary Edit summary to use
     * @param $minor Whether or not to mark edit as minor.  (Default false)
     * @param $bot Whether or not to mark edit as a bot edit.  (Default true)
     * @param $wpStarttime Time in MW TS format of beginning of edit.  (Default now)
     * @param $wpEdittime Time in MW TS format of last edit to that page.  (Default correct)
     *
     * @return bool True on success, false on failure
     **/
    public function edit(
        $page,
        $data,
        $summary = '',
        $minor = false,
        $bot = true,
        $wpStarttime = null,
        $wpEdittime = null,
        $checkrun = true
    ) {
        global $run, $user, $logger;

        $wpq = new WikipediaQuery();
        $wpq->queryurl = str_replace('api.php', 'query.php', $this->apiurl);

        if ($checkrun == true) {
            if (
                !preg_match(
                    '/(yes|enable|true)/iS',
                    ((isset($run)) ? $run : $wpq->getpage('User:' . $user . '/Run'))
                )
            ) {
                return false;
            }
        } /* Check /Run page */

        $params = array(
            'action' => 'edit',
            'format' => 'php',
            'assert' => 'bot',
            'title' => $page,
            'text' => $data,
            'token' => $this->gettoken($page),
            'summary' => $summary,
            ($minor ? 'minor' : 'notminor') => '1',
            ($bot ? 'bot' : 'notbot') => '1',
        );

        if ($wpStarttime !== null) {
            $params['starttimestamp'] = $wpStarttime;
        }
        if ($wpEdittime !== null) {
            $params['basetimestamp'] = $wpEdittime;
        }

        $x = $this->http->post($this->apiurl, $params);
        $logger->addDebug($x);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('edit API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        if ($x['edit']['result'] == 'Success') {
            return true;
        }
        if ($x['error']['code'] == 'badtoken') {
            if ($this->gettoken($page) == '') {
                if (!$this->login($this->user, $this->pass)) {
                    $logger->addError('Bah! Could not login!');
                    return false;
                }
            }

            return $this->edit($page, $data, $summary, $minor, $bot, $wpStarttime, $wpEdittime, $checkrun);
        } else {
            return false;
        }
    }

    /**
     * Moves a page.
     *
     * @param $old Name of page to move
     * @param $new New page title
     * @param $reason Move summary to use
     **/
    public function move($old, $new, $reason)
    {
        global $logger;
        $params = array(
            'action' => 'move',
            'format' => 'php',
            'from' => $old,
            'to' => $new,
            'token' => $this->gettoken($old),
            'reason' => $reason,
        );

        $x = $this->http->post($this->apiurl, $params);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('move API returned warnings: ' .
                                var_export($x['warnings'], true));
        }

        $logger->addInfo($x);
    }

    /**
     * Rollback an edit.
     *
     * @param $title Title of page to rollback
     * @param $user Username of last edit to the page to rollback
     * @param $reason Edit summary to use for rollback
     * @param $token Rollback token.  If not given, it will be fetched.  (Default null)
     **/
    public function rollback($title, $user, $reason, $token = null)
    {
        global $logger;
        if (($token == null) or ($token == '')) {
            $token = $this->revisions($title, 1, 'older', false, null, true, true);
            if ($token[0]['user'] == $user) {
                $token = $token[0]['rollbacktoken'];
            } else {
                return false;
            }
        }
        $params = array(
            'action' => 'rollback',
            'format' => 'php',
            'title' => $title,
            'user' => $user,
            'summary' => $reason,
            'token' => $token,
            'markbot' => 0,
        );

        $logger->addDebug('Posting to API: ' . $params);
        $x = $this->http->post($this->apiurl, $params);
        $x = unserialize($x);

        if (isset($x['warnings'])) {
            $logger->addWarning('rollback API returned warnings: ' .
                                var_export($x['warnings'], true));
        }
        $logger->addInfo($x);

        return (isset($x['rollback']['summary']) and isset($x[ 'rollback' ][ 'revid' ]) and $x[ 'rollback' ][ 'revid' ])
            ? true
            : false;
    }
}
