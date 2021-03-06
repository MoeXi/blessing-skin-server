<?php
/**
 * Migrations Bootstrap of Blessing Skin Server
 */

// Define Base Directory
define('BASE_DIR', dirname(dirname(dirname(__FILE__))));

// Register Composer Auto Loader
require BASE_DIR.'/vendor/autoload.php';

// Boot Services
App\Services\Boot::loadServices();
Config::checkPHPVersion();
Boot::loadDotEnv(BASE_DIR);
Boot::registerErrorHandler(new \Whoops\Handler\PrettyPageHandler);
Boot::startSession();

$db_config = Config::getDbConfig();

// Boot Eloquent to make Schema available
if (Config::checkDbConfig($db_config)) {
    Boot::bootEloquent($db_config);
}

Boot::checkInstallation('../../setup/index.php');

if (isset($_COOKIE['email']) && isset($_COOKIE['token'])) {
    $_SESSION['email'] = $_COOKIE['email'];
    $_SESSION['token'] = $_COOKIE['token'];
}

// check permission
if (isset($_SESSION['email'])) {
    $user = new App\Models\User(0, ['email' => $_SESSION['email']]);

    if ($_SESSION['token'] != $user->getToken())
        Http::redirect('../../auth/login', '无效的 token，请重新登录~');

    if ($user->getPermission() != "2")
        Http::abort(403, '此页面仅超级管理员可访问');

} else {
    Http::redirect('../../auth/login', '非法访问，请先登录');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'index';

switch ($action) {
    case 'index':
        View::show('setup.migrations.index');
        break;

    case 'import-v2-textures':
        View::show('setup.migrations.import-v2-textures');
        break;

    default:
        throw new App\Exceptions\E('非法参数', 1, true);
        break;
}


