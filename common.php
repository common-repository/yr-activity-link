<?php

/**
 * 该方法就是去除那些强制加的转义字符
 * wordpress会强制给$_GET`, `$_POST`, `$_COOKIE`, and `$_SERVER添加转义字符，具体实现细节在wp-settings.php/wp_magic_quotes();
 * https://www.npc.ink/8987.html
 */
function YrActivityLink_Remove_Wp_Extra_Add_Slashes()
{
    function _stripslashes(&$var)
    {
        if (is_array($var)) {
            foreach ($var as $k => &$v) {
                _stripslashes($v);
            }
        } else {
            $var = stripslashes($var);
        }
    }
    _stripslashes($_GET);
    _stripslashes($_POST);
    _stripslashes($_COOKIE);
    _stripslashes($_REQUEST);
}


/**
 * 通用的路径
 */
function YrActivityLink_Path($path)
{
    $plugin = plugins_url('/', __FILE__);
    return $plugin . implode("/", $path);
}

/**
 * 判断插件是否激活
 */
function YrActivityLink_is_plugin_active($plugin)
{
    return in_array($plugin, (array) get_option('active_plugins', array())) || is_plugin_active_for_network($plugin);
}

/**
 * 当代码正常运行时，以JSON形式输出信息.
 *
 * @param object 待返回内容
 * @param string $successString 错误内容
 */
function YrActivityLink_JsonSuccess($data, $successString)
{
    YrActivityLink_JsonError(0, $successString, $data);
}

/**
 * 以JSON形式输出错误信息.(err code为(int)0认为是没有错误，所以把0转为1)
 *
 * @param string $errorCode   错误编号
 * @param string $errorString 错误内容
 * @param object $data 具体内容
 */
function YrActivityLink_JsonError($errorCode, $errorString, $data)
{
    $exit = true;
    if ($errorCode === 0) {
        $exit = false;
    }
    $result = array(
        'data' => $data,
        'err'  => array(
            'code' => $errorCode,
            'msg'  => $errorString,
            //'runtime' => RunTime(),
            'timestamp' => time(),
        ),
    );
    @ob_clean();
    echo json_encode($result);
    if ($exit) {
        exit;
    }
}

/**
 * 获取参数值
 *
 * @param string $name 数组key名
 * @param string $type 默认为REQUEST
 *
 * @return mixed|null
 */
function YrActivityLink_GetVars($name, $type = 'REQUEST', $default = null)
{
    if (empty($type)) {
        $type = 'REQUEST';
    }
    $array = &$GLOBALS[strtoupper("_$type")];

    if (array_key_exists($name, $array)) {
        return $array[$name];
    } else {
        return $default;
    }
}

/**
 * 获取参数值
 *
 * @param string $name 数组key名
 * @param string $type 默认为REQUEST
 *
 * @return mixed|null
 */
function YrActivityLink_GetVarsAndTrim($name, $type)
{
    if (!empty($name) && !empty($type)) {
        $value = YrActivityLink_GetVars($name, $type);
        if (is_array($value)) {
            return $value;
        } else if (!is_null($value)) {
            return trim($value);
        } else {
            return $value;
        }
    } else {
        return false;
    }
}
/**
 * 判断数组是否存在指定的key加强版
 * @param int|string $key — Value to check.
 * @param array|ArrayObject $array — An array with keys to check.
 * @return bool — true on success or false on failure.
 */
function YrActivityLink_array_key_exists($key, $array)
{
    return array_key_exists($key, empty($array) ? array() : $array);
}

/**
 * 递归转义 HTML 实体.
 *
 * @param array $arr
 */
function YrActivityLink_RecHtmlSpecialChars(&$arr)
{
    if (is_array($arr)) {
        foreach ($arr as &$value) {
            if (is_array($value)) {
                YrActivityLink_RecHtmlSpecialChars($value);
            } elseif (is_string($value)) {
                $value = htmlspecialchars($value);
            }
        }
    }
}

/**
 * 中文与特殊字符友好的 JSON 编码.
 *
 * @param array $arr
 *
 * @return string
 */
