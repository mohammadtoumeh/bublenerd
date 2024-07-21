<?php

namespace App\Repositories;

interface AuthRepositoryInterface
{
    public function register(array $data);

    public function login(array $credentials);

    public function  getAllTeacher();
    public function  getTeacher(int $id);
    public function searchForTeacher($name);

    public function update(array $data, int $id);

}
