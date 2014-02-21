#@version 1
#@since 2014-02-21 08:33:17
#@author geroe
#@message initial query to create the dbc_cache table
CREATE TABLE `dbc_cache` (
  `name` varchar(255) NOT NULL,
  `value` text,
  `user` varchar(50) DEFAULT NULL,
  `tmstmp` datetime DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
