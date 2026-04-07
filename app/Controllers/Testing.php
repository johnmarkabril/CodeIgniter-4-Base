<?php

namespace App\Controllers;

class Testing extends BaseController
{
    public function getData()
    {
        $usersModel = new \App\Models\UsersModel();

        $where  =   [
            'DELETED'   =>  NULL
        ];

        $orderBy    =   'USERS_ID desc';

        $data = $usersModel->listReader($where, 5, null, $orderBy);

        dump($data, 1);
    }
}
