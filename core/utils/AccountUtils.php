<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/UserQueries.php");
includeOnce("core/models/User.php");
includeOnce("core/models/EmailQueue.php");
includeOnce("core/queries/NotificationQueries.php");
includeOnce("core/queries/AccountDataQueries.php");
includeOnce("core/utils/Debug.php");
includeOnce("core/utils/UrlUtils.php");
includeOnce("core/utils/DateUtils.php");
//includeOnce("core/utils/EmailUtils.php");
session_save_path(root('live/sessions'));

class AccountUtils
{

    public static function sendConfirmationEmail($email, $pwd)
    {
        $config = Config::getInstance();
        $verificationLink = UrlUtils::shorten($config->DOMAIN_SECURE . "/svc/signup?verify&et=" . StringUtils::advancedEncryptText("<a>" . $email . "<mid>" . $pwd . "<a>"));
        $blockLink = UrlUtils::shorten($config->DOMAIN_SECURE . "/signin?block&et=" . StringUtils::advancedEncryptText("<a>" . $email . "<a>"));
        if (strpos($email, ",") !== false) {
            die("Email cannot contain a comma");
            return false;
        }/*
        EmailUtils::sendEmail($email, "Verify your email address", EmailTemplates::VerifyEmail, array(
            "verificationLink" => $verificationLink,
            "blockLink" => $blockLink
        ), EmailPriority::High);*/

    }

    public static function sendPasswordResetEmail($email)
    {
        $config = Config::getInstance();
        $resetPasswordLink = UrlUtils::shorten($config->DOMAIN_SECURE . "/signin?rp&et=" . StringUtils::advancedEncryptText("<a>" . $email . "<a>") . "&ex=" . TokenUtils::get());
        if (strpos($email, ",") !== false) {
            die("Email cannot contain a comma");
            return false;
        }/*
        EmailUtils::sendEmail($email, "Reset Password", EmailTemplates::ForgotPassword, array("resetPasswordLink" => $resetPasswordLink), EmailPriority::High);*/

    }

    public static function startSession()
    {
        if (session_status() == PHP_SESSION_NONE) {

            $config = Config::getInstance();
            session_name("__f1");
            $lifetime = 86400 * 182; // 6 months
            session_set_cookie_params($lifetime, "/");
            ini_set('session.gc_maxlifetime', $lifetime);
            ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            session_start();

            Debug::log(session_id());
        }
    }

