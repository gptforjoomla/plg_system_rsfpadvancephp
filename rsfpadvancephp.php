<?php
/**
* 
* 	@version 	3.0.0 Jun 12, 2021
* 	@package 	RSForm  - Advance PHP plugin
* 	@author  	Llewellyn van der Merwe <llewellyn@vdm.io>
* 	@copyright	Copyright (C) 2013 Vast Development Method <http://www.vdm.io>
* 	@license	GNU General Public License <http://www.gnu.org/copyleft/gpl.html>
*
**/

defined('JPATH_BASE') or die;

/**
 * RSForm! Pro - Advance PHP
 */
class plgSystemRSFPadvancephp extends JPlugin
{
	protected $hasAccess;
	protected $VDM_post;
	protected $VDM_active;
	protected $VDM_code;
	protected $VDM_rsform_id;
	
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;
	
	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since   1.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		
		// set backend access
		$this->hasAccess = $this->backendAccess();
		
		// set the table path
		$tablePath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_rsform' . DIRECTORY_SEPARATOR . 'tables';
		JTable::addIncludePath($tablePath);
	}
	
	/**
	*	//////    Vast Development Method   \\\\\\
	*	###########################################
	*   ##########       BACK END      ############
	*   ##########    Event Triggers   ############
	*	###########################################
	*
	**/
		
	/** 
	 * Event Triggered in Back-end [On Form Save]
	 */
	public function onRsformFormSave($form)
	{
		if($this->hasAccess){
			$post = JRequest::get('post', JREQUEST_ALLOWRAW);
			$formId = (int)$post['formId'];
			
			// get active states
			$events = range(0,11);
			$events_active = array();
			$events_code = array();
			foreach($events as $nr){
				$events_active[$nr] 	= (int)$post['rsfpadvancephp_active_'.$nr];
				if($events_active[$nr] != 0){
					$events_code[$nr] 	= base64_encode($post['rsfpadvancephp_code_'.$nr]);
				}
			}
			if(is_array($events_active) && count($events_active)){
				$events_active = json_encode($events_active);
				
			}
			if(is_array($events_code) && count($events_code)){
				$events_code = json_encode($events_code);
			} else {
				$events_code = '';
			}
			
			// Get a db connection.
			$db = JFactory::getDbo();
			// check if data is set
			$db->setQuery("SELECT form_id FROM #__rsform_advancephp WHERE form_id='".$formId."'");
			if (!$db->loadResult())
			{
				// Create a new query object.
				$query = $db->getQuery(true);
				 
				// Insert columns.
				$columns = array('form_id','events_active','events_code');
				 
				// Insert values.
				$values = array($formId, $db->quote($events_active), $db->quote($events_code));
				 
				// Prepare the insert query.
				$query
					->insert($db->quoteName('#__rsform_advancephp'))
					->columns($db->quoteName($columns))
					->values(implode(',', $values));
				 
				// Set the query using our newly populated query object and execute it.
				$db->setQuery($query);
				
				return $db->query();
			}
			
			$query = $db->getQuery(true);
			 
			// Fields to update.
			$fields = array(	
				$db->quoteName('events_active') . ' = ' . $db->quote($events_active),
				$db->quoteName('events_code') . ' = ' . $db->quote($events_code)
			);
			 
			// Conditions for which records should be updated.
			$conditions = array(
				$db->quoteName('form_id') . ' = ' . $formId
			);
			 
			$query->update($db->quoteName('#__rsform_advancephp'))->set($fields)->where($conditions);
			 
			$db->setQuery($query);
			 
			return $db->query();
		}
	}
	
	/**
	 * Event Triggered in Back-end [On After Show Form Edit Tabs Tab]
	 */
	public function  onRsformBackendAfterShowFormEditTabsTab()
	{
		if($this->hasAccess){
			$lang = JFactory::getLanguage();
			$lang->load('plg_system_rsfpadvancephp');
			
			echo '<li><a id="scripts" href="javascript: void(0);"><span>'.JText::_('PLG_SYSTEM_RSFPADVANCEPHP_JOOMLA_PROFILE_TAB').'</span></a></li>';
		}
	}
	
	/**
	 * Event Triggered in Back-end [On After Show Form Edit Tabs]
	 */
	public function onRsformBackendAfterShowFormEditTabs()
	{
		if($this->hasAccess){
			// set event range
			$events = range(0,11);
			// set the lang range
			$JText 	= range('B','M'); 
			
			// set language
			$lang = JFactory::getLanguage();
			$lang->load('plg_system_rsfpadvancephp');
			
			// get set values
			$formId = JRequest::getInt('formId');
			$row = JTable::getInstance('RSForm_Advancephp', 'Table');
			if (!$row) {
				return;
			}
			$row->load($formId);
			if(strlen($row->events_active) > 0){
				$events_active 	= json_decode($row->events_active, true);
			} else {
				foreach($events as $nr){
					$events_active[$nr] = 0;
				}
			}
			if(strlen($row->events_code) > 0){
				$events_code 	= json_decode($row->events_code, true);
			} else {
				foreach($events as $nr){
					$events_code[$nr] = '';
				}
			}
			foreach($events as $nr){
				if(isset($events_code[$nr])){
					if(base64_encode(base64_decode($events_code[$nr], true)) === $events_code[$nr]){
						$events_code[$nr] = base64_decode($events_code[$nr]);
					}
					$lists['active_'.$nr] 	= RSFormProHelper::renderHTML('select.booleanlist','rsfpadvancephp_active_'.$nr,'class="inputbox"',$events_active[$nr]);
					$lists['code_'.$nr] 	= '<textarea class="rs_textarea codemirror-php" name="rsfpadvancephp_code_'.$nr.'" id="code_'.$nr.'" rows="20" style="width: 98%;" cols="900" filter="raw" >'
											. htmlspecialchars($events_code[$nr], ENT_COMPAT, 'UTF-8') . '</textarea>';
				} else {
					$lists['active_'.$nr] 	= RSFormProHelper::renderHTML('select.booleanlist','rsfpadvancephp_active_'.$nr,'class="inputbox"',$events_active[$nr]);
					$lists['code_'.$nr] 	= '<textarea class="rs_textarea codemirror-php" name="rsfpadvancephp_code_'.$nr.'" id="code_'.$nr.'" rows="20" style="width: 98%;" cols="900" filter="raw" ></textarea>';
				}
			}
			echo '<div id="rsfpadvancephpdiv">';
				include JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/rsfpadvancephp.php';
			echo '</div>';
		}
	}
	
	/** 
	 * Event Triggered in Back-end [On After Show Configuration Tabs]
	 */	
	public function onRsformBackendAfterShowConfigurationTabs($tabs)
	{
		if($this->hasAccess){
			$lang = JFactory::getLanguage();
			$lang->load('plg_system_rsfpadvancephp');
			
			$tabs->addTitle(JText::_('PLG_SYSTEM_RSFPADVANCEPHP_CONFIG_TAB'), 'form-advancephp');
			$tabs->addContent($this->configurationScreen());
		}
	}
	
	/**
	 * Sets the tab display for the Configuration Screen
	 */
	protected function configurationScreen()
	{
		if($this->hasAccess){
			$lang = JFactory::getLanguage();
			$lang->load('plg_system_rsfpadvancephp');
			
			return JText::_('PLG_SYSTEM_RSFPADVANCEPHP_CONFIG_NOTICE');
		}
	}
	
	/**
	*	//////    Vast Development Method   \\\\\\
	*	###########################################
	*   ##########      FRONT END      ############
	*   ##########    Event Triggers   ############
	*	###########################################
	*
	**/
	
	/**
	 * Event Triggered in Front-end [On Init Form Display]
	 *
	 * $args = array('find'=>&$find,'replace'=>&$replace,'formLayout'=>&$formLayout)
	 */
	/*public function onRsformFrontendInitFormDisplay($args)
	{
		-- >>  We need a formId to have this work  << ---
		
		$this->setEvents($this->VDM_rsform_id);
		if($this->VDM_active[0] == 1){
			eval($this->VDM_code[0]);
		}
	}*/
	
	/**
	 * Event Triggered in Front-end [On Before Form Display]
	 *
	 * $args = array('formLayout'=>&$formLayout,'formId'=>$formId)
	 */
	public function onRsformFrontendBeforeFormDisplay($args)
	{
		$this->setEvents($args['formId']);
		if($this->VDM_active[0] == 1){
			$data = $this->VDM_code[0];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	 * Event Triggered in Front-end [On Before Form Validation]
	 *
	 * $args = array('invalid'=>&$invalid, 'formId' => $formId, 'post' => &$post)
	 */
	public function onRsformFrontendBeforeFormValidation($args)
	{
		$this->setEvents($args['formId']);
		if($this->VDM_active[1] == 1){
			$data = $this->VDM_code[1];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	 * Event Triggered in Front-end [On Before Form Process]
	 *
	 * $args = array('post' => &$post)
	 */
	public function onRsformFrontendBeforeFormProcess($args)
	{
		$this->setEvents($args['post']['formId']);
		if($this->VDM_active[2] == 1){
			$data = $this->VDM_code[2];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	 * Event Triggered in Front-end [On Before Store Submissions]
	 *
	 * $args = array('formId'=>$formId,'post'=>&$post,'SubmissionId'=>$SubmissionId)
	 */
	public function onRsformFrontendBeforeStoreSubmissions($args)
	{
		$this->setEvents($args['formId']);
		if($this->VDM_active[3] == 1){
			$data = $this->VDM_code[3];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}	
	
	/** 
	 * Event Triggered in Front-end [On After Store Submissions]
	 *
	 * $args = array('SubmissionId'=>$SubmissionId, 'formId'=>$formId)
	 */		
	public function  onRsformFrontendAfterStoreSubmissions($args)
	{
		$this->setEvents($args['formId']);
		if($this->VDM_active[4] == 1){
			$data = $this->VDM_code[4];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	 * Event Triggered in Front-end [On After Form Process]
	 *
	 * $args = array('SubmissionId'=>$SubmissionId,'formId'=>$formId)
	 */
	public function onRsformFrontendAfterFormProcess($args)
	{
		$this->setEvents($args['formId']);
		if($this->VDM_active[5] == 1){
			$data = $this->VDM_code[5];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	
	/** 
	 * Event Triggered in Front-end [On After Show Thankyou Message]
	 *
	 * $args = array('output'=>&$output,'formId'=>&$formId)
	 */		
	public function onRsformFrontendAfterShowThankyouMessage($args)
	{
		$this->setEvents($args['formId']);
		if($this->VDM_active[6] == 1){
			$data = $this->VDM_code[6];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	 * Event Triggered in Front-end [On After Create Placeholders]
	 *
	 * $args = array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submission' => $submission)
	 */
	public function onRsformAfterCreatePlaceholders($args)
	{
		$this->setEvents($args['form']->FormId);
		if($this->VDM_active[7] == 1){
			$data = $this->VDM_code[7];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	 * Event Triggered in Front-end [On Before User Email]
	 *
	 * $args = array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'userEmail'=>&$userEmail)
	 */
	public function onRsformBeforeUserEmail($args)
	{
		$this->setEvents($args['form']->FormId);
		if($this->VDM_active[8] == 1){
			$data = $this->VDM_code[8];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	
	/**
	 * Event Triggered in Front-end [On Before Admin Email]
	 *
	 * $args = array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'adminEmail'=>&$adminEmail)
	 */
	public function onRsformBeforeAdminEmail($args)
	{
		$this->setEvents($args['form']->FormId);
		if($this->VDM_active[9] == 1){
			$data = $this->VDM_code[9];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	 * Event Triggered in Front-end [On Before Additional Email]
	 *
	 * $args = array('form'=>&$form,'placeholders'=>&$placeholders,'values'=>&$values,'submissionId'=>$SubmissionId,'additionalEmail'=>&$additionalEmail)
	 */
	public function onRsformBeforeAdditionalEmail($args)
	{
		$this->setEvents($args['form']->FormId);
		if($this->VDM_active[10] == 1){
			$data = $this->VDM_code[10];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	 * Event Triggered in Front-end [On After Confirm Payment]
	 *
	 * $SubmissionId = 'SubmissionId'
	 */
	public function onRsformAfterConfirmPayment($SubmissionId)
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		$db->setQuery("SELECT FormId FROM #__rsform_submissions WHERE SubmissionId='".$SubmissionId."'");

		$FormId = $db->loadResult();

		$this->setEvents($FormId);
		if($this->VDM_active[11] == 1){
			$data = $this->VDM_code[11];
			if(base64_encode(base64_decode($data, true)) === $data){
				$data = base64_decode($data);
			}
			eval($data);
		}
	}
	
	/**
	*	Check if this plugin is active on the form
	*
	*	@returns a bool false or true
	**/
	protected function setEvents($formId)
	{
		if(!is_array($this->VDM_active) || !count($this->VDM_active) || $this->VDM_rsform_id != $formId){
			// set form id
			$this->VDM_rsform_id = $formId;
			// set event array
			$events = range(0,11);
			// get set values
			$row = JTable::getInstance('RSForm_Advancephp', 'Table');
			if (!$row) {
				return;
			}
			$row->load($this->VDM_rsform_id);
			if(strlen($row->events_active)){
				$this->VDM_active 	= json_decode($row->events_active, true);
			} else {
				foreach($events as $nr){
					$this->VDM_active[$nr] = 0;
				}
			}
			if(strlen($row->events_code)){
				$this->VDM_code 	= json_decode($row->events_code, true);
			} else {
				foreach($events as $nr){
					$this->VDM_code[$nr] = '';
				}
			}
		}
	}
	
	/**
	*	Check if the user has backend access
	*
	*	@returns a bool false or true
	**/
	protected function backendAccess()
	{
		// get user
		$userId = JFactory::getUser()->id;
		$userGroup = JUserHelper::getUserGroups($userId);
		$accessGroups = $this->params->get('access');
		if(is_array($userGroup)){
			if(is_array($accessGroups)){
				return (count(array_intersect($accessGroups, $userGroup))) ? true : false;
			} else {
				// return true if not set
				return true;
			}
		} 
		return false;			
	}
}
