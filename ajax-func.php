<?php
if (!defined('ABSPATH')) {
  die('Invalid request.');
}
// header('Access-Control-Allow-Origin: *');
header("Content-type: application/json");
header('HTTP/1.1 200 OK');
if (!YrActivityLink_is_plugin_active('yr-activity-link/yr-activity-link.php')) {
  return YrActivityLink_JsonError(999, '插件未启用，请在管理界面启用', array());
  die();
}
global $wpdb, $plugin_table_name, $action;
$plugin_table_name = $wpdb->prefix . 'plugin_yr_activity_link';
$action = YrActivityLink_GetVars('act', 'GET');
if (empty($action)) {
  die('非法访问，已记录');
}
$nonce = YrActivityLink_GetVars('csrfToken', 'GET');
$checkCSRFTokenValidResult = wp_verify_nonce($nonce, 'yractivitylink');
if (!$checkCSRFTokenValidResult && !in_array($action, array('getId', 'type3TemplateContent', 'backendRenderTemplate', 'customPluginStyles'))) {
  return YrActivityLink_JsonError(999, 'token已失效，请尝试刷新页面或者退出登录重新进入即可！！！', array());
  die();
}
switch ($action) {
  case 'add':
    YrActivityLink_Add();
    die();
  case 'edit':
    YrActivityLink_Edit();
    die();
  case 'list':
    YrActivityLink_List();
    die();
  case 'delete':
    YrActivityLink_Delete();
    die();
  case 'getId':
    YrActivityLink_Get_Id();
    die();
  case 'settingAdd':
    YrActivityLink_Setting();
    die();
  case 'settingEdit':
    YrActivityLink_Setting();
    die();
  case 'settingGet':
    YrActivityLink_Setting();
    die();
  case 'dataExport':
    YrActivityLink_Data_Export();
    die();
  case 'dataImport':
    YrActivityLink_Data_Import();
    die();
  case 'type3TemplateContent':
    YrActivityLink_Type_3_Custom_Content_Template();
    die();
  case 'backendRenderTemplate':
    YrActivityLink_Backend_Render_Template();
    die();
  case 'customPluginStyles':
    YrActivityLink_Custom_Plugin_Styles();
    die();
  default:
    # code...
    break;
}
function YrActivityLink_Add()
{
  /*
  {
    title: 标题,
    pc:{
      link:
    },
    android:{
      link:
    },
    ios:{
      link:
    },
    checkLinkKeywords:"关键词1|关键词2",
    popupTopTitle: 弹窗顶部标题,
    popupMiddleTitle: 弹窗中部标题,
    popupBottomTitle: 弹窗底部标题,
  }
  */
  global $wpdb, $plugin_table_name;

  $title = YrActivityLink_GetVarsAndTrim('title', 'POST');

  $pc = YrActivityLink_GetVarsAndTrim('pc', 'POST');
  $pcLink = YrActivityLink_array_key_exists('link', $pc) ? $pc['link'] : null;
  $pcEnabledQrCode = false;
  if (YrActivityLink_array_key_exists('enabledQrCode', $pc)) {
    $pc['enabledQrCode'] = (int)$pc['enabledQrCode'];
    $pcEnabledQrCode = $pc['enabledQrCode'];
  } else {
    $pc['enabledQrCode'] = 1;
    $pcEnabledQrCode = false;
  }

  $android = YrActivityLink_GetVarsAndTrim('android', 'POST');
  $androidLink = YrActivityLink_array_key_exists('link', $android) ? $android['link'] : null;

  $ios = YrActivityLink_GetVarsAndTrim('ios', 'POST');
  $iosLink = YrActivityLink_array_key_exists('link', $ios) ? $ios['link'] : null;

  $popupTopTitle = YrActivityLink_GetVarsAndTrim('popupTopTitle', 'POST');
  $popupMiddleTitle = YrActivityLink_GetVarsAndTrim('popupMiddleTitle', 'POST');
  $popupBottomTitle = YrActivityLink_GetVarsAndTrim('popupBottomTitle', 'POST');

  $type = YrActivityLink_GetVarsAndTrim('type', 'POST'); //展示的类型 1-基本 2-app展示
  $type_2_logo = YrActivityLink_GetVarsAndTrim('type_2_logo', 'POST'); //app的logo
  $type_2_name = YrActivityLink_GetVarsAndTrim('type_2_name', 'POST'); //app的名称
  $type_2_type = YrActivityLink_GetVarsAndTrim('type_2_type', 'POST'); //app的类型
  $type_2_desc = YrActivityLink_GetVarsAndTrim('type_2_desc', 'POST'); //app的描述
  $type_2_danmu_list = YrActivityLink_GetVarsAndTrim('type_2_danmu_list', 'POST'); //app中的弹幕列表

  $checkLinkKeywords = YrActivityLink_GetVarsAndTrim('checkLinkKeywords', 'POST');
  $type_3_templateContent = YrActivityLink_GetVarsAndTrim('templateContent', 'POST'); //type3的模板内容
  $insertContentData = array();
  switch ($type) {
    case '3':
      $insertContentData = array(
        'title' => $title ? $title : null,
        'type' => $type,
        'templateContent' => $type_3_templateContent
      );
      break;
    default:
      $insertContentData = array(
        'title' => $title ? $title : null,
        'pc' => $pc,
        'android' => $android,
        'ios' => $ios,
        'popupTopTitle' => $popupTopTitle ? $popupTopTitle : null,
        'popupMiddleTitle' => $popupMiddleTitle ? $popupMiddleTitle : null,
        'popupBottomTitle' => $popupBottomTitle ? $popupBottomTitle : null,
        'checkLinkKeywords' => $checkLinkKeywords ? $checkLinkKeywords : null,
        'type' => $type ? $type : '1',
        'type_2_logo' => $type_2_logo ? $type_2_logo : null,
        'type_2_name' => $type_2_name ? $type_2_name : null,
        'type_2_type' => $type_2_type ? $type_2_type : null,
        'type_2_desc' => $type_2_desc ? $type_2_desc : null,
        'type_2_danmu_list' => $type_2_danmu_list ? $type_2_danmu_list : array(),
      );
      break;
  }
  if ($type !== '3' && preg_match('/\(|\)|\/|\\\/', $checkLinkKeywords)) {
    return YrActivityLink_JsonError(999, '检测链接包含关键词含有特殊字符！！！', array());
  }
  if ($type !== '3' && (!$title || !$pcLink || !$androidLink || !$iosLink)) {
    return YrActivityLink_JsonError(999, '参数缺失', array());
  } else {
    $data = array(
      'content' => array_merge($_POST, $insertContentData),
      'createTime' => time(),
      'updateTime' => time(),
    );
    $data['content'] = YrActivityLink_JsonEncode($data['content']);
    $result = $wpdb->insert($plugin_table_name, $data);
    if ($result) {
      return YrActivityLink_JsonSuccess(array('id' => $result), '新增成功');
    } else {
      return YrActivityLink_JsonError(999, '新增失败', $result);
    }
  }
}

