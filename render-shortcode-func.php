<?php

/**
 * 前置判断是否需要动态渲染展示类型模板
 */
function YrActivityLink_Pre_Check_Is_Backend_Render_Template($callerName, $attr, $content)
{
    // 调用方为ajax.php 一定要输出模板内容
    // 调用方为空的话 && 且有设置开启动态渲染
    // $callerName !== 'ajax.php' && 开启动态渲染
    $YrActivityLink_option = get_option('YrActivityLink');
    $backendRenderDispalyTypeTemplate = array_key_exists('backendRenderDispalyTypeTemplate', $YrActivityLink_option) ? $YrActivityLink_option['backendRenderDispalyTypeTemplate'] : null;
    $backendRenderDispalyTypeTemplateEnabled = isset($backendRenderDispalyTypeTemplate['enabled']) && $backendRenderDispalyTypeTemplate['enabled'] === 1;
    if ($callerName !== 'ajax.php' && $backendRenderDispalyTypeTemplateEnabled) {
        $paramsData = base64_encode(json_encode(array_merge($attr, array('content' => $content))));
        $src = admin_url('admin-ajax.php') . '?action=YrActivityLink_Api&act=backendRenderTemplate&params=' . $paramsData;
        return YrActivityLink_generate_have_referer_url_schema_script_tag($src);
    }
    return false;
}

add_shortcode('YrActivityLink', 'YrActivityLink_My_Shortcode_Func');

