<?php

namespace jangkardev\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function index(): Model | Builder;
    public function store(): Model;
    public function update(): Model;
    public static function rule(array $request): array;
    public function destroy(): Model | bool;
}