    private static function endSession($destroyData = false)
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            // At this point we reset the session in AccountData
            // for current user before we destroy the user
            $userId = AccountUtils::currentUser() === null ? -1 : AccountUtils::currentUser()->id;
            $sessionData = AccountDataQueries::queryByUserIdAndName($userId, AccountDataKeys::CurrentSession);
            if ($sessionData != null) {
                $sessionData->value = "";
                if ($destroyData) {
                    AccountDataQueries::hardDeleteById($sessionData->id);
                } else {
                    AccountDataQueries::persist($sessionData);
                }
            }
            session_destroy();
            $_SESSION = array();
        }
    }

    public static function closeSession()
    {
        session_write_close();
    }

    public static function signout($redirectUrl = "", $destroyData = false)
    {
        session_regenerate_id(true);
        AccountUtils::endSession($destroyData);
        if ($redirectUrl != "") {
            header("location:" . urldecode($redirectUrl));
        }
    }

    public static function signin($email, $name, $redirectUrl = "", $pwd = null)
    {
        session_regenerate_id(true);
        AccountUtils::startSession();
        $user = UserQueries::queryByEmail($email);

        $timezoneBeforeSignin = DateUtils::getTimezoneName();
        if ($user === null) {
            $user = new User();
            $user->email = strtolower($email);
            $user->name = $name;
            if ($pwd != null) {
                $user->passwordHash = $pwd;
            }
            $user->preferenceFlag = UserPreference::getFlag(UserPreference::NotificationsGeneral)
                | UserPreference::getFlag(UserPreference::NotificationsProductUpdate)
                | UserPreference::getFlag(UserPreference::NotificationsHadithUpdate);
            $user->permissionFlag = GeneralUserPermission::getFlag(GeneralUserPermission::Basic);
            $user = UserQueries::persist($user);
        }
        // We save current session ID in to AccountData
        // so when user logs in using same ID via mobile
        // we are using same session. This is so user gets
        // live results for favourites etc
        $sessionData = AccountDataQueries::queryByUserIdAndName($user->id, AccountDataKeys::CurrentSession);
        $timezoneData = AccountDataQueries::queryByUserIdAndName($user->id, AccountDataKeys::TimeZone);
        if ($sessionData === null) {
            $sessionData = new AccountData();
            $sessionData->userId = $user->id;
            $sessionData->name = AccountDataKeys::CurrentSession;
            $sessionData->value = session_id();
            AccountDataQueries::persist($sessionData);
        } else if ($sessionData->value == "") {
            $sessionData->value = session_id();
            AccountDataQueries::persist($sessionData);
        } else {

            session_destroy();
            session_id($sessionData->value);
            session_start();
        }
        if ($timezoneData == null) {
            $timezoneData = new AccountData();
            $timezoneData->userId = $user->id;
            $timezoneData->name = AccountDataKeys::TimeZone;
            $timezoneData->value = $timezoneBeforeSignin;
            AccountDataQueries::persist($timezoneData);
        }
        $_SESSION["user"] = $user;
        $_SESSION["time"] = time();
        $_SESSION["timezone"] = $timezoneData->value;
        $_SESSION["timezone_last_update_utc"] = DateUtils::newDateUtc();

        AccountUtils::refreshAccountData($user);
        if ($redirectUrl != "") {

            header("location:" . $redirectUrl);
        }
        return $sessionData->value;
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION) && isset($_SESSION["user"]);
    }

    public static function currentUser()
    {
        if (AccountUtils::isLoggedIn()) {
            return $_SESSION["user"];
        }
        return null;
    }

    public static function accountData($key = null, $data = null, $defaultValue = null)
    {
        if (AccountUtils::isLoggedIn()) {
            if ($key === null && $data === null) {
                return $_SESSION["account_data"];
            }
            $defaultData = $defaultValue === null ? null : new AccountData(array("value" => $defaultValue));
            $accountData = array_key_exists($key, $_SESSION["account_data"]) ? $_SESSION["account_data"][$key] : $defaultData;
            if ($data !== null) {
                $_SESSION["account_data"][$key] = $data;
            }
            return $accountData;
        }
        return null;
    }

    public static function setAndUpdateAccountData($user, $dataKey, $value)
    {
        $data = AccountDataQueries::queryByUserIdAndName($user->id, $dataKey);
        if ($data === null) {
            $data = new AccountData();
            $data->userId = $user->id;
            $data->name = $dataKey;
            $data->value = $value;
        } else {
            $data->value = $value;
        }
        // Update in session
        AccountUtils::accountData($dataKey, $data);
        AccountDataQueries::persist($data);
    }


    public static function checkJsonArrayAccountData($key, $value)
    {
        $user = AccountUtils::currentUser();
        $currentData = AccountDataQueries::queryByUserIdAndName($user->id, $key);
        if ($currentData !== null) {
            $values = json_decode($currentData->value);
            return in_array($value, $values);
        }
        return false;
    }

    public static function checkJsonArrayAccountDataSize($key)
    {
        $user = AccountUtils::currentUser();
        $currentData = AccountDataQueries::queryByUserIdAndName($user->id, $key);
        if ($currentData !== null) {
            $values = json_decode($currentData->value);
            return count($values);
        }
        return 0;
    }

    public static function addJsonArrayAccountDataToUser($key, $value, $user)
    {
        $currentData = AccountDataQueries::queryByUserIdAndName($user->id, $key);
        if ($currentData !== null) {
            $values = json_decode($currentData->value);
            if (!in_array($value, $values)) {
                $values[] = $value;
                $currentData->value = json_encode($values);
            }
        } else {
            $currentData = new AccountData();
            $currentData->userId = $user->id;
            $currentData->name = $key;
            $currentData->value = json_encode(array($value));
        }
        AccountDataQueries::persist($currentData);
        AccountUtils::accountData($key, $currentData);
    }

    public static function addJsonArrayAccountData($key, $value)
    {
        $user = AccountUtils::currentUser();
        AccountUtils::addJsonArrayAccountDataToUser($key, $value, $user);
    }


    public static function removeJsonArrayAccountDataFromUser($key, $value, $user)
    {
        $currentData = AccountDataQueries::queryByUserIdAndName($user->id, $key);
        if ($currentData !== null) {
            $values = json_decode($currentData->value);
            if (in_array($value, $values)) {
                $values = array_merge(array_diff($values, array($value)));
                $currentData->value = json_encode($values);
                AccountDataQueries::persist($currentData, array("value"));
                AccountUtils::accountData($key, $currentData);
            }
        }
    }

    public static function removeJsonArrayAccountData($key, $value)
    {
        $user = AccountUtils::currentUser();
        AccountUtils::removeJsonArrayAccountDataFromUser($key, $value, $user);
    }

    public static function refreshAccountData($user)
    {
        if ($user == null) {
            return;
        }
        $data = AccountDataQueries::queryByUserId($user->id);
        $sessionData = AccountDataQueries::queryByUserIdAndName($user->id, AccountDataKeys::CurrentSession);
        if ($sessionData != null && $sessionData->value != "") {
            session_id($sessionData->value);
            $_SESSION["account_data"] = array();
            foreach ($data as $accountData) {
                $_SESSION["account_data"]["$accountData->name"] = $accountData;
            }
        }
    }

    public static function verifyAccess($userPermission)
    {
        if (AccountUtils::currentUser() === null) {
            return false;
        }
        return AccountUtils::currentUser()->hasPermission($userPermission);
    }

    public static function verifyAccessToUrl($userPermission)
    {
        if (AccountUtils::currentUser() === null) {
          $redr = "https://amrayn.com" . UrlUtils::pageRedirectParam();
$cookHtml = <<<COOK
<div id="try"></div>
<script type="text/javascript">
  window.onload = function() {
    fetch('/api/cfg/read?key=PHP_SESSION').then(r => r.json()).then(({ val }) => {
      if (val) {
        document.querySelector('#try').innerHTML= `<a href='/svc/signintoken?s=\${val}&redr=$redr'>Try</a>`;
      }
    }).catch(() => {});
  }
</script>
COOK;
            die("Please <a href='/signin?redr=" . UrlUtils::pageRedirectParam() . "'>sign in</a> first so we can verify your permissions.$cookHtml<br/> (<a href='/'>Goto Homepage</a>)");
        }
        if (!AccountUtils::currentUser()->hasPermission($userPermission)) {
            die("Permission denied! (<a href='/signout?redr=/signin?redr=" . UrlUtils::pageRedirectParam() . "'>Sign in again</a> OR <a href='/'>Goto Homepage</a>)");
        }
    }

    public static function signInTokenToUser($token, $lenient = false)
    {
        $id = AccountData::getActualId($token);
        if ($id == -1) {
            return 1;
        } else {
            $data = AccountDataQueries::queryActiveById($id);
            if ($data == null || $data->name != AccountDataKeys::CurrentSession) {
                return 2;
            } else if (($data->value == null || $data->value == "") && !$lenient) {
                return 3;
            } else {
                $user = UserQueries::queryActiveById($data->userId);
                return $user;
            }
        }
    }
}
