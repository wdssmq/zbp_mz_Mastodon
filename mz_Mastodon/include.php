<?php
require __DIR__ . "/class/Mastodon_api.php";
require __DIR__ . "/function.php";
#注册插件
RegisterPlugin("mz_Mastodon", "ActivePlugin_mz_Mastodon");

function ActivePlugin_mz_Mastodon()
{
  global $zbp;
  Add_Filter_Plugin('Filter_Plugin_ViewAuto_Begin', 'mz_Mastodon_InsertCSS');
  Add_Filter_Plugin('Filter_Plugin_ViewFeed_End', 'mz_Mastodon_AutoRenew');
}
function mz_Mastodon_InsertCSS()
{
  global $zbp;
  $style = mz_Mastodon_Path("css/plugin.css", "host");
  $zbp->header .= "<link rel=\"stylesheet\" href=\"{$style}\">";
}
function mz_Mastodon_AutoRenew()
{
  global $zbp;
  $userID = $zbp->Config('mz_Mastodon')->userID;
  mz_Mastodon_ParseUsrInfo($userID);
}
function mz_Mastodon_Path($file, $t = 'path')
{
  global $zbp;
  $result = $zbp->$t . 'zb_users/plugin/mz_Mastodon/';
  switch ($file) {
    case 'tpl-toot':
      return $result . 'tpl/toot.html';
      break;
    case 'usr':
      return $result . 'usr/';
      break;
    case 'var':
      return $result . 'var/';
      break;
    case 'main':
      return $result . 'main.php';
      break;
    default:
      return $result . $file;
  }
}
function InstallPlugin_mz_Mastodon()
{
  global $zbp;
  if (!$zbp->HasConfig('mz_Mastodon')) {
    $zbp->Config('mz_Mastodon')->version = 1;
    $zbp->Config('mz_Mastodon')->tootsCount = 10;
    $zbp->SaveConfig('mz_Mastodon');
  }
}
function UninstallPlugin_mz_Mastodon()
{
  global $zbp;
  $modName = "mastodon-toot";
  $mod = $zbp->GetModuleByFileName($modName);
  $mod->Del();
}