function YrActivityLink_Edit()
{
  global $wpdb, $plugin_table_name;
  $id = YrActivityLink_GetVarsAndTrim('id', 'POST');
  $title = YrActivityLink_GetVarsAndTrim('title', 'POST');

  $pc = YrActivityLink_GetVarsAndTrim('pc', 'POST');
  $pcLink = YrActivityLink_array_key_exists('link', $pc) ? $pc['link'] : null;
  $pcEnabledQrCode = false;
  if (YrActivityLink_array_key_exists('enabledQrCode', $pc)) {
    $pc['enabledQrCode'] = (int)$pc['enabledQrCode'];
    $pcEnabledQrCode = $pc['enabledQrCode'];
  } else {
    $pc['enabledQrCode'] = 1;
    $pcEnabledQrCode = false;
  }

  $android = YrActivityLink_GetVarsAndTrim('android', 'POST');
  $androidLink = YrActivityLink_array_key_exists('link', $android) ? $android['link'] : null;

  $ios = YrActivityLink_GetVarsAndTrim('ios', 'POST');
  $iosLink = YrActivityLink_array_key_exists('link', $ios) ? $ios['link'] : null;

  $popupTopTitle = YrActivityLink_GetVarsAndTrim('popupTopTitle', 'POST');
  $popupMiddleTitle = YrActivityLink_GetVarsAndTrim('popupMiddleTitle', 'POST');
  $popupBottomTitle = YrActivityLink_GetVarsAndTrim('popupBottomTitle', 'POST');

  $popupTopTitle = YrActivityLink_GetVarsAndTrim('popupTopTitle', 'POST');
  $popupMiddleTitle = YrActivityLink_GetVarsAndTrim('popupMiddleTitle', 'POST');
  $popupBottomTitle = YrActivityLink_GetVarsAndTrim('popupBottomTitle', 'POST');

  $type = YrActivityLink_GetVarsAndTrim('type', 'POST'); //展示的类型 1-基本 2-app展示
  $type_2_logo = YrActivityLink_GetVarsAndTrim('type_2_logo', 'POST'); //app的logo
  $type_2_name = YrActivityLink_GetVarsAndTrim('type_2_name', 'POST'); //app的名称
  $type_2_type = YrActivityLink_GetVarsAndTrim('type_2_type', 'POST'); //app的类型
  $type_2_desc = YrActivityLink_GetVarsAndTrim('type_2_desc', 'POST'); //app的描述
  $type_2_danmu_list = YrActivityLink_GetVarsAndTrim('type_2_danmu_list', 'POST'); //app中的弹幕列表

  $checkLinkKeywords = YrActivityLink_GetVarsAndTrim('checkLinkKeywords', 'POST');
  $type3TemplateContent = YrActivityLink_GetVarsAndTrim('type3TemplateContent', 'POST'); //type3的模板内容

  $insertContentData = array();
  switch ($type) {
    case '3':
      $insertContentData = array(
        'title' => $title ? $title : null,
        'type' => $type,
        'type3TemplateContent' => $type3TemplateContent
      );
      break;
    default:
      $insertContentData = array(
        'title' => $title ? $title : null,
        'pc' => YrActivityLink_appointKeysToAppointType($pc, array('checkNum', 'checkTime', 'views', 'failNum'), 'int'),
        'android' => YrActivityLink_appointKeysToAppointType($android, array('checkNum', 'checkTime', 'views', 'failNum'), 'int'),
        'ios' => YrActivityLink_appointKeysToAppointType($ios, array('checkNum', 'checkTime', 'views', 'failNum'), 'int'),
        'popupTopTitle' => $popupTopTitle ? $popupTopTitle : null,
        'popupMiddleTitle' => $popupMiddleTitle ? $popupMiddleTitle : null,
        'popupBottomTitle' => $popupBottomTitle ? $popupBottomTitle : null,
        'checkLinkKeywords' => $checkLinkKeywords ? $checkLinkKeywords : null,
        'type' => $type ? $type : '1',
        'type_2_logo' => $type_2_logo ? $type_2_logo : null,
        'type_2_name' => $type_2_name ? $type_2_name : null,
        'type_2_type' => $type_2_type ? $type_2_type : null,
        'type_2_desc' => $type_2_desc ? $type_2_desc : null,
        'type_2_danmu_list' => $type_2_danmu_list ? $type_2_danmu_list : array(),
      );
      break;
  }
  preg_match('/\(|\)|\/|\\\/', $checkLinkKeywords, $checkLinkKeywordsMatchResult);
  if ($type !== '3' && !empty($checkLinkKeywordsMatchResult)) {
    return YrActivityLink_JsonError(999, '检测链接包含关键词含有特殊字符！！！', array());
  }

  if ($type !== '3' && (!$id || !$title || !$pcLink || !$androidLink || !$iosLink)) {
    return YrActivityLink_JsonError(999, '参数缺失', array());
  } else {
    $oldData = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$plugin_table_name` WHERE `id` = %d", (int)$id), ARRAY_A);
    if (!$oldData) {
      return YrActivityLink_JsonError(999, '更新失败', array());
    }
    $data = array(
      'content' => array_merge(json_decode(($oldData[0]['content']), true), $_POST, $insertContentData),
      'updateTime' => time(),
    );
    $data['content'] = YrActivityLink_JsonEncode($data['content']);

    $result = $wpdb->update($plugin_table_name, $data, array('id' => (int)$id));
    if ($result) {
      return YrActivityLink_JsonSuccess(array(), '更新成功');
    } else {
      return YrActivityLink_JsonError(999, '更新失败', $result);
    }
  }
}

function YrActivityLink_List()
{
  global $wpdb, $plugin_table_name;
  $page = YrActivityLink_GetVarsAndTrim('page', 'GET');
  $size = YrActivityLink_GetVarsAndTrim('size', 'GET');
  if (!$page || !$size) {
    return YrActivityLink_JsonError(999, '参数缺失', array());
  }
  $m = ((int)$page - 1) * (int)$size;
  $n = (int)$size;
  $searchField = YrActivityLink_GetVarsAndTrim('searchField', 'GET');
  $searchFieldContent = YrActivityLink_GetVarsAndTrim('searchFieldContent', 'GET');
  $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) AS total FROM `$plugin_table_name` WHERE (substring_index(substring_index(`content`,%s,-1), %s,1)) LIKE %s ORDER BY `id` DESC", "\"$searchField\":\"", '"', '%' . $wpdb->esc_like(is_null($searchField) || is_null($searchFieldContent) ? "" : $searchFieldContent) . '%'));
  $searchResult =  $wpdb->get_results($wpdb->prepare("SELECT * FROM `$plugin_table_name` WHERE (substring_index(substring_index(`content`,%s,-1), %s,1)) LIKE %s ORDER BY `id` DESC LIMIT %d OFFSET %d", "\"$searchField\":\"", '"', $wpdb->esc_like(is_null($searchField) || is_null($searchFieldContent) ? "" : $searchFieldContent) . '%', $n, $m), ARRAY_A);
  $returnData = array(
    'page' => (int)$page,
    'size' => (int)$size,
    'total' => (int)$total,
    'list' => array()
  );
  foreach ($searchResult as $item) {
    // if($item['id']==='5'){
    //   $item['content']=htmlspecialchars_decode($item['content']);
    //   var_dump($item['content']);
    //   var_dump(json_decode($item['content'],true));
    //   var_dump(json_last_error_msg());
    //   var_dump(json_decode($searchResult[2]['content'],true));
    //   die();
    // }
    $content = array_merge(
      array('id' => $item['id']),
      YrActivityLink_array_value_htmlspecialchars_decode(json_decode($item['content'], true)),
      array('createTime' => $item['createTime']),
      array('updateTime' => $item['updateTime'])
    );
    array_push($returnData['list'], $content);
  }
  if (isset($searchResult) && isset($total)) {
    return YrActivityLink_JsonSuccess($returnData, '获取成功');
  } else {
    return YrActivityLink_JsonError(999, '获取失败', array());
  }
}

function YrActivityLink_Delete()
{
  global $wpdb, $plugin_table_name;
  $id = YrActivityLink_GetVarsAndTrim('id', 'POST');
  if (!$id) {
    return YrActivityLink_JsonError(999, 'id参数缺失', array());
  } else {
    $result =  $wpdb->query("DELETE FROM `$plugin_table_name` WHERE (`id` IN ($id))");
    if (isset($result)) {
      return YrActivityLink_JsonSuccess($result, '删除成功');
    } else {
      return YrActivityLink_JsonError(999, '删除失败', array());
    }
  }
}

function YrActivityLink_Get_Id()
{
  global $wpdb, $plugin_table_name;
  $id = YrActivityLink_GetVarsAndTrim('id', 'POST');
  if (!$id) {
    return YrActivityLink_JsonError(999, 'id参数缺失', array());
  } else {
    $result =  $wpdb->get_results($wpdb->prepare("SELECT * FROM `$plugin_table_name` WHERE `id` = %d", $id), ARRAY_A);
    if (empty($result)) {
      return YrActivityLink_JsonError(999, '数据获取出错', array());
    }
    //id
    $id = (int)$result[0]['id'];

    $content = htmlspecialchars_decode($result[0]['content']);
    $createTime = $result[0]['createTime'];
    $updateTime = $result[0]['updateTime'];
    $contentDecode = json_decode($content, true);

    //活动标题
    $title = $contentDecode['title'];

    //检测链接包含关键词
    $checkLinkKeywords = $contentDecode['checkLinkKeywords'];

    //pc
    $pc = &$contentDecode['pc'];
    $pc_link = $pc['link'];
    $pc_enabledQrCode = $pc['enabledQrCode'];
    $pc_checkTime = YrActivityLink_GetValueInArray($pc, 'checkTime', null);
    $pc_checkNum = (int)YrActivityLink_GetValueInArray($pc, 'checkNum', 0);
    $pc['checkTime'] = $pc_checkTime;
    $pc['checkNum'] = $pc_checkNum;

    //android
    $android = &$contentDecode['android'];
    $android_link = $android['link'];
    $android_checkTime = YrActivityLink_GetValueInArray($android, 'checkTime', null);
    $android_checkNum = (int)YrActivityLink_GetValueInArray($android, 'checkNum', 0);
    $android['checkTime'] = $android_checkTime;
    $android['checkNum'] = $android_checkNum;

    //ios
    $ios = &$contentDecode['ios'];
    $ios_link = $ios['link'];
    $ios_checkTime = YrActivityLink_GetValueInArray($ios, 'checkTime', null);
    $ios_checkNum = (int)YrActivityLink_GetValueInArray($ios, 'checkNum', 0);
    $ios['checkTime'] = $ios_checkTime;
    $ios['checkNum'] = $ios_checkNum;

    //弹窗
    $popupTopTitle = $contentDecode['popupTopTitle'];
    $popupMiddleTitle = $contentDecode['popupMiddleTitle'];
    $popupBottomTitle = $contentDecode['popupBottomTitle'];

    if (get_option('YrActivityLink')) {
      $YrActivityLink_option = get_option('YrActivityLink');
      $linkCheck = array_key_exists('linkCheck', $YrActivityLink_option) ? $YrActivityLink_option['linkCheck'] : array();
      $toIntKey = array('enabled', 'checkInterval', 'maxRemindNum');
      foreach ($linkCheck as $key => $value) {
        if (in_array($key, $toIntKey)) {
          $linkCheck[$key] = (int)$value;
        }
      }
      $linkCheck_enabled = YrActivityLink_GetValueInArray($linkCheck, 'enabled', null);
      $linkCheck_checkInterval = YrActivityLink_GetValueInArray($linkCheck, 'checkInterval', null);
      $linkCheck_maxRemindNum = YrActivityLink_GetValueInArray($linkCheck, 'maxRemindNum', null);
      $linkCheck_remindMode = YrActivityLink_GetValueInArray($linkCheck, 'remindMode', null);
      $linkCheck_comment = YrActivityLink_GetValueInArray($linkCheck, 'comment', null);
      $linkCheck_email = YrActivityLink_GetValueInArray($linkCheck, 'email', null);
      //获取设备类型
      $deviceType = YrActivityLink_GetDeviceType(YrActivityLink_GetVars('HTTP_USER_AGENT', 'SERVER'));
      //最终返回的链接
      $jumpLink = array(
        'pc' => $pc_link,
        'android' => $android_link,
        'ios' => $ios_link
      );
      $jumpLink = $jumpLink[$deviceType];
      $style = '
        <style>
          .ant-modal-root {
            display: none;
          }
          .ant-modal-root.open {
            display: block;
          }
          .ant-modal-mask {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            height: 100%;
            background-color: #00000073;
          }

          .ant-modal-wrap {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            overflow: auto;
            outline: 0;
            -webkit-overflow-scrolling: touch;
            z-index: 99999;
          }

          .ant-modal {
            box-sizing: border-box;
            color: #000000d9;
            font-size: 14px;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
          }

          .ant-modal .ant-modal-content {
            position: relative;
            background-color: #fff;
            background-clip: padding-box;
            border: 0;
            border-radius: 2px;
            box-shadow: 0 3px 6px -4px #0000001f, 0 6px 16px #00000014,
              0 9px 28px 8px #0000000d;
            pointer-events: auto;
          }

          .ant-modal-content .ant-modal-body p:first-child{
            margin-bottom: 6px;
            font-size:16px;
            line-height: 30px;
          }

          .ant-modal-content .ant-modal-header {
            padding: 16px 24px;
            color: #000000d9;
            background: #fff;
            border-bottom: 2px solid #f0f0f0;
            border-radius: 2px 2px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: left;
          }

          .ant-modal-header .ant-modal-title {
            color: #000000d9;
            font-weight: 500;
            font-size: 16px;
            width: calc(100% - 30px);
            min-height: 20px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          }

          .ant-modal-header .ant-modal-close {
            padding: 10px;
            color: #00000073;
            font-weight: 700;
            background: none;
            border: 0;
            outline: 0;
            cursor: pointer;
            transition: color 0.3s;
            position: absolute;
            right: 16px;
            font-size: 22px;
            top: 6px;
          }

          .ant-modal-header .ant-modal-close:hover {
            color: #000000bf;
          }

          .ant-modal-body {
            padding: 14px 24px;
            font-size: 14px;
            line-height: 1.5715;
            text-align: center;
          }

          .ant-modal-body img.link-qrcode {
            display: inline-block;
            border: 6px solid #ea5f00;
            padding: 10px;
          }

          .ant-modal-footer {
            padding: 12px;
            background: 0 0;
            border-top: 2px solid #f0f0f0;
            border-radius: 0 0 2px 2px;
            text-align: center;
          }

          .ant-modal-footer p {
            text-align: center;
            color: #55595c;
            margin: 0;
          }
        </style>
      ';
      $modal = '
        <div class="ant-modal-root">
          <div class="ant-modal-mask"></div>
          <div class="ant-modal-wrap">
            <div class="ant-modal">
              <div class="ant-modal-content">
                <div class="ant-modal-header">
                  <div class="ant-modal-title">' . $popupTopTitle . '</div>
                  <button class="ant-modal-close" title="关闭">
                    <svg
                      viewBox="64 64 896 896"
                      focusable="false"
                      width="1em"
                      height="1em"
                      fill="currentColor"
                    >
                      <path
                        d="M563.8 512l262.5-312.9c4.4-5.2.7-13.1-6.1-13.1h-79.8c-4.7 0-9.2 2.1-12.3 5.7L511.6 449.8 295.1 191.7c-3-3.6-7.5-5.7-12.3-5.7H203c-6.8 0-10.5 7.9-6.1 13.1L459.4 512 196.9 824.9A7.95 7.95 0 00203 838h79.8c4.7 0 9.2-2.1 12.3-5.7l216.5-258.1 216.5 258.1c3 3.6 7.5 5.7 12.3 5.7h79.8c6.8 0 10.5-7.9 6.1-13.1L563.8 512z"
                      ></path>
                    </svg>
                  </button>
                </div>
                <div class="ant-modal-body">
                  ' . (empty($popupMiddleTitle) ? null : '<p>' . $popupMiddleTitle . '</p>') . '
                  <img class="link-qrcode" />
                </div>
                ' . (empty($popupBottomTitle) ? null : '<div class="ant-modal-footer"><p>' . $popupBottomTitle . '</p></div>') . '
              </div>
            </div>
          </div>
        </div>
      ';
      $script = array(
        'popup' => '
          <script>
            (function ($) {
              try {
                let checkQrcodePluginsResult = checkQrcodePlugins();
                if (checkQrcodePluginsResult) {
                  toggleActiveLinkModal();
                } else {
                  let script = document.createElement("script");
                  script.src =
                    "' . YrActivityLink_Path(array('script', 'qrcode.min.js')) . '";
                  document.head.appendChild(script);
                  script.onload = function () {
                    toggleActiveLinkModal();
                  };
                }
                function toggleActiveLinkModal() {
                  $("#active_link-wrapper .ant-modal-close").on("click", () => {
                    $("#active_link-wrapper .ant-modal-root").toggleClass("open");
                  });
                  switch (checkQrcodePluginsResult) {
                    case "qrcode":
                      let result = $("<template></template>")
                        .qrcode("' . $jumpLink . '")
                        .find("canvas")
                        .get(0)
                        .toDataURL();
                      $("#active_link-wrapper .link-qrcode").attr("src", result);
                      $("#active_link-wrapper .ant-modal-root").toggleClass("open");
                      break;
                    case "QRCode":
                    default:
                      QRCode.toDataURL(
                        "' . $jumpLink . '",
                        {
                          errorCorrectionLevel: "H",
                          width: 180,
                          height: 180,
                          margin: 1,
                        },
                        function (err, string) {
                          if (err) return;
                          $("#active_link-wrapper .link-qrcode").attr("src", string);
                          $("#active_link-wrapper .ant-modal-root").toggleClass(
                            "open"
                          );
                        }
                      );
                      break;
                  }
                }
                function checkQrcodePlugins() {
                  if (
                    typeof QRCode !== "undefined" &&
                    typeof QRCode.toCanvas === "function"
                  ) {
                    return "QRCode";
                  }
                  if (
                    typeof jQuery !== "undefined" &&
                    typeof jQuery.prototype.qrcode != "undefined"
                  ) {
                    return "qrcode";
                  }
                  return false;
                }
              } catch (e) {console.error(e)}
            })(jQuery);
          </script>
        ',
        'jump' => $deviceType === 'pc' ?
          '
          <script>
            (() => { window.open("' . $jumpLink . '"); })();
          </script>
          ' :
          '
          <script>
            (() => { window.location.href = "' . $jumpLink . '"; })();
          </script>
        '
      );
      $returnHtmlTempalte = array(
        'popup' => '
          <div id="active_link-wrapper">
            ' . $style . '
            ' . $modal . '
            ' . $script['popup'] . '
          </div>
          ',
        'jump' => '
          <div id="active_link-wrapper">
            ' . $script['jump'] . '
          </div>
        '
      );
      //如果设备类型是pc 且 开启显示二维码
      if ($deviceType === 'pc') {
        if ($pc_enabledQrCode) {
          $returnHtmlTempalte = $returnHtmlTempalte['popup'];
        } else {
          $returnHtmlTempalte = $returnHtmlTempalte['jump'];
        }
      } else {
        $returnHtmlTempalte = $returnHtmlTempalte['jump'];
      }
      //进行压缩处理
      $returnHtmlTempalte = YrActivityLink_HTMLCompressionStr($returnHtmlTempalte);


      //检测间隔配置项
      $allCheckTimeAndNumOptions = array(
        'pc' => array($pc_checkTime, $pc_checkNum),
        'android' => array($android_checkTime, $android_checkNum),
        'ios' => array($ios_checkTime, $ios_checkNum),
      );
      $allCheckTimeAndNumOptions = $allCheckTimeAndNumOptions[$deviceType];

      $checkSpace = is_null($allCheckTimeAndNumOptions[0]) ? 0 : time() - (int)$allCheckTimeAndNumOptions[0];
      $isInCheckSpace =  ($checkSpace == 0 || $checkSpace >= $linkCheck_checkInterval) ? true : false;
      $isExceedCheckNum = $allCheckTimeAndNumOptions[1] >= $linkCheck_maxRemindNum ? true : false;
      // 增加访问次数
      // php5.6不支持这种写法$$deviceType['views']，要不然会报PHP Warning:  Illegal string offset
      $temp_deviceType = &$$deviceType;
      $temp_deviceType['views'] = isset($temp_deviceType['views']) ? (int)$temp_deviceType['views']  + 1 : 1;
      //开启了链接检测 且 存在失效提醒方式 且 在检测间隔内 且没有超过检测次数
      if ($linkCheck_enabled && $linkCheck_remindMode && $isInCheckSpace && !$isExceedCheckNum) {
        $http = new WP_Http;
        $httpResponse = $http->request($jumpLink);
        $getLinkResult = is_wp_error($httpResponse) ? null : $httpResponse['body'];
        $commentContent = "失效信息如下：\n活动名称：${title}\n活动链接：${jumpLink}\n设备类型：${deviceType}";
        $temp_deviceType['checkTime'] = time();
        $temp_deviceType['checkNum'] = $temp_deviceType['checkNum'] + 1;
        if ($getLinkResult) {
          //判断是否包含指定的关键词，对于包含指定的关键词是空的话，默认都会匹配到一个结果的，所以不需要额外判断了
          preg_match("/${checkLinkKeywords}/", $getLinkResult, $matchResult);
          if (empty($matchResult) && $linkCheck_remindMode === 'comment') {
            //失效的次数
            $temp_deviceType['failNum'] = isset($temp_deviceType['failNum']) ? $temp_deviceType['failNum']  + 1 : 1;
            register_shutdown_function('YrActivityLink_PostCheckLinkComment', $linkCheck_comment['id'], "${commentContent}\n失效原因：没有检测到指定的关键词");
          }
        } else {
          if ($linkCheck_remindMode === 'comment') {
            //失效的次数
            $temp_deviceType['failNum'] = isset($temp_deviceType['failNum']) ? $temp_deviceType['failNum']  + 1 : 1;
            register_shutdown_function('YrActivityLink_PostCheckLinkComment', $linkCheck_comment['id'], "${commentContent}\n失效原因：服务器无法访问");
          }
        }
      }
      $upDataData = array(
        'content' => YrActivityLink_JsonEncode($contentDecode)
      );
      //更新新的数据库
      $wpdb->update($plugin_table_name, $upDataData, array('id' => $id));
      return YrActivityLink_JsonSuccess($returnHtmlTempalte, '获取成功');
    } else {
      return YrActivityLink_JsonError(999, '插件配置项不存在', array());
    }
  }
}

function YrActivityLink_Setting()
{
  /*
  linkCheck:{
    enabled:1 || 0,//是否开启检测
    checkInterval：3600,//检测间隔
    maxRemindNum：3,//最大提醒次数
    remindMode:"email" || "comment",
    //评论配置项
    comment:{
      id
    },
    //邮件配置项
    email:{
  
    }
  }
  */
  global $action;
  if ($action === 'settingGet') {
    //获取插件配置信息
    if (get_option('YrActivityLink')) {
      $YrActivityLink_option = get_option('YrActivityLink');
      $linkCheck = array_key_exists('linkCheck', $YrActivityLink_option) ? $YrActivityLink_option['linkCheck'] : array();
      $toIntKey = array('enabled', 'checkInterval', 'maxRemindNum');
      foreach ($linkCheck as $key => $value) {
        if (in_array($key, $toIntKey)) {
          $linkCheck[$key] = (int)$value;
        }
      }
      $backendRenderDispalyTypeTemplate = array_key_exists('backendRenderDispalyTypeTemplate', $YrActivityLink_option) ? $YrActivityLink_option['backendRenderDispalyTypeTemplate'] : null;
      $customPluginStyles = array_key_exists('customPluginStyles', $YrActivityLink_option) ? $YrActivityLink_option['customPluginStyles'] : null;
      $returnData = array(
        'linkCheck' => empty($linkCheck) ? null : $linkCheck,
        'backendRenderDispalyTypeTemplate' => $backendRenderDispalyTypeTemplate,
        'customPluginStyles' => $customPluginStyles
      );
      return YrActivityLink_JsonSuccess($returnData, '获取成功');
    } else {
      return YrActivityLink_JsonError(999, '插件配置项不存在，请重新安装插件后重试', array());
    }
  } else {
    //添加/编辑插件信息
    $linkCheck = YrActivityLink_GetVarsAndTrim('linkCheck', 'POST');
    $backendRenderDispalyTypeTemplate = YrActivityLink_GetVarsAndTrim('backendRenderDispalyTypeTemplate', 'POST');
    $customPluginStyles = YrActivityLink_GetVarsAndTrim('customPluginStyles', 'POST');
    if (!$linkCheck) {
      return YrActivityLink_JsonError(999, '参数缺失', array());
    }
    $toIntKey = array('enabled', 'checkInterval', 'maxRemindNum');
    foreach ($linkCheck as $key => $value) {
      if (in_array($key, $toIntKey)) {
        $linkCheck[$key] = (int)$value;
      }
    }
    if (isset($backendRenderDispalyTypeTemplate['enabled'])) {
      $backendRenderDispalyTypeTemplate['enabled'] = (int)$backendRenderDispalyTypeTemplate['enabled'];
    }
    $enabled = YrActivityLink_array_key_exists('enabled', $linkCheck) ? $linkCheck['enabled'] : null;
    $checkInterval = YrActivityLink_array_key_exists('checkInterval', $linkCheck) ? $linkCheck['checkInterval'] : null;
    $maxRemindNum = YrActivityLink_array_key_exists('maxRemindNum', $linkCheck) ? $linkCheck['maxRemindNum'] : null;

    if (is_null($enabled) || is_null($checkInterval) || is_null($maxRemindNum)) {
      return YrActivityLink_JsonError(999, '参数缺失', array());
    }
    if (get_option('YrActivityLink')) {
      update_option('YrActivityLink', array(
        'linkCheck' => $linkCheck,
        'backendRenderDispalyTypeTemplate' => empty($backendRenderDispalyTypeTemplate) ? null : $backendRenderDispalyTypeTemplate,
        'customPluginStyles' => empty($customPluginStyles) ? null : $customPluginStyles
      ), true);
      return YrActivityLink_JsonSuccess(array(), '保存成功');
    } else {
      return YrActivityLink_JsonError(999, '插件配置项不存在', array());
    }
  }
}

function YrActivityLink_Data_Export()
{
  global $wpdb, $plugin_table_name;
  if ($wpdb->get_var("SHOW TABLES LIKE '$plugin_table_name'") !== $plugin_table_name) {
    return YrActivityLink_JsonError(999, '数据库表不存在，导出失败！！', array());
  }
  if (!get_option('YrActivityLink')) {
    return YrActivityLink_JsonError(999, '插件配置信息不存在，导出失败！！', array());
  }
  $pluginConfigData = get_option('YrActivityLink');
  $pluginConfigDataArr = array();
  foreach ($pluginConfigData as $key => $value) {
    $pluginConfigDataArr[$key] = $value;
  }
  //插件配置信息
  $pluginConfigDataArrStr = json_encode($pluginConfigDataArr);
  $fullSqlArr = array();
  $dropTableIfExists = "DROP TABLE IF EXISTS `$plugin_table_name`;";
  $tableStructure = $wpdb->get_results("SHOW CREATE TABLE `$plugin_table_name`", ARRAY_A)[0]['Create Table'] . ';';
  $tableData = $wpdb->get_results("SELECT * FROM `$plugin_table_name`", ARRAY_A);
  array_push($fullSqlArr, $pluginConfigDataArrStr, $dropTableIfExists, $tableStructure);
  foreach ($tableData as $value) {
    $keys = array_keys($value);
    $keys = array_map(function ($curKey) {
      return "`{$curKey}`";
    }, $keys);
    $keys = join(',', $keys);
    $values = array_values($value);
    $values = array_map(function ($curValue) {
      return "'" . addslashes($curValue) . "'";
    }, $values);
    $values = join(',', $values);
    $insertSqlStr = "INSERT INTO `$plugin_table_name` ($keys) VALUES ($values);";
    array_push($fullSqlArr, $insertSqlStr);
  }
  $fullSqlArrToStr = implode("我是分割符", $fullSqlArr);
  return YrActivityLink_JsonSuccess(array(
    "content" => base64_encode($fullSqlArrToStr)
  ), '备份成功，请及时下载保存，以免失效！');
}

function YrActivityLink_Data_Import()
{
  global $wpdb;
  // $A=GetVars('REQUEST_METHOD', 'SERVER');//获取请求方式
  $file = YrActivityLink_GetVars('file', 'FILES'); //获取文件信息
  // 如果上传的是个空的后缀名的话，如果不加【PATHINFO_EXTENSION】常量会报错
  $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
  if ($fileExt !== 'YrActivityLink') {
    return YrActivityLink_JsonError(999, '备份文件格式错误！', array());
  }
  $fileContent = file_get_contents($file['tmp_name']);
  if (is_bool($fileContent)) {
    return YrActivityLink_JsonError(999, '备份文件数据读取失败，请重新上传重新！', array());
  }
  $fileContent = base64_decode($fileContent);
  $fileContentArr = explode("我是分割符", $fileContent);
  if (count($fileContentArr) < 2) {
    return YrActivityLink_JsonError(999, '备份文件数据无效，程序无法识别，请重新上传重新！', array());
  }
  $pluginConfigData = array_splice($fileContentArr, 0, 1);
  $pluginConfigData = json_decode($pluginConfigData[0], true);
  update_option('YrActivityLink', $pluginConfigData);
  mysqli_multi_query($wpdb->dbh, implode("", $fileContentArr));
  return YrActivityLink_JsonSuccess(array(), '导入成功');
}

function YrActivityLink_Type_3_Custom_Content_Template()
{
  header('Content-type: text/javascript');
  global $wpdb, $plugin_table_name;;
  $referrer = YrActivityLink_GetVars('referrer', 'GET');
  if (YrActivityLink_is_spider() || YrActivityLink_referer_is_spider($referrer)) {
    die();
  }
  $id = YrActivityLink_GetVars('id', 'GET');
  $request_uri = $_SERVER['REQUEST_URI'];
  if (!$id) {
    die();
  } else {
    $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$plugin_table_name` WHERE `id` = %d", (int)$id), ARRAY_A);
    if (empty($result)) {
      die();
    }
    $content = json_decode($result[0]['content'], true);
    $templateContentData = isset($content['type3TemplateContent']) ? htmlspecialchars_decode($content['type3TemplateContent']) : false;
    if (!$templateContentData) {
      die();
    }
    $js = base64_encode('document.querySelectorAll(`script[src*=\"' . $request_uri . '"]`).forEach(ele=>{
      ele.outerHTML=`' . $templateContentData . '`;
    })');
    echo '(new Function(decodeURIComponent(escape(atob(\'' . $js . '\')))))()';
  }
}

