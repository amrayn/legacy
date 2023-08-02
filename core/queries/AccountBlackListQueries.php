<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/AccountBlackList.php");

class AccountBlackListQueries extends Queries
{
    public static function queryByEmail($email)
    {
        $email = StringUtils::cleanAlphaNumber($email, array("._@-"));
        return static::queryActiveByFields(array("email" => strtolower($email)), true);
    }
}
