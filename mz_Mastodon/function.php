<?php
function mz_Mastodon_CreateAPP($name, $url, $force = false)
{
  global $zbp;
  $client_id = $zbp->Config('mz_Mastodon')->client_id;
  $client_secret = $zbp->Config('mz_Mastodon')->client_secret;
  if (empty($name) || empty($url)) {
    return;
  }
  $nameCur = $zbp->Config("mz_Mastodon")->appName;
  $urlCur = $zbp->Config("mz_Mastodon")->tootSite;
  if ($nameCur !== $name || $urlCur !== $url) {
    $force = true;
  }
  if (empty($client_id) || empty($client_secret) || $force) {
    $toot_http = new mz_Mastodon();
    $toot_http->set_url($url);
    $data = $toot_http->create_app($name, array('read'), null, $url);
    $zbp->Config("mz_Mastodon")->appName = $name;
    $zbp->Config("mz_Mastodon")->tootSite = $url;
    $zbp->Config("mz_Mastodon")->client_id = $data["html"]["client_id"];
    $zbp->Config("mz_Mastodon")->client_secret = $data["html"]["client_secret"];
    $zbp->Config("mz_Mastodon")->access_token = "";
    $zbp->Config("mz_Mastodon")->token_type = "";
    $zbp->SaveConfig('mz_Mastodon');
  }
}

function mz_Mastodon_Login($email, $password)
{
  global $zbp;
  $tootSite = $zbp->Config('mz_Mastodon')->tootSite;
  $client_id = $zbp->Config('mz_Mastodon')->client_id;
  $client_secret = $zbp->Config('mz_Mastodon')->client_secret;
  if (empty($tootSite) || empty($client_id) || empty($client_secret)  || empty($email) || empty($password)) {
    return;
  }
  $toot_http = new mz_Mastodon();
  $toot_http->set_url($tootSite);
  $toot_http->set_client($client_id, $client_secret);
  $data = $toot_http->login($email, $password);

  $zbp->Config("mz_Mastodon")->access_token = $data["html"]["access_token"];
  $zbp->Config("mz_Mastodon")->token_type = $data["html"]["token_type"];
  $zbp->SaveConfig('mz_Mastodon');
}

function mz_Mastodon_Get($userid = "")
{
  global $zbp;

  $tootSite = $zbp->Config('mz_Mastodon')->tootSite;
  $client_id = $zbp->Config('mz_Mastodon')->client_id;
  $client_secret = $zbp->Config('mz_Mastodon')->client_secret;
  $access_token =  $zbp->Config("mz_Mastodon")->access_token;
  $token_type = $zbp->Config("mz_Mastodon")->token_type;

  $toot_http = new mz_Mastodon();
  $toot_http->set_url($tootSite);
  $toot_http->set_client($client_id, $client_secret);
  $toot_http->set_token($access_token, $token_type);

  if (is_string($userid)) {
    // $data = $toot_http->accounts($userid);
    $data = $toot_http->accounts_statuses($userid);
    if (is_array($data) && isset($data["html"])) {
      $data["html"]["code"] = 200;
      return $data["html"];
    }
  }
  return array("code" => 404);
}

// 缓存用户头像到本地
function mz_Mastodon_CacheAvatar($remoteUrl)
{
  $localPath = mz_Mastodon_Path("usr/avatar.png", "path");
  // 获取远程头像
  $http = Network::Create();
  $http->open("get", $remoteUrl);
  $http->send();
  if ($http->status != 200) {
    $avatarUrl = $remoteUrl;
    return $avatarUrl;
  } else {
    $httpRes = $http->responseText;
  }
  file_put_contents($localPath, $httpRes);
  $avatarUrl = mz_Mastodon_Path("usr/avatar.png", "host");
  return $avatarUrl;
}

function mz_Mastodon_ParseUsrInfo($userID, $force = false)
{
  global $zbp;
  $userInfo = array();
  $pickList = array(
    'id'       => null,
    'username'  => null,
    'acct'    => null,
    'display_name'     => null,
    'avatar' => null,
    'url' => null,
  );
  $curTime = date("YmdH");
  $lstTime = $zbp->Config('mz_Mastodon')->lstTime;
  if ($lstTime === $curTime && !$force) {
    $userInfo = $zbp->Config('mz_Mastodon')->userInfo;
    return $userInfo;
  }
  if (is_string($userID)) {
    $data = mz_Mastodon_Get($userID);
    if ($data["code"] === 200) {
      // 用户信息获取
      $account = $data[0]["account"];
      $userInfo = array_intersect_key($account, $pickList);
      // 头像缓存
      $userInfo["avatar"] = mz_Mastodon_CacheAvatar($userInfo["avatar"]);
      // 写入侧栏
      mz_Mastodon_UpdateTootMod($data, $userInfo);
      // 缓存到配置项
      $zbp->Config('mz_Mastodon')->userInfo = $userInfo;
      $zbp->Config('mz_Mastodon')->lstTime = $curTime;
      $zbp->SaveConfig('mz_Mastodon');
    }
  }
  return $userInfo;
}

function mz_Mastodon_UpdateTootMod($toots = array(), $userInfo)
{
  global $zbp;
  $zbp->LoadModules();
  $modName = "mastodon-toot";
  $mod = $zbp->GetModuleByFileName($modName);
  if ($mod->ID == 0) {
    $mod->Name = "「Mastodon」嘟特模块";
    $mod->Source = "mz_Mastodon";
    $mod->Type = "div";
    $mod->FileName = $modName;
    $mod->HtmlID = $mod->FileName;
    $mod->IsHideTitle = true;
  }
  $pickList = array(
    'url'       => null,
    'content'  => null,
    'created_at'    => null,
  );
  $tplToot = file_get_contents(mz_Mastodon_Path("tpl-toot"));
  foreach ($userInfo as $key => $value) {
    $tplToot = str_replace("-user.{$key}-", $value, $tplToot);
  }
  $tootsCount = (int)$zbp->Config('mz_Mastodon')->tootsCount;
  $rlt = "<div class=\"mz-toot-list\">";
  foreach ($toots as $key => $toot) {
    if ("code" === "key") {
      continue;
    }
    if ($tootsCount <= 0) {
      break;
    }
    $tootsCount--;
    $curToot = $tplToot;
    $curDta = array_intersect_key($toot, $pickList);
    $curDta["time"] = date("Y-m-d H:i:s", strtotime($curDta["created_at"]));
    $curDta["content"] = preg_replace("/<\/?span>/", "", $curDta["content"]);

    foreach ($curDta as $k => $v) {
      $curToot = str_replace("-toot.{$k}-", $v, $curToot);
    }
    $rlt .= $curToot;
  }
  $rlt .= "</div>";
  $rlt = str_replace("<br />", "", $rlt);
  $mod->Content = $rlt;
  $mod->Save();
}

function mz_Mastodon_UsrInfoDisplay($userID, $force = false)
{
  $rtl = "";
  if (empty($userID) || !$force) {
    return "";
  }
  foreach (mz_Mastodon_ParseUsrInfo($userID) as $key => $value) {
    $rtl .= "{$key}：{$value}<br>";
  }
  return $rtl;
}