function YrActivityLink_My_Shortcode_Func($attr, $content = null, $callerName = '')
{
    global $wpdb;
    $plugin_table_name = $wpdb->prefix . 'plugin_yr_activity_link';
    if ($callerName === 'ajax.php') {
        add_shortcode('YrActivityLink', function ($attr, $content) {
            return call_user_func('YrActivityLink_My_Shortcode_Func', $attr, $content, 'ajax.php');
        });
    }
    extract(shortcode_atts(array(
        'id' => null,
        'style' => 'cursor: pointer;',
        'ele' => 'a',
        'type' => null
    ), $attr));
    $preCheckResult = YrActivityLink_Pre_Check_Is_Backend_Render_Template($callerName, $attr, $content);
    if (empty($id)) {
        return null;
    } else if ($type) {
        $type_arr = explode("|", $type); //展示type|关联的其他的ID
        if ($preCheckResult)  return $preCheckResult;
        if ($type_arr[0] === '2') {
            $relation_ids = count($type_arr) === 2 ? $type_arr[1] : null;
            $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$plugin_table_name` WHERE `id` IN (%s)", is_null($relation_ids) ? $id : implode(',', array($id, $relation_ids))), ARRAY_A);
            if (!count($result)) {
                return null;
            }
            $template_data = array();
            foreach ($result as $key => $value) {
                $content = json_decode($value['content'], true);
                if (!array_key_exists('type', $content)) {
                    $content['type'] = '1';
                }
                if ($content['type'] !== '2') {
                    return null;
                }
                array_push($template_data, array(
                    "id" => $value['id'],
                    'logo' => array_key_exists("type_$type_arr[0]_logo", $content) ? $content["type_$type_arr[0]_logo"] : null,
                    'name' => array_key_exists("type_$type_arr[0]_name", $content) ? $content["type_$type_arr[0]_name"] : null,
                    'type' => array_key_exists("type_$type_arr[0]_type", $content) ? $content["type_$type_arr[0]_type"] : null,
                    'desc' => array_key_exists("type_$type_arr[0]_desc", $content) ? $content["type_$type_arr[0]_desc"] : null,
                    'danmu_list' => array_key_exists("type_$type_arr[0]_danmu_list", $content) ? $content["type_$type_arr[0]_danmu_list"] : array(),
                ));
            }
            return YrActivityLink_render_app_template($template_data);
        } else if ($type_arr[0] === '3') {
            $result =  $wpdb->get_results($wpdb->prepare("SELECT * FROM `$plugin_table_name` WHERE `id` = %d", (int)$id), ARRAY_A);
            if (!count($result)) {
                return null;
            }
            $content = YrActivityLink_array_value_htmlspecialchars_decode(json_decode($result[0]['content'], true));
            return do_shortcode($content['type3TemplateContent']);
            // return YrActivityLink_render_custom_content_template(array('id' => $id));
        } else if ($type_arr[0] === '4') {
            $result =  $wpdb->get_results($wpdb->prepare("SELECT * FROM `$plugin_table_name` WHERE `id` = %d", (int)$id), ARRAY_A);
            if (!count($result)) {
                return null;
            }
            $template_data = array();
            foreach ($result as $key => $value) {
                $content = json_decode($value['content'], true);
                if ($content['type'] !== '4') {
                    return null;
                }
                array_push($template_data, array(
                    "id" => $value['id'],
                    'app4Image' => array_key_exists("app4Image", $content) ? $content["app4Image"] : null,
                    'app4Title' => array_key_exists("app4Title", $content) ? $content["app4Title"] : null,
                    'app4Tags' => array_key_exists("app4Tags", $content) && is_array($content["app4Tags"]) ? $content["app4Tags"] : array(),
                    'app4Specifications' => array_key_exists("app4Specifications", $content) && is_array($content["app4Specifications"]) ? $content["app4Specifications"] : array(),
                    'app4BtnTitle' => array_key_exists("app4BtnTitle", $content) ? $content["app4BtnTitle"] : null,
                ));
            }
            if (!count($template_data)) {
                return null;
            }
            return YrActivityLink_render_app4_template($template_data[0]);
        } else {
            return "<span data-YrActivityLink-id=\"{$id}\" style='cursor:pointer;color:inherit;'>{$content}</span>";
        }
    } else {
        if ($preCheckResult)  return $preCheckResult;
        return "<span data-YrActivityLink-id=\"{$id}\" style='cursor:pointer;color:inherit;'>{$content}</span>";
    }
}

/**
 * 渲染展示类型APP数据
 * @param array $data 数据
 */
function YrActivityLink_render_app_template($data)
{
    ob_start();
?>
    <div class="yr-app-list">
        <?php
        foreach ($data as $key => $value) {
            echo '
      <div class="yr-app-item" data-yractivitylink-id="' . $value['id'] . '">
      ' . YrActivityLink_render_app_danmu_template($value['danmu_list']) . '
        <img class="yr-app-logo" src="' . $value['logo'] . '" alt="' . $value['name'] . '" />
        <div class="yr-app-main">
          <div class="yr-col yr-name">' . $value['name'] . '</div>
          <div class="yr-col yr-type">类型：' . $value['type'] . '</div>
          <div class="yr-col yr-desc">特点：' . $value['desc'] . '</div>
          <button class="yr-download-btn">点击下载</button>
        </div>
      </div>
      ';
        }
        ?>
    </div>
<?php
    $result = ob_get_clean();
    return $result;
}

/**
 * 渲染展示类型为APP中的弹幕模板
 * @param array $data 数据
 */
function YrActivityLink_render_app_danmu_template($data)
{
    if (count($data) === 0 || !is_array($data)) {
        return null;
    }
    ob_start();
?>
    <div class="swiper-no-swiping yr-swiper-danmu-container">
        <div class="swiper-wrapper yr-swiper-danmu-wrapper">
            <?php
            foreach ($data as $key => $value) {
                echo '      
        <div class="swiper-slide yr-swiper-slide-danmu-container">
          <div class="yr-swiper-slide-danmu-wrapper">
            <img class="yr-swiper-slide-danmu-avatar" src="' . $value['avatar'] . '" />
            <div class="yr-swiper-slide-danmu-content">' . $value['content'] . '</div>
          </div>
        </div>';
            }
            ?>
        </div>
    </div>
<?php
    $result = ob_get_clean();
    return $result;
}

/**
 * 渲染自定义内容模块
 * @param array $data 数据
 */
function YrActivityLink_render_custom_content_template($data)
{
    if (count($data) === 0 || !is_array($data)) {
        return null;
    }
    $id = $data['id'];
    $src = admin_url('admin-ajax.php') . '?action=YrActivityLink_Api&act=type3TemplateContent&id=' . $id . '';
    return YrActivityLink_generate_have_referer_url_schema_script_tag($src);
}

/**
 * 渲染app4-商品卡片
 * @param object $data 数据
 */
function YrActivityLink_render_app4_template($data)
{

    $id = $data['id'];
    $app4Image = $data['app4Image'];
    $app4Title = $data['app4Title'];
    $app4Tags = $data['app4Tags'];
    $app4Specifications = $data['app4Specifications'];
    $app4BtnTitle = $data['app4BtnTitle'];
    ob_start();
?>
    <div class="yr-app4-box">
        <div class="yr-app4-title-container">
            <img class="yr-app4-goods-img" src="<?php echo $app4Image; ?>" />
            <div class="yr-app4-title"><?php echo $app4Title; ?></div>
        </div>
        <div class="yr-app4-tags-container">
            <?php
            foreach ($app4Tags as $key => $value) {
                echo '<span class="yr-app4-tag" style="background-color:' . $value['backgroundColor'] . ';color:' . $value['color'] . '">' . $value['content'] . '</span>';
            }
            ?>
        </div>
        <hr class="yr-app4-hr" />
        <div class="yr-app4-btn" data-yractivitylink-id="<?php echo $id; ?>"><?php echo $app4BtnTitle; ?></div>
        <div class="yr-app4-specifications">
            <?php
            foreach ($app4Specifications as $key => $value) {
                echo '<div class="yr-app4-specifications-title">' . $value['title'] . '<span class="yr-app4-specifications-content">' . $value['content'] . '</span></div>';
            }
            ?>
        </div>
    </div>
<?php
    $result = ob_get_clean();
    return $result;
}
