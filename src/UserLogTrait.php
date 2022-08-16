<?php

namespace App\Traits;

use App\Models\User;
use App\Models\Team;

trait UserLogTrait
{
    //query builder
    public function validatedWithUser()
    {
        $input = $this->validated();
        $input['created_by'] = config('user')->id;
        $input['updated_by'] = config('user')->id;
        return $input;
    }
    public function validatedWithUserEdit()
    {
        $input = $this->validated();
        $input['updated_by'] = config('user')->id;
        return $input;
    }
    public function validatedWithUserDelete()
    {
        $input['deleted_by'] = config('user')->id;
        return $input;
    }
}
