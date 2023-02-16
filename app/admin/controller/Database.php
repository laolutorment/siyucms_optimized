<?php

/**
 * +----------------------------------------------------------------------
 * | 数据库备份控制器
 * +----------------------------------------------------------------------
 *                      .::::.
 *                    .::::::::.            | AUTHOR: siyu
 *                    :::::::::::           | EMAIL: 407593529@qq.com
 *                 ..:::::::::::'           | DATETIME: 2020/01/31
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

use think\facade\Request;
use think\facade\Db;
use \tp5er\Backup;
use think\facade\Log;
use think\facade\View;

// 引入表格构建器
use app\common\builder\TableBuilder;
// 引入表单构建器
use app\common\builder\FormBuilder;
// 引入导出的命名空间
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Database extends Base
{
    protected $db = '', $datadir;
    function initialize()
    {
        parent::initialize();
        $this->config = array(
            'path'     => './Data/', // 数据库备份路径
            'part'     => 20971520,  // 数据库备份卷大小
            'compress' => 0,         // 数据库备份文件是否启用压缩 0不压缩 1 压缩
            'level'    => 9          // 数据库备份文件压缩级别 1普通 4 一般  9最高
        );
        $this->db = new Backup($this->config);
    }

    // 数据列表
    public function database()
    {
        // 设置主键
        $pk = 'name';
        // 字段信息
        $columns = [
            ['name', '数据表'],
            ['engine', '存储引擎'],
            //['version', '版本'],
            ['row_format', '行格式'],
            ['rows', '行数', '', '', '', '', 'true'],
            //['avg_row_length', '平均每行包括的字节数'],
            ['data_size', '字节数', '', '', '', '', 'true'],
            ['data_length', '数据量'],
            //['max_data_length', '可容纳的最大数据量'],
            //['index_length', '索引占用磁盘的空间大小'],
            //['data_free', '已经分配，但目前没有使用的空间'],
            //['auto_increment', 'auto_increment'],
            ['comment', '额外信息'],
            ['create_time', '创建时间'],
            ['update_time', '更新时间'],
            //['check_time', '最后一次检查表的时间'],
            //['collation', '表的默认字符集和字符排序规则'],
            //['checksum', '整个表的实时校验和'],
            //['create_options', '创建表时指定的其他选项'],
        ];
        // 数据信息
        $list = $this->db->dataList();
        // 计算总大小
        $total = 0;
        foreach ($list as $k => $v) {
            $total += $v['data_length'];
            $list[$k]['data_size'] = $v['data_length'];
            $list[$k]['data_length'] = format_bytes($v['data_length']);
        }
        // 提示信息
        $pageTips = '数据库中共有 ' . count($list) . ' 张表，共计 ' . format_bytes($total);
        // 可搜索的字段
        $search = [
            ['text', 'name', '数据表', 'LIKE'],
        ];
        // 搜索
        if (Request::param('getList') == 1) {
            // 排序规则
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc = Request::param('isAsc') ?? 'desc';
            $isAsc = $isAsc == 'desc' ? SORT_DESC : SORT_ASC;
            // 排序处理
            $date = array_column($list, $orderByColumn);
            array_multisort($date, $isAsc, $list);
            if (Request::param('name')) {
                foreach ($list as $k => $v) {
                    if (strpos($v['name'], Request::param('name')) == false) {
                        unset($list[$k]);
                    }
                }
            }
            // 渲染输出
            $result = [
                'total'        => count($list),
                'per_page'     => 1000,
                'current_page' => 1,
                'last_page'    => 1,
                'data'         => $list,
            ];
            return $result;
        }
        // 构建页面
        return TableBuilder::getInstance()
            ->setUniqueId($pk)                              // 设置主键
            ->addColumns($columns)                         // 添加列表字段数据
            ->setSearch($search)                            // 添加头部搜索
            ->setPageTips($pageTips, 'warning')             // 提示信息
            ->setPagination('false')                        // 关闭分页显示
            ->addTopButtons([
                'backup' => [
                    'title'       => '备份',
                    'icon'        => 'fa fa-server',
                    'class'       => 'btn btn-success multiple disabled',
                    'href'        => '',
                    'target'      => '',
                    'onclick'     => '$.operate.database(\'' . url('backup') . '\' , \'备份\')',
                ],
                'optimize' => [
                    'title'       => '优化',
                    'icon'        => 'fa fa-medkit',
                    'class'       => 'btn btn-primary multiple disabled',
                    'href'        => '',
                    'target'      => '',
                    'onclick'     => '$.operate.database(\'' . url('optimize') . '\', \'优化\')',
                ],
                'repair' => [
                    'title'       => '修复',
                    'icon'        => 'fa fa-user-md',
                    'class'       => 'btn btn-warning multiple disabled',
                    'href'        => '',
                    'target'      => '',
                    'onclick'     => '$.operate.database(\'' . url('repair') . '\', \'修复\')',
                ],
                // ZTX-011，增加数据库导入导出功能。
                'import' => [
                    'title'       => '导入',
                    'icon'        => 'fa fa-file-import',
                    'class'       => 'btn btn-success single disabled',
                    'href'        => '',
                    'target'      => '',
                    'onclick'     => '$.operate.database_import()',
                ],
                'export' => [
                    'title'       => '导出',
                    'icon'        => 'fa fa-file-export',
                    'class'       => 'btn btn-warning single disabled',
                    'href'        => '',
                    'target'      => '',
                    'onclick'     => '$.operate.database_export()',
                ],
                // ZTX-011，增加数据库导入导出功能。
            ])
            ->fetch();
    }
    // ZTX-011，增加数据库导入导出功能。
    // 导入
    public function excel_import()
    {
        $tables = Request::param('id');
        $Excel_file = Request::param('Excel_file');
        $rownum = Request::param('rownum') ?? 2;
        $prefix=\think\facade\Env::get('DATABASE.PREFIX');//获取数据表前缀
        $module_table_name=ltrim($tables,$prefix);//移除表前缀
        $module_id=\app\common\model\Module::where('table_name', $module_table_name)->value('id');
        if ($Excel_file) {

            $excel_data = self::readExecl(root_path() . 'public' . $Excel_file, 0, $rownum, 0, []);
        }

        if (!empty($tables)) {

            $columns = Db::query("show COLUMNS FROM " . $tables);
            $columns_checkbox = [];
            $checkbox_value = [];
            foreach ($columns as $k => $v) {
                $checkbox_title = \app\common\model\Field::where('field', $v['Field'])->where('module_id', $module_id)->value('name');
                $checkbox_title = $checkbox_title . '(' . $v['Field'] . ')';
                $checkbox_value[] = $v['Field'];
                $columns_checkbox[$v['Field']] = $checkbox_title;
            }

            $data = [
                "columns_checkbox" => $columns_checkbox,
                'table_name' => $tables,
                'rowCnt' => $excel_data['rowCnt'] ?? 1,
                'excel_data' => $excel_data['data'] ?? [],
                'rownum' => $excel_data['rownum'] ?? 2,
                'Excel_file' => $Excel_file
            ];
          
            View::assign($data);
            return View::fetch();
        }
    }
    //数据导入中间控制器
    public function excel_import_Post()
    {

        if (Request::isPost()) {
            $data   = Request::except(['file'], 'post');
            if (isset($data['backup']) && $data['backup'] == 'yes') {
                $this->db->setFile()->backup($data['table_name'], 0);
            }

            try {
                /* 转码 */
                $file = iconv("utf-8", "gb2312", root_path() . 'public' . $data['Excel_file2']);
                //设置读取的第一个标签
                $sheet = 0;
                //设置最大列号为0，意思是读取全部列
                $columnCnt = 0;

                if (empty($file) or !file_exists($file)) {
                    throw new \Exception('文件不存在!');
                }

                /** @var Xlsx $objRead */
                $objRead = IOFactory::createReader('Xlsx');

                if (!$objRead->canRead($file)) {
                    /** @var Xls $objRead */
                    $objRead = IOFactory::createReader('Xls');

                    if (!$objRead->canRead($file)) {
                        throw new \Exception('只支持导入Excel文件！');
                    }
                }

                /* 如果不需要获取特殊操作，则只读内容，可以大幅度提升读取Excel效率 */
                empty($options) && $objRead->setReadDataOnly(true);
                /* 建立excel对象 */
                $obj = $objRead->load($file);
                /* 获取指定的sheet表 */
                $currSheet = $obj->getSheet($sheet);

                if (isset($options['mergeCells'])) {
                    /* 读取合并行列 */
                    $options['mergeCells'] = $currSheet->getMergeCells();
                }

                if (0 == $columnCnt) {
                    /* 取得最大的列号 */
                    $columnH = $currSheet->getHighestColumn();
                    /* 兼容原逻辑，循环时使用的是小于等于 */
                    $columnCnt = Coordinate::columnIndexFromString($columnH);
                }

                /* 获取总行数 */
                $rowCnt = $currSheet->getHighestRow();


                $start_row = $data['excel_title'] + 1; //设置开始读取的行数为标题行的下一行
                $end_row = $rowCnt;


                /* 读取内容 */
                for ($_row = $start_row; $_row <= $end_row; $_row++) {
                    $isNull = true;
                    if ($data['import_type'] == 'delete') {
                        $cellName = $data['excel_col'][$data['key']];
                        $cellId   = $cellName . $_row;
                        $cell     =  trim($currSheet->getCell($cellId)->getFormattedValue());
                        $result = Db::table($data['table_name'])->where($data['key'], $cell)->delete();
                        if ($result == 1) {
                            echo '成功删除匹配excle表第' . $_row . '行数据<br>';
                        } else {
                            echo '匹配第' . $_row . '行数据失败，未删除任何数据<br>';
                        }
                    } else {
                        foreach ($data['col'] as $key => $value) {
                            if (isset($data['excel_col'][$value])) {

                                //当EXCEL表格的对应列有值时，才进行以下数据收集
                                $cellName = $data['excel_col'][$value];
                                $cellId   = $cellName . $_row;
                                $cell     = $currSheet->getCell($cellId);

                                if (isset($options['format'])) {
                                    /* 获取格式 */
                                    $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
                                    /* 记录格式 */
                                    $options['format'][$_row][$cellName] = $format;
                                }

                                if (isset($options['formula'])) {
                                    /* 获取公式，公式均为=号开头数据 */
                                    $formula = $currSheet->getCell($cellId)->getValue();

                                    if (0 === strpos($formula, '=')) {
                                        $options['formula'][$cellName . $_row] = $formula;
                                    }
                                }

                                if (isset($format) && 'm/d/yyyy' == $format) {
                                    /* 日期格式翻转处理 */
                                    $cell->getStyle()->getNumberFormat()->setFormatCode('yyyy/mm/dd');
                                }
                                //获取整列EXCEL的数据
                                if ($data['changedata'][$value]=='strtotime') {
                                    /* 日期格式转时间戳处理 */
                                    $excel_data[$_row][$value]=strtotime($currSheet->getCell($cellId)->getFormattedValue());
                                                                  
                                } else{
                                    $excel_data[$_row][$value] = trim($currSheet->getCell($cellId)->getFormattedValue());
                                }


                                if (!empty($excel_data[$_row][$value])) {
                                    $isNull = false;
                                }
                            }
                        }

                        /* 判断是否整行数据为空，是的话删除该行数据 */
                        if ($isNull) {
                            unset($excel_data[$_row]);
                        } else {
                            switch ($data['import_type']) {
                                case 'updata':
                                    //把数据写入数据库
                                    $result = Db::table($data['table_name'])->where($data['key'], $excel_data[$_row][$data['key']])->data($excel_data[$_row])->update();
                                    if ($result == 1) {
                                        echo '成功更新导入excle表第' . $_row . '行数据<br>';
                                    } else {
                                        echo '导入excle表第' . $_row . '行数据失败<br>';
                                    }
                                    break;
                                case 'insert':
                                    //把数据写入数据库
                                    //判断是否存在主键，若存在，则进行匹配导入
                                    if (isset($data['key'])) {
                                        $q = Db::table($data['table_name'])->where($data['key'], $excel_data[$_row][$data['key']])->find();
                                        if ($q) {
                                            $result = Db::table($data['table_name'])->insert($excel_data[$_row]);
                                        } else {
                                            $result = 0;
                                        }
                                    } else {
                                        $result = Db::table($data['table_name'])->insert($excel_data[$_row]);
                                    }
                                    if ($result == 1) {
                                        echo '成功添加导入excle表第' . $_row . '行数据<br>';
                                    } else {
                                        echo '导入excle表第' . $_row . '行数据失败<br>';
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }
            
             //删除已上传的文件
             $del_met = 'app\admin\controller\Index';
             $del_met::file_del($data['Excel_file2']);
            
            } 
            catch (\Exception $e) {
                throw $e;
            }


           
        }
    }

    /**
     * 读取Excel
     *
     * @param string $file      文件地址
     * @param int    $sheet     工作表sheet(传0则获取第一个sheet)
     * @param int    $columnCnt 列数(传0则自动获取最大列)
     * @param int    $rownum    指定行(传1则自动获取所有行)
     * @param array  $options   操作选项
     *                          array mergeCells 合并单元格数组
     *                          array formula    公式数组
     *                          array format     单元格格式数组
     *
     * @return array
     * @throws Exception
     */
    function readExecl(string $file = '', int $sheet = 0, int $rownum = 0, int $columnCnt = 0, $options = [])
    {
        try {
            /* 转码 */
            $file = iconv("utf-8", "gb2312", $file);

            if (empty($file) or !file_exists($file)) {
                throw new \Exception('文件不存在!');
            }

            /** @var Xlsx $objRead */
            $objRead = IOFactory::createReader('Xlsx');

            if (!$objRead->canRead($file)) {
                /** @var Xls $objRead */
                $objRead = IOFactory::createReader('Xls');

                if (!$objRead->canRead($file)) {
                    throw new \Exception('只支持导入Excel文件！');
                }
            }

            /* 如果不需要获取特殊操作，则只读内容，可以大幅度提升读取Excel效率 */
            empty($options) && $objRead->setReadDataOnly(true);
            /* 建立excel对象 */
            $obj = $objRead->load($file);
            /* 获取指定的sheet表 */
            $currSheet = $obj->getSheet($sheet);

            if (isset($options['mergeCells'])) {
                /* 读取合并行列 */
                $options['mergeCells'] = $currSheet->getMergeCells();
            }

            if (0 == $columnCnt) {
                /* 取得最大的列号 */
                $columnH = $currSheet->getHighestColumn();
                /* 兼容原逻辑，循环时使用的是小于等于 */
                $columnCnt = Coordinate::columnIndexFromString($columnH);
            }

            /* 获取总行数 */
            $rowCnt = $currSheet->getHighestRow();
            $data   = [];
            if ($rownum == 0) {
                $start_row = 1;
                $end_row = $rowCnt;
            } else {
                $start_row = $rownum;
                $end_row = $rownum;
            }

            /* 读取内容 */
            for ($_row = $start_row; $_row <= $end_row; $_row++) {
                $isNull = true;

                for ($_column = 1; $_column <= $columnCnt; $_column++) {
                    $cellName = Coordinate::stringFromColumnIndex($_column);
                    $cellId   = $cellName . $_row;
                    $cell     = $currSheet->getCell($cellId);

                    if (isset($options['format'])) {
                        /* 获取格式 */
                        $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
                        /* 记录格式 */
                        $options['format'][$_row][$cellName] = $format;
                    }

                    if (isset($options['formula'])) {
                        /* 获取公式，公式均为=号开头数据 */
                        $formula = $currSheet->getCell($cellId)->getValue();

                        if (0 === strpos($formula, '=')) {
                            $options['formula'][$cellName . $_row] = $formula;
                        }
                    }

                    if (isset($format) && 'm/d/yyyy' == $format) {
                        /* 日期格式翻转处理 */
                        $cell->getStyle()->getNumberFormat()->setFormatCode('yyyy/mm/dd');
                    }

                    $data[$_row][$cellName] = trim($currSheet->getCell($cellId)->getFormattedValue());

                    if (!empty($data[$_row][$cellName])) {
                        $isNull = false;
                    }
                }

                /* 判断是否整行数据为空，是的话删除该行数据 */
                if ($isNull) {
                    unset($data[$_row]);
                }
            }
            $excel_info = [
                'data' => $data,
                'rowCnt' => $rowCnt,
                'rownum' => $rownum

            ];
           
            return $excel_info;
        } catch (\Exception $e) {
            throw $e;
        }
    }






    // 导出
    public function export()
    {
        $tables = Request::param('id');
        if (!empty($tables)) {

            $columns = Db::query("show COLUMNS FROM " . $tables);
            $columns_checkbox = [];
            $checkbox_value = [];
            foreach ($columns as $k => $v) {
                $checkbox_title = \app\common\model\Field::where('field', $v['Field'])->value('name');
                $checkbox_title = $checkbox_title . '(' . $v['Field'] . ')';
                $checkbox_value[] = $v['Field'];
                $columns_checkbox[$v['Field']] = $checkbox_title;
            }
            $data = [
                "columns_checkbox" => $columns_checkbox,
                'table_name' => $tables
            ];
            View::assign($data);
            return View::fetch();
        }
    }
    public function exportPost()
    {

        if (Request::isPost()) {
            $data   = Request::except(['file'], 'post');
            if (empty($data['col'])) {
                return "未选择要导出的字段";
            }
            $table = $data['table_name'];
            $columns = $data['col'];
            $list = Db::table($table)->field($columns)->select()->toArray();
            // 初始化表头数组
            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->calculateColumnWidths();
            $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER); //※默认垂直居中
            $spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); //※默认水平居中
            $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑'); //※默认字体
            $spreadsheet->getDefaultStyle()->getFont()->setSize(10); //※默认字体大小
            foreach ($columns as $k => $v) {
                $sheet->setCellValueByColumnAndRow($k + 1, 2, $v); //※修改为以坐标获取单元格的方式赋值
                $column_max = $k + 1; //※获取最大列标
                $sheet->getColumnDimensionByColumn($column_max)->setAutoSize(true); //※把所有列宽设置为自动列宽
            }
            foreach ($list as $key => $value) {
                foreach ($columns as $k => $v) {
                    if ($data[$v] == 'datetime') {
                        $CellValue = date('Y-m-d h:i:s', $value[$v]);
                    } else {
                        $CellValue = $value[$v];
                    }
                    $sheet->setCellValueExplicitByColumnAndRow($k + 1, $key + 3, $CellValue,DataType::TYPE_STRING); //※修改为以坐标获取单元格的方式赋值,并且强制赋予文本格式
                    $row_max = $key + 3; //※获取最大行标
                }
                //※设置主体单元格样式
            }
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyleByColumnAndRow(1, 2, $column_max, $row_max)->applyFromArray($styleArray);
            //※设置主体单元格样式

            $sheet->mergeCellsByColumnAndRow(1, 1, $column_max, 1); //※合并单元格作为大标题
            $sheet->setCellValueByColumnAndRow(1, 1, $table); //※写入标题
            $sheet->getStyleByColumnAndRow(1, 1)->getFont()->setName('黑体'); //※设置标题字体
            $sheet->getStyleByColumnAndRow(1, 1)->getFont()->setSize(18); //※设置标题字号
            $spreadsheet->getActiveSheet()->setTitle($table); //※设置工作表标签页标题
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $table . '_' . date('Ymd_his', time()) . '_导出' . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        } else {
            echo '未选择导出字段';
        }
    }
    //  ZTX-011，增加数据库导入导出功能。

    // 备份
    public function backup()
    {
        $tables = Request::param('id');
        if (!empty($tables)) {
            $tables = explode(',', $tables);
            foreach ($tables as $table) {
                $this->db->setFile()->backup($table, 0);
            }
            return json(['error' => 0, 'msg' => '备份成功！']);
        } else {
            return json(['error' => 1, 'msg' => '请选择要备份的表！']);
        }
    }

    // 优化
    public function optimize()
    {
        $tables = Request::param('id');
        if (empty($tables)) {
            return json(['error' => 1, 'msg' => '请选择要优化的表！']);
        }
        $tables = explode(',', $tables);
        if ($this->db->optimize($tables)) {
            return json(['error' => 0, 'msg' => '数据表优化成功！']);
        } else {
            return json(['error' => 1, 'msg' => '数据表优化出错请重试！']);
        }
    }

    // 修复
    public function repair()
    {
        $tables = Request::param('id');
        if (empty($tables)) {
            return json(['error' => 1, 'msg' => '请选择要修复的表！']);
        }
        $tables = explode(',', $tables);
        if ($this->db->repair($tables)) {
            return json(['error' => 0, 'msg' => '数据表修复成功！']);
        } else {
            return json(['error' => 1, 'msg' => '数据表修复出错请重试！']);
        }
    }

    // ===========================

    // 还原
    public function restore()
    {
        $list = $this->db->fileList();
        $total = 0;
        $listNew = [];
        foreach ($list as $k => $v) {
            $total += $v['size_num'];
            $listNew[] = $list[$k];
        }
        $list = $listNew;
        array_multisort(array_column($list, 'time'), SORT_DESC, $list);

        // 设置主键
        $pk = 'time';
        // 字段信息
        $columns = [
            ['time', '编号',],
            ['name', '文件名称'],
            ['part', '分卷'],
            ['size', '文件大小'],
            ['compress', '分隔符'],
            ['addtime', '创建时间', '', '', '', '', 'true'],
        ];
        // 可搜索的字段
        $search = [
            // ['text', 'name', '文件名称', 'LIKE'],
        ];
        // 提示信息
        $pageTips = '备份文件列表中共有 ' . count($list) . ' 个文件，共计 ' . format_bytes($total);

        // 搜索
        if (Request::param('getList') == 1) {
            // 排序规则
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc = Request::param('isAsc') ?? 'desc';
            $isAsc = $isAsc == 'desc' ? SORT_DESC : SORT_ASC;
            // 排序处理
            $date = array_column($list, $orderByColumn);
            array_multisort($date, $isAsc, $list);
            if (Request::param('name')) {
                foreach ($list as $k => $v) {
                    if (strpos($v['name'], Request::param('name')) == false) {
                        unset($list[$k]);
                    }
                }
            }
            // 渲染输出
            $result = [
                'total' => count($list),
                'per_page' => 1000,
                'current_page' => 1,
                'last_page' => 1,
                'data' => $list,
            ];
            return $result;
        }
        // 构建页面
        return TableBuilder::getInstance()
            ->setUniqueId($pk) // 设置主键
            ->addColumns($columns) // 添加列表字段数据
            ->setSearch($search) // 添加头部搜索
            ->setPageTips($pageTips, 'warning') // 提示信息
            ->setPagination('false') // 关闭分页显示
            ->addColumn('right_button', '操作', 'btn')
            ->addTopButtons('del')
            ->addRightButton('info', [
                'title' => '恢复',
                'icon'  => 'fa fa-exclamation-triangle',

                'class' => 'btn btn-flat btn-warning btn-xs confirm',
                'href'  => url('import', ['id' => '__time__'])
            ]) // 添加额外按钮
            ->addRightButton('info', [
                'title' => '下载',
                'icon'  => 'fa fa-download',
                'target' => '_blank',
                'class' => 'btn btn-flat btn-success btn-xs confirm',
                'href'  => url('downFile', ['id' => '__time__'])
            ]) // 添加额外按钮
            ->addRightButton('delete')
            ->fetch();
    }

    // 执行还原数据库操作
    public function import(string $id)
    {
        $list = $this->db->getFile('timeverif', $id);
        $this->db->setFile($list)->import(1);
        // return json(['error' => 0, 'msg' => '还原成功！']);
         //ZTX-数据库还原时弹出模态框并跳转上一页
         return "<script>toastr.success('还原成功');window.history.back();</script>";
    }

    // 下载
    public function downFile(string $id)
    {
        $this->db->downloadFile($id);
    }

    // 删除sql文件
    public function del(string $id)
    {
        if (Request::isPost()) {
            if (strpos($id, ',') !== false) {
                $idArr = explode(',', $id);
                foreach ($idArr as $k => $v) {
                    $this->db->delFile($v);
                }
                return json(['error' => 0, 'msg' => "删除成功！"]);
            }
            if ($this->db->delFile($id)) {
                return json(['error' => 0, 'msg' => "删除成功！"]);
            } else {
                return json(['error' => 1, 'msg' => "备份文件删除失败，请检查文件权限！"]);
            }
        }
    }
}