function YrActivityLink_JsonEncode($arr)
{
    YrActivityLink_RecHtmlSpecialChars($arr);

    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        return str_ireplace(
            '\\/',
            '/',
            preg_replace_callback(
                '#\\\u([0-9a-f]{4})#i',
                'Ucs2Utf8',
                json_encode($arr)
            )
        );
    } else {
        return call_user_func('json_encode', $arr, (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

/**
 * 指定的key转换到指定的类型
 * @param array 转换类型的数据
 * @param array 转换类型的key
 * @param string 转换的类型
 */
function YrActivityLink_appointKeysToAppointType($arr, $needToTypeKeysArr, $type)
{
    $newData = $arr;
    if (!is_array($arr)) {
        return false;
    }
    foreach ($newData as $key => $value) {
        if (is_array($value)) {
            foreach ($needToTypeKeysArr as $toIntKeyIndex => $toIntValue) {
                //'c.d.c' => array('c','d','c')
                $explodeResult = explode('.', $toIntValue);
                if ($explodeResult[0] === $key) {
                    array_splice($explodeResult, $toIntKeyIndex, 1); //去除匹配到的key
                    // array('d','c') => 'd.c'
                    $needToTypeKeysArr[$toIntKeyIndex] = implode('.', $explodeResult); //替换成新的key
                    $newData[$key] = YrActivityLink_appointKeysToAppointType($value, $needToTypeKeysArr, $type);
                    break;
                } else {
                    continue;
                }
            }
        } else if (in_array($key, $needToTypeKeysArr)) {
            $needDeleteKeyIndex = array_search($key, array_values($needToTypeKeysArr));
            array_splice($needToTypeKeysArr, $needDeleteKeyIndex, 1);
            //这个设置类型的函数会有返回值，且第一个参数是引用类型的
            settype($value, $type);
            $newData[$key] = $value;
        }
    }
    return $newData;
}

/**
 * 通过Key从数组获取数据.
 *
 * @param array  $array 数组名
 * @param string $name  下标key
 *
 * @return mixed
 */
function YrActivityLink_GetValueInArray($array, $name, $default = null)
{
    if (is_array($array)) {
        if (array_key_exists($name, $array)) {
            return $array[$name];
        }
        return $default;
    }
    return $default;
}

/**
 * 获取设备类型
 * @param string $userAgent 用户代理
 * @return string 返回对应的设备类型 pc（默认） ios android
 */
function YrActivityLink_GetDeviceType($userAgent)
{
    $agent = strtolower($userAgent);
    $type = 'pc';
    if (strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
        $type = 'ios';
    }
    if (strpos($agent, 'android')) {
        $type = 'android';
    }
    return $type;
}

/**
 * html链接压缩成一行，特别需要注意的是如果script里面的代码不是很规范的话，
 * 很有可能会导致代码无法运行，比如每句语句的末尾的分号忘加了
 * @param string $htmlStr html字符串
 */
function YrActivityLink_HTMLCompressionStr($htmlStr)
{
    $chunks = preg_split('/(<!--<nocompress>-->.*?<!--<\/nocompress>-->|<nocompress>.*?<\/nocompress>|<pre.*?\/pre>|<textarea.*?\/textarea>|<script.*?\/script>)/msi', $htmlStr, -1, PREG_SPLIT_DELIM_CAPTURE);
    $compress = '';
    foreach ($chunks as $c) {
        if (strtolower(substr($c, 0, 19)) == '<!--<nocompress>-->') {
            $c = substr($c, 19, strlen($c) - 19 - 20);
            $compress .= $c;
            continue;
        } else if (strtolower(substr($c, 0, 12)) == '<nocompress>') {
            $c = substr($c, 12, strlen($c) - 12 - 13);
            $compress .= $c;
            continue;
        } else if (strtolower(substr($c, 0, 4)) == '<pre' || strtolower(substr($c, 0, 9)) == '<textarea') {
            $compress .= $c;
            continue;
        } else if (strtolower(substr($c, 0, 7)) == '<script' && strpos($c, '//') != false && (strpos($c, "\r") !== false || strpos($c, "\n") !== false)) { // JS代码，包含“//”注释的，单行代码不处理
            $tmps = preg_split('/(\r|\n)/ms', $c, -1, PREG_SPLIT_NO_EMPTY);
            $c = '';
            foreach ($tmps as $tmp) {
                if (strpos($tmp, '//') !== false) { // 对含有“//”的行做处理
                    if (substr(trim($tmp), 0, 2) == '//') { // 开头是“//”的就是注释
                        //这行已经把JS 的注释给干掉了，因为上面的逻辑是先把它复制为空，之后再赋值回来
                        continue;
                    }
                    $chars = preg_split('//', $tmp, -1, PREG_SPLIT_NO_EMPTY);
                    $is_quot = $is_apos = false;
                    foreach ($chars as $key => $char) {
                        if ($char == '"' && $chars[$key - 1] != '\\' && !$is_apos) {
                            $is_quot = !$is_quot;
                        } else if ($char == '\'' && $chars[$key - 1] != '\\' && !$is_quot) {
                            $is_apos = !$is_apos;
                        } else if ($char == '/' && $chars[$key + 1] == '/' && !$is_quot && !$is_apos) {
                            $tmp = substr($tmp, 0, $key); // 不是字符串内的就是注释
                            break;
                        }
                    }
                }
                $c .= $tmp;
            }
        }
        $c = preg_replace('/[\\n\\r\\t]+/', ' ', $c); // 清除换行符，清除制表符
        $c = preg_replace('/\\s{2,}/', ' ', $c); // 清除额外的空格
        $c = preg_replace('/>\\s</', '> <', $c); // 清除标签间的空格
        $c = preg_replace('/\\/\\*.*?\\*\\//i', '', $c); // 清除 CSS & JS 的注释
        $c = preg_replace('/<!--[^!]*-->/', '', $c); // 清除 HTML 的注释
        $compress .= $c;
    }

    return trim($compress);
}

/**
 * 发布检测链接失效的评论
 * @param string $LogID 文章id
 * @param string $Content 评论内容
 */
function YrActivityLink_PostCheckLinkComment($LogID, $Content)
{
    $comment_data = array(
        'comment_author' => '检测链接小助手',
        'comment_content' => $Content,
        'comment_parent' => 0,
        'comment_post_ID' => $LogID,
        'comment_approved' => 1
    );
    wp_insert_comment($comment_data);
}


/**
 * 对数组的value进行html实体解码
 */
function YrActivityLink_array_value_htmlspecialchars_decode($arr)
{
    return array_map(function ($value) {
        if (is_null($value) || empty($value)) {
            return $value;
        }
        if (is_array($value)) {
            return YrActivityLink_array_value_htmlspecialchars_decode($value);
        } else {
            while (is_string($value) && $value !== htmlspecialchars_decode($value)) {
                $value = htmlspecialchars_decode($value);
            }
            return $value;
        }
    }, $arr);
}

/**
 * 判断是否搜索引擎蜘蛛蜘蛛
 */
function YrActivityLink_is_spider()
{
    $agent = strtolower(@$_SERVER['HTTP_USER_AGENT']);
    if (!empty($agent)) {
        $spiderSite = array(
            "TencentTraveler",
            "Baiduspider+",
            "BaiduGame",
            "Googlebot",
            "msnbot",
            "Sosospider+",
            "Sogou web spider",
            "ia_archiver",
            "Yahoo! Slurp",
            "YoudaoBot",
            "Yahoo Slurp",
            "MSNBot",
            "Java (Often spam bot)",
            "BaiDuSpider",
            "Voila",
            "Yandex bot",
            "BSpider",
            "twiceler",
            "Sogou Spider",
            "Speedy Spider",
            "Google AdSense",
            "Heritrix",
            "Python-urllib",
            "Alexa (IA Archiver)",
            "Ask",
            "Exabot",
            "Custo",
            "OutfoxBot/YodaoBot",
            "yacy",
            "SurveyBot",
            "legs",
            "lwp-trivial",
            "Nutch",
            "StackRambler",
            "The web archive (IA Archiver)",
            "Perl tool",
            "MJ12bot",
            "Netcraft",
            "MSIECrawler",
            "WGet tools",
            "larbin",
            "Fish search",
        );
        foreach ($spiderSite as $val) {
            $str = strtolower($val);
            if (strpos($agent, $str) !== false) {
                return true;
            }
        }
        return false;
    } else {
        return false;
    }
}

/**
 * 判断网站来路是否来自搜索引擎
 */
function YrActivityLink_referer_is_spider($referrer)
{
    $httpReferer = $referrer ? $referrer : @$_SERVER["HTTP_REFERER"]; //获取完整的来路URL
    $parseUrlResult = @parse_url($httpReferer);
    $host = isset($parseUrlResult['host']) ? $parseUrlResult['host'] : false;
    if (!$host) {
        return false;
    }
    $spiderHostList = array(
        'm.baidu.com',
        'www.baidu.com',
        'sm.cn',
        'sogou.com',
        'google.cn',
        'toutiao.com'
    );
    return in_array($host, $spiderHostList);
}

/**
 * 生成url-schema的base64-script标签带有referrer来源标识的
 */
function YrActivityLink_generate_have_referer_url_schema_script_tag($src)
{
    $jsContentBase64 = '
  (() => {
    const oldScriptEle = document.currentScript;
    const newScriptEle = document.createElement("script");
    newScriptEle.src = `' . $src . '&referrer=${new URL(document.referrer || window.location.href).origin}`;
    newScriptEle.async = true;
    oldScriptEle.parentNode.replaceChild(newScriptEle, oldScriptEle);
  })();
  ';
    return '<script src="data:text/html;base64,' . base64_encode($jsContentBase64) . '"></script>';
}
