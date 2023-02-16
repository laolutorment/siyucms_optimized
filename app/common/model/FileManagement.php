<?php
/**
 * +----------------------------------------------------------------------
 * | 上传文件管理模型
 * +----------------------------------------------------------------------
 *                      .::::.
 *                    .::::::::.            | AUTHOR: siyu
 *                    :::::::::::           | EMAIL: 407593529@qq.com
 *                 ..:::::::::::'           | DATETIME: 2022/04/20
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
namespace app\common\model;

class FileManagement extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 删除ZTX-005折腾侠
    public static function del($id)
    {
        try {
			$path=self::where('id',$id)->value('link');
			$con='app\admin\controller\Index';
			self::file_del($path);
            self::destroy($id);
            return json(['error' => 0, 'msg' => '删除成功!']);
        } catch (\Exception $e) {
            return json(['error' => 1, 'msg' => $e->getMessage()]);
        }
    } 
    // 批量删除ZTX-005折腾侠
    public static function selectDel($id)
    {
        if ($id) {
            $ids = explode(',', $id);
			foreach ($ids as $k => $v) {
				$path=self::where('id',$v)->value('link');
				self::file_del($path);
			}
			
            self::destroy($ids);
            return json(['error' => 0, 'msg' => '删除成功!']);
        } else {
            return ['error' => 1, 'msg' => '删除失败'];
        }
    }
    
	
	
	/**
     * ZTX-005折腾侠：增加从服务器上删除对应文件的操作
	 * 通用ajax删除服务器文件
     * @param string    $file      文件路径
     */
    public static function file_del(string $file='')
    {
		$file='.'.$file;
		if (file_exists($file)) {
			  //删除文件
		if (unlink($file)) {
		  $code='1';//删除成功
										} else {
		  $code='2';//删除失败
				}
			} 
			else {
			  $code='0';//文件不存在
			}
		return $code;
	}

}