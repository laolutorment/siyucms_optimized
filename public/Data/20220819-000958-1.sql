
-- -----------------------------
-- Table structure for `tp_awesome_icons`
-- -----------------------------
DROP TABLE IF EXISTS `tp_awesome_icons`;
CREATE TABLE `tp_awesome_icons` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  `sort` mediumint(8) DEFAULT 50 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `code` varchar(255) NOT NULL DEFAULT '' COMMENT '代码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='awesome图标库';

