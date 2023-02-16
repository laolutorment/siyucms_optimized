<?php

/**
 * +----------------------------------------------------------------------
 * | 首页控制器
 * +----------------------------------------------------------------------
 *                      .::::.
 *                    .::::::::.            | AUTHOR: siyu
 *                    :::::::::::           | EMAIL: 407593529@qq.com
 *                 ..:::::::::::'           | QQ: 407593529
 *             '::::::::::::'               | DATETIME: 2019/04/03
 *                .::::::::::
 *           '::::::::::::::..
 *                ..::::::::::::.
 *              ``::::::::::::::::
 *               ::::``:::::::::'        .:::.
 *              ::::'   ':::::'       .::::::::.
 *            .::::'      ::::     .:::::::'::::.
 *           .:::'       :::::  .:::::::::' ':::::.
 *          .::'        :::::.:::::::::'      ':::::.
 *         .::'         ::::::::::::::'         ``::::.
 *     ...:::           ::::::::::::'              ``::.
 *   ```` ':.          ':::::::::'                  ::::..
 *                      '.:::::'                    ':'````..
 * +----------------------------------------------------------------------
 */

namespace app\admin\controller;

use think\facade\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Log;

class Index extends Base
{
    // 首页
    public function index()
    {
        // 系统信息
        $mysqlVersion = Db::query('SELECT VERSION() AS ver');
        $config       = [
            'url'             => $_SERVER['HTTP_HOST'],
            'document_root'   => $_SERVER['DOCUMENT_ROOT'],
            'server_os'       => PHP_OS,
            'server_port'     => $_SERVER['SERVER_PORT'],
            'server_ip'       => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
            'server_soft'     => $_SERVER['SERVER_SOFTWARE'],
            'php_version'     => PHP_VERSION,
            'mysql_version'   => $mysqlVersion[0]['ver'],
            'max_upload_size' => ini_get('upload_max_filesize'),
            'version'         => App::version(),
            'siyu_version'    => Config::get('app.siyu_version'),
        ];

        // 查找一周内注册用户信息
        $user = \app\common\model\Users::where('create_time', '>', time() - 60 * 60 * 24 * 7)->count();

        // 查找待处理留言信息
        if (class_exists('\app\common\model\Message')) {
            $message = \app\common\model\Message::where('status', '0')->count();
        }

        // 查找是否有在线留言的模型id
        $messageModuleId = \app\common\model\Module::where('table_name', 'message')->value('id');
        $messageCatUrl   = url('Message/index');
        if ($messageModuleId) {
            // 查询留言模块第一个栏目ID
            $messageCatId = \app\common\model\Cate::where('module_id', $messageModuleId)->value('id');
            if (!is_null($messageCatId)) {
                // 生成URL
                $messageCatUrl = url('Message/index', ['cate_id' => $messageCatId]);
            }
        }

        $view = [
            'config'        => $config,
            'user'          => $user,
            'message'       => $message ?? 0,
            'messageCatUrl' => $messageCatUrl,
            'indexTips'     => $this->getIndexTips(),
        ];
        View::assign($view);
        return View::fetch();
    }

    // 清除缓存
    public function clear()
    {
        $path = App::getRootPath() . 'runtime';
        if ($this->_deleteDir($path)) {
            $result['msg']   = '清除缓存成功!';
            $result['error'] = 0;
        } else {
            $result['msg']   = '清除缓存失败!';
            $result['error'] = 1;
        }
        $result['url'] = (string)url('login/index');
        return json($result);
    }

    /**
     * 预览
     * @param string $module 模型名称
     * @param string $id     文章id
     * @return \think\response\Redirect
     */
    public function preview(string $module, string $id)
    {
        // 查询当前模块信息
        $model = '\app\common\model\\' . $module;
        $info  = $model::find($id);
        if ($info) {
            // 查询所在栏目信息
            $cate = \app\common\model\Cate::find($info['cate_id']);
            if ($cate->module->getData('model_name') == 'Page') {
                if ($cate['cate_folder']) {
                    $url = $cate['cate_folder'] . '.' . Config::get('route.url_html_suffix');
                } else {
                    $url = $module . Config::get('route.pathinfo_depr') . 'index.' . Config::get('route.url_html_suffix') . '?cate=' . $cate['id'];
                }
            } else {
                if ($cate['cate_folder']) {
                    $url = $cate['cate_folder'] . Config::get('route.pathinfo_depr') . $id . '.' . Config::get('route.url_html_suffix');
                } else {
                    $url = $module . Config::get('route.pathinfo_depr') . 'info.' . Config::get('route.url_html_suffix') . '?cate=' . $cate['id'] . '&id=' . $id;
                }
            }
            if (isset($url) && !empty($url)) {
                // 检测是否开启了域名绑定
                $domainBind = Config::get('app.domain_bind');
                if ($domainBind) {
                    $domainBindKey = array_search('index', $domainBind);
                    $domainBindKey = $domainBindKey == '*' ? 'www.' : ($domainBindKey ? $domainBindKey . '.' : '');
                    $url           = Request::scheme() . '://' . $domainBindKey . Request::rootDomain() . '/' . $url;
                } else {
                    $url = '/index/' . $url;
                }
            }
        }
        return redirect($url);
    }

