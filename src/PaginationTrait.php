<?php

namespace App\Traits;

/* A trait that allows you to paginate your data. */

trait PaginationTrait
{
    /* Setting the default value of the perPage variable. */
    public $per_page_request = 10;
    /**
     * It returns all the records if the perPage is set to all or 0. Otherwise, it returns the records
     * in the pagination format.
     *
     * @param perPage The number of items to show per page.
     */
    public function numeration($perPage = null)
    {
        $perPage = $perPage ?? $this->per_page_request;
        return ($perPage == 'all' || $perPage == 0) ? $this->get() : $this->paginate((int)$perPage);
    }
    /**
     * It returns all the results if the perPage parameter is set to 'all' or 0, otherwise it returns a
     * paginated result
     *
     * @param query The query object that you want to paginate.
     * @param perPage The number of items to show per page.
     *
     * @return A query builder instance.
     */
    public function scopeNumeration($query, $perPage = null)
    {
        $perPage = $perPage ?? $this->per_page_request;
        return ($perPage == 'all' || $perPage == 0) ? $query->get() : $query->paginate((int)$perPage);
    }
}
