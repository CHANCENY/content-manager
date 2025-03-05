<?php

namespace Simp\Core\lib\controllers;


use PDO;
use Simp\Core\lib\themes\View;
use Simp\Core\modules\database\Database;
use Simp\Core\modules\database\DTO;
use Simp\Core\modules\database\Modal;
use Simp\Core\modules\database\query_builder\QueryBuilder;
use Simp\Core\modules\user\entity\UserEntity;
use Simp\Default\ConditionalField;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HomeController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws \Exception
     */
    public function home_controller(...$args): Response
    {
        $builder = new QueryBuilder();
        $builder->select("users", 'u')
            ->addField('name',table_alias: 'u')
            ->addField('mail',table_alias: 'u')
            ->leftJoin("user_roles",'u.uid = r.uid', 'r')
            ->addCondition("uid",1,table_alias: 'u');

        $st = $builder->statement();

        dump($st->fetchAll(PDO::FETCH_ASSOC));
        return new Response(View::view('default.view.home'),200);
    }
}