    /**
     * select 2 ajax分页获取数据
     * @param int    $id      字段id
     * @param string $keyWord 搜索词
     * @param string $rows    显示数量
     * @param string $value   默认值
     * @return array
     */
    public function select2(int $id, string $keyWord = '', string $rows = '10', string $value = '')
    {
        // 字段信息
        $field = \app\common\model\Field::find($id);
        if (is_null($field) || empty($field['relation_model']) || empty($field['relation_field'])) {
            return [];
        }
        $model = '\app\common\model\\' . $field['relation_model'];
        // 获取主键
        $pk = \app\common\model\Module::where('model_name', $field['relation_model'])->value('pk') ?? 'id';
        // 默认值
        if ($value) {
            $valueText = $model::where($pk, $value)->value($field['relation_field']);
            if ($valueText) {
                return [
                    'key'   => $value,
                    'value' => $valueText
                ];
            }
        }

        // 搜索条件
        $where = [];
        if ($keyWord) {
            $where[] = [$field['relation_field'], 'LIKE', '%' . $keyWord . '%'];
        }

        $list = $model::field($pk . ',' . $field['relation_field'])
            ->where($where)
            ->order($pk . ' desc')
            ->paginate([
                'query'     => Request::get(),
                'list_rows' => $rows,
            ]);
        foreach ($list as $k => $v) {
            $v['text'] = $v[$field['relation_field']];
        }
        return $list;
    }

    /**
     * ajax获取多级联动数据
     * @param string $model        模型名称
     * @param string $key          关联模型的主键
     * @param string $keyValue     要展示的字段
     * @param int    $pid          父ID
     * @param string $pidFieldName 关联模型的父级id字段名
     * @return array
     */
    public function linkage(string $model, string $key, string $keyValue, int $pid = 0, string $pidFieldName = 'pid')
    {
        $list   = getLinkageData($model, $pid, $pidFieldName);
        $result = [];
        foreach ($list as $v) {
            $result[] = [
                'key'   => $v[$key],
                'value' => $v[$keyValue],
            ];
        }
        return [
            'code' => 1,
            'list' => $result
        ];
    }

    // 执行删除
    private function _deleteDir($R)
    {
        Cache::clear();
        $handle = opendir($R);
        while (($item = readdir($handle)) !== false) {
            // log目录不可以删除
            if ($item != '.' && $item != '..' && $item != 'log') {
                if (is_dir($R . DIRECTORY_SEPARATOR . $item)) {
                    $this->_deleteDir($R . DIRECTORY_SEPARATOR . $item);
                } else {
                    if ($item != '.gitignore') {
                        if (!unlink($R . DIRECTORY_SEPARATOR . $item)) {
                            return false;
                        }
                    }
                }
            }
        }
        closedir($handle);
        return true;
        //return rmdir($R); // 删除空的目录
    }

    // 检查提示信息
    private function getIndexTips()
    {
        $password = \app\common\model\Admin::where('id', session('admin.id'))->value('password');
        if ($password == md5('admin')) {
            return '<h6 class="mb-0"><i class="icon fas fa-fw fa-exclamation-triangle"></i> 请尽快修改后台初始密码！</h6>';
        }
        return '';
    }




    /**
     * ZTX-002折腾侠：增加从服务器上删除对应文件的操作
     * 通用ajax删除服务器文件
     * @param string    $file      文件路径
     */
    public static function file_del(string $file = '', string $file_type = '')
    {
        $file_all = '.' . $file;
        if (file_exists($file_all)) {
            if ($file_type == 'image') {
                $file_info = pathinfo($file_all);
                $small_file = $file_info['dirname'] . '/' . 'small_' . $file_info['basename'];
                $small_file_url = mb_substr($small_file, 1);
                if (file_exists($small_file)) {
                    if (unlink($small_file)) {
                        $model = 'app\common\model\FileManagement'; //ZTX-005
                        $model::where('link', '=', $small_file_url)->delete(); //ZTX-005删除文件管理模块数据
                    }
                }
            }


            //删除文件
            if (unlink($file_all)) {
                $code = '1'; //删除成功
                $model = 'app\common\model\FileManagement'; //ZTX-005
                $model::where('link', '=', $file)->delete(); //ZTX-005删除文件管理模块数据
            } else {
                $code = '2'; //删除失败
            }
        } else {
            $code = '0'; //文件不存在
        }
        return $code;
    }

    /**
     * ZTX-005折腾侠：增加从站内文件选择
     * 通用ajax删除服务器文件
     * @param string    $where      查询条件
     */
    public static function autocomplete_file(array $where = [])
    {
        $model = 'app\common\model\FileManagement'; //ZTX-005
        
        $data = $model::where([$where])->field('name,link')->select();
        
        return $data;
    }

    /**
     * ZTX-005折腾侠：增加从站内文件选择
     * 通用ajax删除服务器文件
     * @param string    $key      查询关键字
     */
    public static function autocomplete_icon(string $key ='')
    {
        $model = 'app\common\model\AwesomeIcons'; //ZTX-005
        
        $data = $model::where('name','like','%'.$key.'%')->whereor('code','like','%'.$key.'%')->field('name,code')->select();
        
        return $data;
    }
}
