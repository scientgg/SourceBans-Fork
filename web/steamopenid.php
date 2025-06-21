<?php
/*************************************************************************
	This file is part of SourceBans++
	
	Copyright © 2014-2016 SourceBans++ Dev Team <https://github.com/sbpp>

	SourceBans++ is licensed under a
	Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.

	You should have received a copy of the license along with this
	work.  If not, see <http://creativecommons.org/licenses/by-nc-sa/3.0/>.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.

	This program is based off work covered by the following copyright(s): 
		SourceBans 1.4.11
		Copyright © 2007-2014 SourceBans Team - Part of GameConnect
		Licensed under CC BY-NC-SA 3.0
		Page: <http://www.sourcebans.net/> - <http://www.gameconnect.net/>
*************************************************************************/
// Steam Login by @duhowpi 2015

session_start();
include_once 'init.php';
include_once 'config.php';
include_once INCLUDES_PATH . '/system-functions.php';
require_once 'includes/openid.php';

// Convert Steam 64 bit IDs to STEAM_X:Y:Z without relying on 64-bit extensions
function sb_convert64to32($friendid)
{
    if (!preg_match('/^[0-9]+$/', $friendid)) {
        return false;
    }

    $offset = '76561197960265728';
    $diff = sb_big_sub($friendid, $offset);
    if ($diff === false) {
        return false;
    }

    $server = sb_big_mod2($diff);
    if ($server == 1) {
        $diff = sb_big_sub($diff, '1');
    }
    $auth = sb_big_div2($diff);
    return 'STEAM_0:' . $server . ':' . $auth;
}

function sb_big_sub($a, $b)
{
    $a = ltrim($a, '0');
    $b = ltrim($b, '0');
    if (strlen($a) < strlen($b) || (strlen($a) === strlen($b) && strcmp($a, $b) < 0)) {
        return false;
    }
    $a = strrev($a);
    $b = strrev($b);
    $carry = 0;
    $res = '';
    $len = strlen($a);
    for ($i = 0; $i < $len; $i++) {
        $da = (int)$a[$i];
        $db = $i < strlen($b) ? (int)$b[$i] : 0;
        $digit = $da - $db - $carry;
        if ($digit < 0) {
            $digit += 10;
            $carry = 1;
        } else {
            $carry = 0;
        }
        $res .= $digit;
    }
    $res = strrev($res);
    $res = ltrim($res, '0');
    return $res === '' ? '0' : $res;
}

function sb_big_mod2($a)
{
    return ((int)substr($a, -1)) & 1;
}

function sb_big_div2($a)
{
    $result = '';
    $carry = 0;
    $len = strlen($a);
    for ($i = 0; $i < $len; $i++) {
        $num = $carry * 10 + (int)$a[$i];
        $result .= (int)($num / 2);
        $carry = $num % 2;
    }
    $result = ltrim($result, '0');
    return $result === '' ? '0' : $result;
}

define('SB_HOST', SB_WP_URL);
define('SB_URL', SB_WP_URL);

function steamOauth() {
    $openid = new LightOpenID(SB_HOST);
    if(!$openid->mode) {
        $openid->identity = 'https://steamcommunity.com/openid';
        header("Location: " .$openid->authUrl() );
        exit();
    }
    elseif($openid->mode == 'cancel') {
        // User canceled auth.
        return false;
    } else {
        if($openid->validate()) {
            $id = $openid->identity;
            $ptn = "/^https:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
            preg_match($ptn, $id, $matches);

            if(!empty($matches[1])){ return $matches[1]; }
            return null;
        } else {
            // Not valid
            return false;
        }
    }
}


if(isset($_COOKIE['aid'])){
    header("Location: " .SB_URL);
}

$data = steamOauth();

if($data !== false){
    $data = sb_convert64to32($data);

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if(defined('DB_PREFIX')){ $prfx = DB_PREFIX ."_"; }else{ $prfx = ""; }

    $resultado = $mysqli->query("SELECT aid,password FROM " .$prfx ."admins WHERE authid = '" .$data ."'; ");
    if($resultado->num_rows == 1){
        list($aid, $password) = $resultado->fetch_row();
        global $userbank;
        if (empty($password) || $password == $userbank->encrypt_password('')) {
            header("Location: " .SB_URL ."/index.php?p=login&m=empty_pwd");
            die;
        } else {
            setcookie("aid", $aid, time() + LOGIN_COOKIE_LIFETIME);
            setcookie("password", $password, time() + LOGIN_COOKIE_LIFETIME);
        }
    }

    $mysqli->close();
}else{
    header("Location: " .SB_URL ."/index.php?p=login");
}

header("Location: " .SB_URL);
