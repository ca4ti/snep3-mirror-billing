/**
 * Database structure
 *
 * @category  Snep
 * @package   Billing
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */

--
-- Table structure for table `tarifas`
--
CREATE TABLE IF NOT EXISTS `tarifas` (
  `operadora` int(11) NOT NULL default '0',
  `ddi` smallint(6) NOT NULL default '0',
  `pais` varchar(30) NOT NULL default '',
  `ddd` smallint(6) NOT NULL default '0',
  `cidade` varchar(30) NOT NULL default '',
  `estado` char(2) NOT NULL default '',
  `prefixo` varchar(6) NOT NULL default '',
  `codigo` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`codigo`),
  UNIQUE KEY `operadora` (`operadora`,`ddi`,`ddd`,`prefixo`,`cidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `tarifas_valores`
--
CREATE TABLE IF NOT EXISTS `tarifas_valores` (
  `codigo` int(11) NOT NULL,
  `data` datetime NOT NULL,
  `vcel` float NOT NULL default '0',
  `vfix` float NOT NULL default '0',
  `vpf` float NOT NULL default '0',
  `vpc` float NOT NULL default '0',
  PRIMARY KEY  (`codigo`,`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `operadoras`
--
CREATE TABLE IF NOT EXISTS `operadoras` (
  `codigo` bigint(20) unsigned NOT NULL auto_increment,
  `nome` varchar(50) NOT NULL,
  `tpm` int(11) default '0',
  `tdm` int(11) default '0',
  `tbf` float default '0',
  `tbc` float default '0',
  `vpf` float NOT NULL default '0',
  `vpc` float NOT NULL default '0',
  PRIMARY KEY  (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `oper_ccustos`
--
CREATE TABLE IF NOT EXISTS `oper_ccustos` (
  `operadora` int(11) NOT NULL,
  `ccustos` char(7) NOT NULL,
  PRIMARY KEY  (`operadora`,`ccustos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `oper_contas`
--
CREATE TABLE IF NOT EXISTS `oper_contas` (
  `operadora` int(11) NOT NULL,
  `conta` int(11) NOT NULL,
  PRIMARY KEY  (`operadora`,`conta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;