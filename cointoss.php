<?
/*
 * cointoss.php
 * (c) 2010 Entrecredits
 *
 * Example application for the entrecredits.com API
 *
 * This application implements a simple coin flipping game, with "winnings"
 * paid out via the entrecredits.com API.
 *
 */

session_start();

/* Remove this line if you're talking to the public entrecredits.com site */
define(ECC_OAUTH_URL_OVERRIDE, "https://entrecredits.com");

require_once('OAuth.php');
require_once('EccOAuth.php');

/* Obtain these details from your app details page */
$ECC_CONSUMER_KEY = "b5ff1a3af334043f78ade0fbbd5fb438";
$ECC_CONSUMER_SECRET = "f1af929bf465c8d90961ba61e28f57d8";

/* Which level of access your app requires. Unless you're providing tools to
  help the user manage their own account, this should almost always be 'basic' */
$ECC_ACCESS = "basic";

/* This URL points to this script on your web server. If you're testing on a
  local apache/php instance then it's likely to be http://localhost/something
*/
$APP_CALLBACK_URL = "http://localhost/ecc-php/cointoss.php";

/* Don't change this */
$API_BASE = "http://localhost:5001/api";

$consumer = new OAuthConsumer($ECC_CONSUMER_KEY, $ECC_CONSUMER_SECRET, $APP_CALLBACK_URL);


if (isset($_GET['logout'])) {
    /* Remove session variables. Useful mostly for the demo */
    unset($_SESSION['request_token']);
    unset($_SESSION['access_token']);
    header('Location: ' . $APP_CALLBACK_URL);
    die;
}

/* Do we have an access token? if not, we need to get one */
if (!isset($_SESSION['access_token'])) {
    /* Construct api */
    $api = new OAuthClient(new EccServiceProvider(), $consumer);
    
    /* Two possible conditions: either we're returning from the authorize request or not */
    
    /* Callback from authorize? */
    if (!(isset($_SESSION['request_token']) && isset($_GET['oauth_verifier']))) {
        /* No, we have no access token, we need to get one by generating a request token then
          asking the user to authorize it */
        
        /* Get request token */
        $request_token = $api->getRequestToken($ECC_ACCESS, $APP_CALLBACK_URL);
        #print_r($request_token); # Useful if you're not sure you've got one
        $_SESSION['request_token'] = serialize($request_token);
        
        /* Redirect user to authorize URL (in this case, it'll be somewhere on entrecredits.com) */
        header("Location: " . $api->getAuthorizeUrl($request_token));
        die;
        
    } else {
        /* Yep, callback, so we're authorized and we can trade our request token for an access token */
        $request_token = unserialize($_SESSION['request_token']);
        $access_token = $api->getAccessToken($request_token, $_GET['oauth_verifier']);
        
        /* Put access token into session. It's all we need now */
        $_SESSION['access_token'] = serialize($access_token);
        
        /* Redirect to cointoss game, now with access token */
        header('Location: ' . $APP_CALLBACK_URL);
        die;
    }
}

/* If we reach this point, we have an access token. Yay! we can get information */

/* Construct api object*/
$api = new OAuthClient(new EccServiceProvider(), $consumer, unserialize($_SESSION['access_token']));

/* Obtain the users balance */

$result = $api->call($API_BASE."/balance/get_default_balance", array());
print_r($result);

?>
