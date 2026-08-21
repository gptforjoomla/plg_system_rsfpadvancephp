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

?>
<table class="admintable">
<tr>
	<td valign="top" align="left">
		<table>
			<?php foreach($events as $nr): ?>
            	<tr>
                    <td colspan="2" align="left" nowrap="nowrap"><h2><?php echo JText::_('PLG_SYSTEM_RSFPADVANCEPHP_ACTIVE_'.$JText[$nr]); ?></h2></td>
                </tr>
                <tr>
                    <td colspan="2" align="left" nowrap="nowrap"><?php echo JText::_('PLG_SYSTEM_RSFPADVANCEPHP_ACTIVE'); ?> <?php echo $lists['active_'.$nr]; ?></td>
                </tr>
                <tr class="block_<?php echo $nr; ?>">
                    <td colspan="2" align="left" nowrap="nowrap">
                    	<p> <strong><?php echo JText::_('PLG_SYSTEM_RSFPADVANCEPHP_NOTICE') ?></strong> <br/> <pre><code><?php echo JText::_('PLG_SYSTEM_RSFPADVANCEPHP_NOTICE_'.$JText[$nr]); ?></code></pre> </p>
                	</td>
                </tr>
                <tr class="block_<?php echo $nr; ?>">
                    <td colspan="2" align="center" ><?php echo $lists['code_'.$nr]; ?></td>
                </tr>
                <tr>
                    <?php if($nr != 11): ?>
                    	<td colspan="2" align="center" nowrap="nowrap" width="1500"><hr/></td>
                    <?php else: ?>
                    	<td colspan="2" align="center" nowrap="nowrap" ></td>
                    <?php endif; ?>
                </tr>
                
            <?php endforeach; ?>
		</table>
	</td>
	<td valign="top">&nbsp;
		
	</td>
</tr>
</table>
<script>
jQuery(document).ready(function(){
	<?php foreach($events as $nr): ?>
		jQuery("input:radio[name=rsfpadvancephp_active_<?php echo $nr ?>]").click(function() {
			var value = jQuery(this).val();
			codeBlockSwitch(value, <?php echo $nr ?>);
		});
		var value<?php echo $nr ?> = <?php echo $events_active[$nr]; ?>;
		codeBlockSwitch(value<?php echo $nr ?>, <?php echo $nr ?>);
	<?php endforeach; ?>
	
});
function codeBlockSwitch(value, code){
	if(value == 1){
		jQuery(".block_"+code).show();
	} else {
		jQuery(".block_"+code).hide();
	}
}
</script>
