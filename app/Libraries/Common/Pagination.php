<?php namespace App\Libraries\Common;

/**
 * 
 * Pagination
 * 
 * A simple library to slice data depending on the page.
 * It returns a variety of useful data for paging.
 * 
 * @package App.Libraries
 * @author Bill Dwight Ijiran <dwight.ijiran@gmail.com>
 */

class Pagination
{
    public function execute($data, $page, $limit)
    {
        try {
            if ($data === null) {
                return null;
            }

            $count = count($data);
            if ($count == 0) {
                return null;
            }

            $currentPage = $page;
            $maxNumberOfPages = ceil($count / $limit);

            if ($currentPage > $maxNumberOfPages) {
                $currentPage = $maxNumberOfPages;
            }

            if ($currentPage <= 0) {
                $currentPage = 1;
            }

            $offset = ($currentPage - 1) * $limit;
            $result = array_slice($data, $offset, $limit);
            $prevPage = ($currentPage > 1) ? $currentPage - 1 : 1 ;
            $nextPage = ($currentPage < $maxNumberOfPages) ? $currentPage + 1 : $maxNumberOfPages;

            return array(
                'result' => $result,
                'pagination' => [
                    'currentPage' => (int)$currentPage,
                    'prevPage' => $prevPage,
                    'nextPage' => $nextPage,
                    'overallCount' => $count,
                    'count' => count($result),
                    'maxNumberOfPages' => $maxNumberOfPages
                ]
            );
        } catch (\Exception $e) {
            var_dump($e);
            return null;
        }
    }
}