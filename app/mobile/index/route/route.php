<?php
/**
 * +----------------------------------------------------------------------
 * | 前台路由
 * +----------------------------------------------------------------------
 *                      .::::.
 *                    .::::::::.            | AUTHOR: siyu
 *                    :::::::::::           | DATETIME: 2020/03/17
 *                 ..:::::::::::'
 *             '::::::::::::'
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

use think\facade\Route;

$cate = \app\common\model\Cate::field('id,cate_name,cate_folder,module_id')->order('sort ASC,id ASC')->select();
foreach ($cate as $k => $v) {
    // 当栏目设置了[栏目目录]字段时注册路由
    if ($v['cate_folder']) {
        if ($v->module->getData('model_name') == 'Page') {
            Route::any('' . $v['cate_folder'] . '', '' . $v['cate_folder'] . '/index');
        } else {
            // 列表+详情模型
            Route::any('' . $v['cate_folder'] . '/<id>', $v['cate_folder'] . '/info')->pattern(['id' => '\d+']);;
            Route::any('' . $v['cate_folder'] . '', $v['cate_folder'] . '/index');
        }
    }
}

// tag路由
Route::any('tag_<module>/<t>', 'Index/tag');
