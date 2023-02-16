
-- -----------------------------
-- Table structure for `tp_users_copy`
-- -----------------------------
DROP TABLE IF EXISTS `tp_users_copy`;
CREATE TABLE `tp_users_copy` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `sex` tinyint(1) NOT NULL DEFAULT 0 COMMENT '性别:0=保密,1=男,2=女',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `qq` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'QQ',
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '手机号',
  `mobile_validated` tinyint(3) DEFAULT 0 COMMENT '验证手机:1=验证,0=未验证',
  `email_validated` tinyint(3) DEFAULT 0 COMMENT '验证邮箱:1=验证,0=未验证',
  `type_id` tinyint(3) DEFAULT 0 COMMENT '所属分组',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态',
  `create_ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '注册IP',
  `update_time` int(11) unsigned DEFAULT 0 COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='会员表';

-- -----------------------------
-- Records of `tp_users_copy`
-- -----------------------------
INSERT INTO `tp_users_copy` VALUES ('1', 'test001@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '2', '1583746801', '127.0.0.1', '222222', '1583746801', '0', '0', '1', '1', '127.0.0.1', '1583747367', '1541405155');
INSERT INTO `tp_users_copy` VALUES ('2', 'test002@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '0', '1541405185', '127.0.0.1', '407593529', '1541405185', '0', '0', '2', '1', '127.0.0.1', '1541405155', '1541405185');
INSERT INTO `tp_users_copy` VALUES ('3', 'test003@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '1546060654', '127.0.0.1', '', '1546060654', '0', '0', '1', '1', '127.0.0.1', '1541405155', '1546060654');
INSERT INTO `tp_users_copy` VALUES ('4', 'test004@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '1546060666', '127.0.0.1', '', '1546060666', '0', '0', '1', '1', '127.0.0.1', '1541405155', '1546060666');
INSERT INTO `tp_users_copy` VALUES ('5', 'test005@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '1546060680', '127.0.0.1', '', '1546060680', '0', '0', '1', '1', '127.0.0.1', '1579591129', '1546060680');
INSERT INTO `tp_users_copy` VALUES ('6', 'test007@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '0', '1546061841', '127.0.0.1', '', '1546061841', '0', '0', '1', '1', '127.0.0.1', '1541405155', '1546061841');
INSERT INTO `tp_users_copy` VALUES ('7', 'test008@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '0', '1546062123', '127.0.0.1', '123', '1546062123', '1', '0', '1', '1', '127.0.0.1', '1551844614', '1546061953');
INSERT INTO `tp_users_copy` VALUES ('13', 'test009@qq.com', '96e79218965eb72c92a549dd5a330112', '0', '1583747029', '127.0.0.1', '', '1583747029', '0', '0', '1', '1', '127.0.0.1', '0', '1583747029');
INSERT INTO `tp_users_copy` VALUES ('14', 'test001@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '2', '1583746801', '127.0.0.1', '222222', '1583746801', '0', '0', '1', '1', '127.0.0.1', '1583747367', '1541405155');
INSERT INTO `tp_users_copy` VALUES ('15', 'test002@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '0', '1541405185', '127.0.0.1', '407593529', '1541405185', '0', '0', '2', '1', '127.0.0.1', '1541405155', '1541405185');
INSERT INTO `tp_users_copy` VALUES ('16', 'test003@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '1546060654', '127.0.0.1', '', '1546060654', '0', '0', '1', '1', '127.0.0.1', '1541405155', '1546060654');
INSERT INTO `tp_users_copy` VALUES ('17', 'test004@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '1546060666', '127.0.0.1', '', '1546060666', '0', '0', '1', '1', '127.0.0.1', '1541405155', '1546060666');
INSERT INTO `tp_users_copy` VALUES ('18', 'test005@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '1546060680', '127.0.0.1', '', '1546060680', '0', '0', '1', '1', '127.0.0.1', '1579591129', '1546060680');
INSERT INTO `tp_users_copy` VALUES ('19', 'test007@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '0', '1546061841', '127.0.0.1', '', '1546061841', '0', '0', '1', '1', '127.0.0.1', '1541405155', '1546061841');
INSERT INTO `tp_users_copy` VALUES ('20', 'test008@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '0', '1546062123', '127.0.0.1', '123', '1546062123', '1', '0', '1', '1', '127.0.0.1', '1551844614', '1546061953');
INSERT INTO `tp_users_copy` VALUES ('21', 'test009@qq.com', '96e79218965eb72c92a549dd5a330112', '0', '1583747029', '127.0.0.1', '', '1583747029', '0', '0', '1', '1', '127.0.0.1', '0', '1583747029');
