
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for task
-- ----------------------------
DROP TABLE IF EXISTS `task`;
CREATE TABLE `task` (
  `id` int(11) NOT NULL DEFAULT '0' COMMENT '任务ID',
  `name` varchar(256) DEFAULT '' COMMENT '任务名称',
  `create_at` datetime DEFAULT NULL COMMENT '创建时间',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1. 执行一次 2.循环执行',
  `separate_time` int(11) DEFAULT NULL COMMENT '执行间隔',
  `status` tinyint(4) DEFAULT NULL COMMENT '执行状态 0.未开始 1. 执行中 -1.执行失败 -2.手动暂停',
  `remark` text COMMENT '备注信息',
  `fn` text COMMENT '要执行的数据库存储过程或函数',
  `start_time` datetime DEFAULT NULL COMMENT '开始执行时间',
  `next_exec_time` datetime DEFAULT NULL COMMENT '下次执行时间',
  `last_exec_time` datetime DEFAULT NULL COMMENT '上次执行时间',
  `fn_type` varchar(64) DEFAULT NULL COMMENT 'email, sql 等等',
  `pid` int(11) DEFAULT '0' COMMENT '当前执行任务的进程id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of task
-- ----------------------------
INSERT INTO `task` VALUES ('4', 'hello_world', '2015-08-21 11:21:10', '2', '86400', '0', '你好世界', 'shell', '2015-09-09 00:21:24', '2016-02-02 00:11:24', '2016-02-01 00:11:24', 'php', '0');