function YrActivityLink_Backend_Render_Template()
{
  header('Content-type: text/javascript');
  // ?ajax.php?act=backendRenderTemplate&params=base64的参数&referrer=
  $params = YrActivityLink_GetVars('params', 'GET');
  // 获取不到参数
  if (is_null($params)) {
    return null;
  }
  // 解码失败
  $params = base64_decode($params);
  if (empty($params)) {
    return null;
  }
  $params = json_decode($params, true);
  // 解码失败
  if (is_null($params)) {
    return null;
  }
  $referrer = YrActivityLink_GetVars('referrer', 'GET');
  $httpReferer = $referrer ? $referrer : @$_SERVER["HTTP_REFERER"]; //获取完整的来路URL
  $parseUrlResult = @parse_url($httpReferer);
  $host = isset($parseUrlResult['host']) ? $parseUrlResult['host'] : false;
  $YrActivityLink_option = get_option('YrActivityLink');
  $backendRenderDispalyTypeTemplate = array_key_exists('backendRenderDispalyTypeTemplate', $YrActivityLink_option) ? $YrActivityLink_option['backendRenderDispalyTypeTemplate'] : null;
  $backendRenderDispalyTypeTemplateDisabledReferrerrUrls = isset($backendRenderDispalyTypeTemplate['disabledReferrerrUrls']) ? $backendRenderDispalyTypeTemplate['disabledReferrerrUrls'] : array();
  $isDisabledReferrerrUrls = in_array($host, $backendRenderDispalyTypeTemplateDisabledReferrerrUrls);
  if ($isDisabledReferrerrUrls) {
    return;
  }
  $request_uri = $_SERVER['REQUEST_URI'];
  $templateContentData = YrActivityLink_My_Shortcode_Func($params, $params['content'], 'ajax.php');
  $js = "";
  if (preg_match('/<script.*?src=["\']([^"\']+)["\'].*?>/', $templateContentData, $srcMatches)) {
    if (empty($srcMatches)) {
      return null;
    }
    $js = base64_encode('document.querySelectorAll(`script[src*=\"' . $request_uri . '"]`).forEach(ele=>{
      const sc = document.createElement(`script`);
      sc.src =`' . $srcMatches[1] . '`;
      sc.async = true;
      ele.parentNode.replaceChild(sc, ele);
    })');
  } else {
    $js =  base64_encode('document.querySelectorAll(`script[src*=\"' . $request_uri . '"]`).forEach(ele=>{
      ele.outerHTML=`' . $templateContentData . '`;
    })');
  }
  echo '(new Function(decodeURIComponent(escape(atob(\'' . $js . '\')))))()';
}

function YrActivityLink_Custom_Plugin_Styles()
{
  header('Content-type: text/css');
  $YrActivityLink_option = get_option('YrActivityLink');
  $customPluginStyles = array_key_exists('customPluginStyles', $YrActivityLink_option) ? $YrActivityLink_option['customPluginStyles'] : null;
  $data = $customPluginStyles;
  if (empty($data)) {
    return;
  }
  echo str_replace(array('\n', '\t', '\r'), '', $data);
}
