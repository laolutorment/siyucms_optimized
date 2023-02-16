<?php
/**
 * +----------------------------------------------------------------------
 * | 上传文件管理控制器
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
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;
use think\facade\Log;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class FileManagement extends Base
{
    // 验证器
    protected $validate = 'FileManagement';

    // 当前主表
    protected $tableName = 'file_management';

    // 当前主模型
    protected $modelName = 'FileManagement';
	// 列表
    public function index()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 获取列表数据
        $columns = MakeBuilder::getListColumns($this->tableName);
        // 获取搜索数据
        $search = MakeBuilder::getListSearch($this->tableName);
        // 获取当前模块信息
        $model  = '\app\common\model\\' . $this->modelName;
        $module = \app\common\model\Module::where('table_name', $this->tableName)->find();
        // 搜索
        if (Request::param('getList') == 1) {
            $where         = MakeBuilder::getListWhere($this->tableName);
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc         = Request::param('isAsc') ?? 'desc';
			
			
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        // 检测单页模式
        $isSingle = MakeBuilder::checkSingle($this->modelName);
        if ($isSingle) {
            return $this->jump($isSingle);
        }
        // 获取新增地址
        $addUlr = MakeBuilder::getAddUrl($this->tableName);
        // 构建页面
        return TableBuilder::getInstance()
            ->setUniqueId($pk)                              // 设置主键
            ->addColumns($columns)                          // 添加列表字段数据
            ->setSearch($search)                            // 添加头部搜索
            ->addColumn('right_button', '操作', 'btn')      // 启用右侧操作列
            ->addRightButtons($module->right_button)        // 设置右侧操作列
            ->addTopButtons($module->top_button)  // 设置顶部按钮组
			->setTableTem($module->table_tem_set)           //ZTX-设置单独模板
			->addTopButton('default', [
							'title'   => '扫描添加本地文件',
							'icon'    => 'fas fa-exchange-alt',
							'class'   => 'btn btn-info treeStatus',
							'href'    => '',
							'onclick' => "$.modal.open('本地添加扫描添加', 'file_dir')"
							
						]) // 自定义按钮			
            ->setAddUrl($addUlr)                            // 设置新增地址
            ->fetch();
    }
	//ZTX-005文件管理器数据更新
	//扫描上传目录下所有文件，将文件数据插入数据库
	
	 public static function file_dir()
	 {
		
			function getDir($path){
				static $new_files=0;
				static $old_files=0;
		 $model  = '\app\common\model\FileManagement';
		  if(is_dir($path)){
		 
			$dir = scandir($path);
			foreach ($dir as $value){
			  $sub_path =$path .'/'.$value;
			  if($value == '.' || $value == '..'){
				continue;
			  }else if(is_dir($sub_path)){
				
				getDir($sub_path);
			  }else{
				
				$path_url=substr($path, 1). '/'.$value;
				$old_file = $model::where('link', $path_url)->find();
				if(empty($old_file)){
					//若数据库不存在，则插入新的数据
					$system       = \app\common\model\System::find(1);
					$file_type=substr(strrchr($value, '.'), 1);
					
					
					if(stristr($system['upload_image_ext'],$file_type )){
					$file_type='img/'.$file_type;	
					}
					else{
						$file_type='file/'.$file_type;	
					}
					
					
					$model::create([
							'name'  =>  $value,
							'link' =>  $path_url,
							'module' => '',
							'describe' =>'',
							'file_type' => $file_type,
							'file_size' =>filesize(root_path() . 'public'.$path_url)/1024
						]);
						 $new_files=$new_files+1;
						 echo $new_files.'.  '.$path_url.'已添加<br>';
						 
						
										
				//$new_files=$new_files+1;
				}else
				{
					//若数据已存在，则不做插入操作，仅统计已存在文件
					$old_files = $old_files+1;
					echo $old_files.'.  '.$path_url.'已有，未添加<br>';
				}
		
		
       
      }
    }
  }
}
		$path = './uploads';
		getDir($path); 
			//echo "<script>$.modal.alertSuccess('新增记录".$new_files."条，".$old_files."条记录已存在')</script>";
			//print_r($rec);
	 }

//ZTX-005文件管理器数据更新
}
