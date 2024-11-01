<?php

/**
 * Plugin Name: 活动链接推广插件
 * Description: 助力活动推广
 * Author: 野人
 * Author URI: https://www.yerenwz.com/
 * Version: 1.2.0
 * Text Domain: yr-activity-link
 */
if (!defined('ABSPATH')) {
    die('Invalid request.');
}
require_once('common.php');
require_once('render-shortcode-func.php');
if (!class_exists('YrActivityLink')) {
    class YrActivityLink
    {
        public $opts;
        public static $plugin_file;
        protected static $instance = null;
        private function __construct()
        {
            self::init_actions();
        }
        public static function init_actions()
        {
            self::$plugin_file = __FILE__;
            register_activation_hook(__FILE__, array(__CLASS__, 'activate'));
            register_deactivation_hook(__FILE__, array(__CLASS__, 'deactivate'));
            register_uninstall_hook(__FILE__, array(__CLASS__, 'uninstall'));
            // Show the plugin's admin settings, and a link to them in the plugins list table.
            add_filter('plugin_action_links', array(__CLASS__, 'add_settings_link'), 10, 2);
            // add_filter('plugin_row_meta', array($this, 'register_plugin_links'), 10, 2);
            add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        }
        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        // 插件被启用
        public static function activate()
        {
            add_option('YrActivityLink', array('InstallTime' => time(), 'linkCheck' => array()));
            self::create_table();
        }
        // 插件被停用
        public static function deactivate()
        {
        }
        // 插件被删除
        public static function uninstall()
        {
            delete_option('YrActivityLink');
        }
        public static function add_settings_link($links, $file)
        {
            if (plugin_basename(self::$plugin_file) === $file) {
                $settings_link = '<a href="options-general.php?page=yractivitylink_settings">设置</a>';
                array_push($links, $settings_link);
            }
            return $links;
        }
        protected static function create_table()
        {
            global $wpdb;
            /*
            * We'll set the default character set and collation for this table.
            * If we don't do this, some characters could end up being converted 
            * to just ?'s when saved in our table.
            */
            $charset_collate = 'utf8mb4';
            $table_name = $wpdb->prefix . 'plugin_yr_activity_link';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                if (!empty($wpdb->charset)) {
                    $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
                }

                if (!empty($wpdb->collate)) {
                    $charset_collate .= " COLLATE {$wpdb->collate}";
                }

                $sql = "CREATE TABLE " . $table_name . " (
                    `id` int(11) NOT NULL primary key AUTO_INCREMENT,
                    `content` longtext NOT NULL COMMENT '配置项',
                    `createTime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
                    `updateTime` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间'
                ) $charset_collate;";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
            }
        }
        public static function add_admin_menu()
        {
            add_options_page("活动链接推广插件", "活动链接推广插件", 'manage_options', 'yractivitylink_settings', array(__CLASS__, 'yractivitylink_settings'));
        }
        public static function yractivitylink_settings()
        {
            require_once('common.php');
            $nonce = wp_create_nonce('yractivitylink');
            $front_page = plugins_url('/build/index.html', self::$plugin_file);
            $back_api = admin_url('admin-ajax.php') . '?action=YrActivityLink_Api&csrfToken=' . $nonce;
?>
            <style>
                #yractivitylink_iframe {
                    display: block;
                    margin-top: 10px;
                }
            </style>
            <iframe id="yractivitylink_iframe" src="<?php echo esc_url($front_page); ?>" frameborder="0" width="100%"></iframe>
            <script>
                (($) => {
                    //防抖
                    function debounce(fn, wait = 500) {
                        let timer = null;
                        return function(...args) {
                            if (timer) clearTimeout(timer);
                            timer = setTimeout(() => {
                                fn.apply(this, args);
                            }, wait);
                        };
                    };

                    function insertIframeConfig() {
                        $("#yractivitylink_iframe").get(0).contentWindow.Api = "<?php echo $back_api; ?>";
                        $('#yractivitylink_iframe').get(0).contentWindow.yrConfig = {
                            cmsPlatform: 'wordpress',
                            from: "YrActivityLink"
                        };
                        $("#yractivitylink_iframe").get(0).addEventListener('load', (e) => {
                            resizeIframeHeight();
                            e.target.contentDocument.body.style = 'padding:0 20px';
                        })
                    }

                    function resizeIframeHeight() {
                        const totalHeight = $('#wpwrap').height();
                        const updateNag = $('#wpbody-content .update-nag').outerHeight(true) || 0;
                        const footerHeight = 40;
                        const height = totalHeight - updateNag - 10 - footerHeight;
                        $('#yractivitylink_iframe').attr('height', height);
                    }

                    insertIframeConfig();
                    // $(window).resize(debounce(resizeIframeHeight, 500));
                })(jQuery)
            </script>
<?php
        }
    }
    if (is_admin()) {
        YrActivityLink::get_instance();
    }
}

function YrActivityLink_Api()
{
    YrActivityLink_Remove_Wp_Extra_Add_Slashes();
    require_once('ajax-func.php');
    die();
}
// 用户登录/未登录都注册ajax
add_action('wp_ajax_YrActivityLink_Api', 'YrActivityLink_Api');
add_action('wp_ajax_nopriv_YrActivityLink_Api', 'YrActivityLink_Api');

add_action('wp_footer', function () {;
    $ajax_url = base64_encode(admin_url('admin-ajax.php') . '?action=YrActivityLink_Api&act=getId');
    wp_enqueue_script('YrActivityLink-links', plugins_url('/script/links.js', __FILE__) . '?' . $ajax_url, array(), null, true);
    wp_add_inline_script('YrActivityLink-links', '
        (() => {
            function yrSwiperDanmuInit() {
            new Swiper(".yr-app-item .yr-swiper-danmu-container", {
                autoplay: {
                    disableOnInteraction: false,
                    delay: 0,
                },
                autoplayDisableOnInteraction: false,
                slidesPerView: 2,
                speed: 4000,
                loop: true,
                observer: true,
                observeParents: true
            });
            }
            if (typeof window.Swiper === "undefined") {
            const scriptEle = document.createElement("script");
            scriptEle.src = "' . plugins_url('/script/swiper.min.js', __FILE__) . '";
            scriptEle.onload = yrSwiperDanmuInit;
            document.body.appendChild(scriptEle);
            } else {
                yrSwiperDanmuInit();
                if (window.MutationObserver) {
                    const observer = new MutationObserver(function (mrs) {
                        mrs.forEach((i) => {
                        const { addedNodes } = i;
                        addedNodes.forEach((j) => {
                            if (
                            j?.classList?.contains("yr-app-list") &&
                            j.querySelector(".yr-swiper-danmu-container")
                            ) {
                            yrSwiperDanmuInit();
                            }
                        });
                        });
                    });
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true,
                    });
                }  
            }
        })(); 
	');
    wp_enqueue_style('YrActivityLink-style', plugins_url('/style/yr-app-list.css', __FILE__));
    // 注意css也有阻塞渲染的可能性 https://zhuanlan.zhihu.com/p/36700206
    wp_enqueue_style('YrActivityLink-customPluginStyles', admin_url('admin-ajax.php') . '?action=YrActivityLink_Api&act=customPluginStyles');
});