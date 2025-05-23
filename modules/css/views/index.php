<?php
/**
 * @filesource modules/css/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Css\Index;

use Kotchasan\Http\Request;

/**
 * Generate CSS file
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\KBase
{
    /**
     * สร้างไฟล์ CSS
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // session
        $request->initSession();
        // โหลด css หลัก
        $data = file_get_contents(ROOT_PATH.'skin/fonts.css');
        $data = preg_replace('/url\(([\'"])?fonts\//isu', 'url(\\1'.WEB_URL.'skin/fonts/', $data);
        $data .= file_get_contents(ROOT_PATH.'skin/gcss.css');
        // css ของ template
        $data2 = file_get_contents(ROOT_PATH.self::$cfg->skin.'/style.css');
        $data2 = preg_replace('/url\(([\'"])?(img|fonts)\//isu', 'url(\\1'.WEB_URL.self::$cfg->skin.'/\\2/', $data2);
        // โหลดโมดูลที่ติดตั้งแล้ว
        $modules = \Gcms\Modules::create();
        // ไดเร็คทอรี่โมดูล
        $dir = $modules->getDir();
        // css ของโมดูล
        foreach ($modules->get() as $module) {
            if (is_file($dir.$module.'/style.css')) {
                $data2 .= preg_replace('/url\(img\//isu', 'url('.WEB_URL.'modules/'.$module.'/img/', file_get_contents($dir.$module.'/style.css'));
            }
        }
        $dark = $request->cookie('dark')->toBoolean();
        $root = [
            '--menu-highlight-bg:'.self::$cfg->header_bg_color,
            '--header-background-color:'.self::$cfg->header_bg_color,
            '--header-color:'.self::$cfg->header_color
        ];
        if (!$dark) {
            $root[] = '--content-bg:'.self::$cfg->content_bg;
            $root[] = '--wrapper-background-color:'.self::$cfg->warpper_bg_color;
            if (is_file(ROOT_PATH.DATA_FOLDER.'images/bg_image.png')) {
                $bg_image = WEB_URL.DATA_FOLDER.'images/bg_image.png';
            } else {
                $bg_image = '';
            }
            $root[] = '--background-image:url('.$bg_image.')';
        }
        $data2 .= ':root{'.implode(';', $root).'}';
        $data2 .= '.border-color{border-color:'.self::$cfg->header_bg_color.'}';
        foreach (self::$cfg->color_status as $key => $value) {
            $data2 .= '.status'.$key.'{color:'.$value.'}';
        }
        if ($dark) {
            $data2 .= file_get_contents(ROOT_PATH.'skin/dark.css');
        } else {
            if (!empty(self::$cfg->logo_color)) {
                $data2 .= '.logo_color{color:'.self::$cfg->logo_color.'}';
            }
            if (!empty(self::$cfg->footer_color)) {
                $data2 .= '.footer{color:'.self::$cfg->footer_color.'}';
            }
            if (!empty(self::$cfg->login_color)) {
                $data2 .= '#login_div form{color:'.self::$cfg->login_color.'}';
            }
            if (!empty(self::$cfg->login_header_color)) {
                $data2 .= '#login_div .header_color{color:'.self::$cfg->login_header_color.'}';
            }
            if (!empty(self::$cfg->login_footer_color)) {
                $data2 .= '.welcomepage .footer{color:'.self::$cfg->login_footer_color.'}';
            }
            if (!empty(self::$cfg->login_bg_color)) {
                $data2 .= '.welcomepage .bg_color{background-color:'.self::$cfg->login_bg_color.'}';
            }
        }
        // compress css
        $compressCss = self::compress($data.$data2);

        // Generate ETag
        $etag = md5($compressCss);

        // Response
        $response = new \Kotchasan\Http\Response();
        $headers = [
            'Content-type' => 'text/css; charset=utf-8',
            'Cache-Control' => 'public, max-age=604800',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 604800).' GMT',
            'Pragma' => 'cache',
            'Vary' => 'Accept-Encoding',
            'Last-Modified' => gmdate('D, d M Y H:i:s').' GMT',
            'ETag' => '"'.$etag.'"'
        ];

        // Check if-none-match header
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === '"'.$etag.'"') {
            $response->withHeaders($headers)
                ->withStatus(304)
                ->send();
            exit;
        }

        // Check if-modified-since header
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']));
            if ($if_modified_since >= time() - 604800) {
                $response->withHeaders($headers)
                    ->withStatus(304)
                    ->send();
                exit;
            }
        }

        $headers['Last-Modified'] = gmdate('D, d M Y H:i:s').' GMT';
        $response->withHeaders($headers)
            ->withContent($compressCss)
            ->send();
    }

    /**
     * @param string $contents
     */
    public static function compress($contents)
    {
        return preg_replace(['!/\*[^*]*\*+([^/][^*]*\*+)*/!', '/[\s]{0,}([:;,>\{\}])[\s]{0,}/', '/[\r\n\t]/s', '/[\s]{2,}/s', '/;}/'], ['', '\\1', '', ' ', '}'], $contents);
    }
}
