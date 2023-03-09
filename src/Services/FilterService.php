<?php

namespace Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FilterService
{
    public $request;

    public function __construct($request)
    {
        /* Getting the id of the user that is logged in. */
        $this->id_user = 1;
        $this->request = $request;
    }

    public function perPage()
    {
        $request = $this->request;
        /* Checking if the request has a per_page, if it does, it returns the per_page, if not, it
        returns 10. */
        $perPage = $request->per_page ?? 10;
        return (int)$perPage;
    }

    static function mustArray($payload)
    {
        /* Checking if the payload is an array, if it is, it returns the payload, if not, it returns an
        array with the payload. */
        return is_array($payload) ? $payload : [$payload];
    }

    public function array($payload, $convert = false)
    {
        $request = $this->request;
        /* Checking if the request has a payload, if it does, it returns the payload, if not, it returns
       an empty array. */
        $arrays = $request[$payload] ?? [];
        /* Checking if the payload is empty, if it is, it returns an empty array, if not, it returns
        the payload. */
        $arrays = $arrays == '' || $arrays == null ? [] : $arrays;
        $arrays = $convert ? $request[$payload] ??  [$request] : $arrays;
        return (array)$arrays;
    }

    public function collection($payload, $convert = false)
    {
        return collect($this->array($payload, $convert));
    }

    public function keyword()
    {
        $request = $this->request;
        /* Checking if the request has a keyword, if it does, it returns the keyword, if not, it
        returns an empty string. */
        $keyword = $request['keyword'] ?? '';
        return (string)$keyword;
    }

    public function keywords()
    {
        /* Getting the keyword from the request. */
        $keyword = $this->keyword();
        /* Converting the string into an array. */
        $keyword = explode(',', $keyword);
        /* Checking if the keyword is an array, if it is, it returns the keyword, if not, it returns an
        array with the keyword. */
        return is_array($keyword) ? $keyword : (array)$this->keyword();
    }

    public function date($payload)
    {
        $request = $this->request;
        /* Checking if the request has a payload, if it does, it returns the payload, if not, it
        returns the current date. */
        $dateTime = $request[$payload] ?? Carbon::now()->toDateString();
        /* Creating a new Carbon instance. */
        return new Carbon($dateTime);
    }

    public function collect($payload)
    {
        $payload = $this->array($payload);
        return collect($payload);
    }
}
