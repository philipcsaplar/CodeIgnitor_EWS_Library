CREATE TABLE IF NOT EXISTS `exchange_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exchange_host` varchar(255) NOT NULL,
  `exchange_user` varchar(255) NOT NULL,
  `exchange_password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;