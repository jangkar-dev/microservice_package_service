<?php

namespace jangkardev\Traits;

use Illuminate\Support\Facades\Session;

trait UserLogTrait
{
    //query builder
    public function validatedWithUser()
    {
        $input = $this->validated();
        $input['created_by'] = Session::get('user')->id;
        $input['updated_by'] = Session::get('user')->id;
        return $input;
    }
    public function validatedWithUserEdit()
    {
        $input = $this->validated();
        $input['updated_by'] = Session::get('user')->id;
        return $input;
    }
    public function validatedWithUserDelete()
    {
        $input['deleted_by'] = Session::get('user')->id;
        return $input;
    }
}
