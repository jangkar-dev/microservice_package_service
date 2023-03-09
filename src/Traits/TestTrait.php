<?php

namespace Traits;

use App\Models\Branch;
use App\Models\Company;

trait TestTrait
{
    /**
     * It returns an array of headers that will be used in the test
     *
     * @return array array of headers.
     */
    public function header()
    {
        return [
            "Authenticated" => json_encode(["user" => [
                "id" => 0,
                "name" => "Tester"
            ]]),
            "Microservice_Token" => env("APP_SERVICE_TOKEN"),
            "Accept" => "application/json"
        ];
    }
    /**
     * It returns a random company id from the database
     *
     * @return The id of a random company.
     */
    public function randomCompany()
    {
        return Company::inRandomOrder()->first()->id;
    }
    /**
     * It returns a random branch id from the database.
     *
     * @return The id of a random branch.
     */
    public function randomBranch()
    {
        return Branch::inRandomOrder()->first()->id;
    }
    /**
     * It takes an array of branch ids and returns an array of company ids
     *
     * @param branch_ids The branch ids you want to get the company ids from.
     *
     * @return An array of company ids
     */
    public function companyFromBranch($branch_ids)
    {
        $branch_ids = (array) $branch_ids;
        return Branch::whereIn("id", $branch_ids)->get()->pluck("company_id")->toArray();
    }
    /**
     * It takes an array of company ids and returns an array of branch ids
     *
     * @param company_ids The company ids to get the branches from.
     *
     * @return An array of branch ids.
     */
    public function branchFromCompany($company_ids)
    {
        $company_ids = (array) $company_ids;
        return Branch::whereIn("company_id", $company_ids)->get()->pluck("id")->toArray();
    }
    /**
     * It returns an array of 3 random branch ids
     *
     * @return An array of 3 random branch ids.
     */
    public function randomMultipleBranch()
    {
        return Branch::inRandomOrder()->limit(3)->get()->pluck("id")->toArray();
    }
    /**
     * It returns an array of 3 random branch ids
     *
     * @return An array of 3 random branch ids.
     */
    public function randomMultipleCompany()
    {
        return Company::inRandomOrder()->limit(3)->get()->pluck("id")->toArray();
    }
    /**
     * > It takes a response object and returns the data from the response
     *
     * @param response The response object from the API call.
     *
     * @return The data from the response.
     */
    public function fetch($response)
    {
        return json_decode($response->getContent(), true)["data"];
    }
}
