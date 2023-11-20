<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
  $zbp->ShowError(6);
  die();
}
if (!$zbp->CheckPlugin('mz_Mastodon')) {
  $zbp->ShowError(48);
  die();
}

$act = GetVars('act', 'GET');
$suc = GetVars('suc', 'GET');
if ($act == 'save') {
  CheckIsRefererValid();

  $zbp->Config('mz_Mastodon')->userID = trim(GetVars("userID", "POST"));
  $zbp->Config('mz_Mastodon')->tootsCount = trim(GetVars("tootsCount", "POST"));
  $zbp->SaveConfig('mz_Mastodon');

  mz_Mastodon_CreateAPP($_POST["appName"], $_POST["tootSite"]);

  $userID = $zbp->Config('mz_Mastodon')->userID;
  mz_Mastodon_ParseUsrInfo($userID, true);

  // mz_Mastodon_Login($_POST["read_email"], $_POST["read_password"]);
  $zbp->BuildTemplate();
  $zbp->SetHint('good');
  Redirect('./main.php' . ($suc == null ? '' : "?act={$suc}"));
}

$blogtitle = '嘟特（长毛象）接入';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle; ?></div>
  <div class="SubMenu">
    <a href="main.php" title="首页"><span class="m-left m-now">首页</span></a>
    <?php require "about.php"; ?>
  </div>
  <div id="divMain2">
    <form action="<?php echo BuildSafeURL("main.php?act=save"); ?>" method="post">
      <table width="100%" class="tableBorder">
        <tr>
          <th width="10%">项目</th>
          <th>内容</th>
          <th width="45%">说明</th>
        </tr>
        <tr>
          <td>实例URL：</td>
          <td><?php zbpform::text("tootSite", $zbp->Config("mz_Mastodon")->tootSite, "90%"); ?></td>
          <td></td>
        </tr>
        <tr>
          <td>应用名：</td>
          <td><?php zbpform::text("appName", $zbp->Config("mz_Mastodon")->appName, "90%"); ?></td>
          <td></td>
        </tr>
        <tr>
          <td>回显：</td>
          <td>
            <p><i class="readonly"></i><?php zbpform::text("client_id", $zbp->Config("mz_Mastodon")->client_id, "73%"); ?> <label for="client_id">ID</label></p>
            <p><i class="readonly"></i><?php zbpform::text("client_secret", $zbp->Config("mz_Mastodon")->client_secret, "73%"); ?> <label for="client_secret">secret</label></p>
            <!-- <p><?php zbpform::text("access_token", $zbp->Config("mz_Mastodon")->access_token, "73%"); ?>token</p>
            <p><?php zbpform::text("token_type", $zbp->Config("mz_Mastodon")->token_type, "73%"); ?>token_type</p> -->
          </td>
          <td>设置其他项或登录后自动生成</td>
        </tr>
        <tr>
          <td>抓取数量：</td>
          <td><?php zbpform::text("tootsCount", $zbp->Config("mz_Mastodon")->tootsCount, "73%"); ?></td>
          <td></td>
        </tr>
        <tr>
          <td>用户 ID：</td>
          <td>
            <p><?php zbpform::text("userID", $zbp->Config("mz_Mastodon")->userID, "73%"); ?></p>
            <p>
              <?php
              if ($zbp->Config("mz_Mastodon")->userID) {
                echo mz_Mastodon_UsrInfoDisplay($zbp->Config("mz_Mastodon")->userID);
              }
              ?>
            </p>
          </td>
          <td>此处需要数字 ID</td>
        </tr>
        <!-- <tr>
          <td>获取鉴权</td>
          <td>
            <p><?php zbpform::text("read_email", "", "90%"); ?></p>
            <p><?php zbpform::text("read_password", "", "90%"); ?></p>
          </td>
          <td></td>
        </tr> -->
        <tr>
          <td></td>
          <td colspan="2"><input type="submit" value="提交" /></td>
        </tr>
      </table>
    </form>
  </div>
</div>
<script src="mz-ui/mizu.js"></script>
<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
