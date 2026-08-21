<?php
/**
* 
* 	@version 	1.9.6 Feb 28, 2015
* 	@package 	RSFormPro! - Advance PHP plugin
* 	@author  	Llewellyn van der Merwe <llewellyn@vdm.io>
* 	@copyright	Copyright (C) 2013 Vast Development Method <http://www.vdm.io>
* 	@license	GNU General Public License <http://www.gnu.org/copyleft/gpl.html>
*
**/

defined('_JEXEC') or die('Restricted access');

class TableRSForm_Advancephp extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	var $form_id = null;
	var $events_active;
	var $events_code;
		
	/**
	 * Constructor
	 *
	 * @since   1.5
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__rsform_advancephp', 'form_id', $db);
	}
}