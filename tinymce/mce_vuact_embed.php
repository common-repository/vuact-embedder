<?php

if ( !defined('ABSPATH') )
    die('You are not allowed to call this page directly.');

global $wpdb;

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

class SaferScript {
	var $source, $allowedCalls;

	function SaferScript($scriptText) {
		$this->source = $scriptText;
		$this->allowedCalls = array();
	}

	function allowHarmlessCalls() {
		$this->allowedCalls = explode(',',
				'explode,implode,date,time,round,trunc,rand,ceil,floor,srand,'.
				'strtolower,strtoupper,substr,stristr,strpos,print,print_r,md5');
	}

	function parse() {
		$this->parseErrors = array();
		$tokens = token_get_all('<?'.'php '.$this->source.' ?'.'>');
		$vcall = '';

		foreach ($tokens as $token) {
			if (is_array($token)) {
				$id = $token[0];
				switch ($id) {
					case(T_VARIABLE): { $vcall .= 'v'; break; }
					case(T_STRING): { $vcall .= 's'; }
					case(T_REQUIRE_ONCE): case(T_REQUIRE): case(T_NEW): case(T_RETURN):
					case(T_BREAK): case(T_CATCH): case(T_CLONE): case(T_EXIT):
					case(T_PRINT): case(T_GLOBAL): case(T_ECHO): case(T_INCLUDE_ONCE):
					case(T_INCLUDE): case(T_EVAL): case(T_FUNCTION): {
						if (array_search($token[1], $this->allowedCalls) === false)
							$this->parseErrors[] = 'illegal call: '.$token[1];
					}
				}
			}
			else
				$vcall .= $token;
		}

		if (stristr($vcall, 'v(') != '')
			$this->parseErrors[] = array('illegal dynamic function call');

		return($this->parseErrors);
	}

	function execute($parameters = array()) {
		foreach ($parameters as $k => $v)
			$$k = $v;
		if (sizeof($this->parseErrors) == 0)
			eval($this->source);
		else
			print('cannot execute, script contains errors');
	}
}

// Usage example:
// $ls = new SaferScript('horribleCode();');
// $ls->allowHarmlessCalls();
// print_r($ls->parse());
// $ls->execute();

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Vuact Embedder</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo plugins_url() ?>/vuact-embedder/tinymce/vuact_embed.js"></script>
	<script language="javascript" type="text/javascript">
		var old=false;
	</script>
	<base target="_self" />
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');document.body.style.display='';document.getElementById('vuact_embed_insertlinktab').focus();" style="display: none">
<!-- <form onsubmit="insertLink();return false;" action="#"> -->
	<form name="Vuact_embed" action="#">
	<div class="tabs">
		<ul>
		<li id="vuact_embed_insertlinktab" class="current"><span><a href="javascript:mcTabs.displayTab('vuact_embed_insertlinktab','vuact_embed_insertlinkpanel');" onmousedown="return false;">Insert existing Vuact video</a></span></li>
		<li id="vuact_embed_addvideotab"><span><a href="javascript:mcTabs.displayTab('vuact_embed_addvideotab','vuact_embed_addvideopanel');" onmousedown="return false;">Add new video to your Vuact account</a></span></li>
		</ul>
	</div>

	<div class="panel_wrapper" style="height:280px">

		<div id="vuact_embed_insertlinkpanel" class="panel current" style="height:280px">
			<br />
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<td nowrap="nowrap" valign="top">
						<label><?php _e('Paste a Vuact video URL here.', 'vuact_embed'); ?></label>
					</td>
				</tr>
				<tr>
					<td  nowrap="nowrap" valign="top">
						<input type="text" id="vuact_embedlink" name="vuact_embedlink" style="width: 100%" value="<?php _e('URL', 'vuact_embed'); ?>" onclick="if(!old) { this.value=''; old=true; }"/>
					</td>
				</tr>
				<tr>
					<td nowrap="nowrap" valign="top">
						<label><?php _e('An example URL would look like:', 'vuact_embed'); ?>
							<ul>
								<li>http://www.vuact.com/watch/vuact-official/vuact-product-introduction</li>
							</ul>
							</label>
					</td>
				</tr>
			</table>

			<div class="mceActionPanel">
				<div style="float: left">
					<input type="button" id="cancel" name="cancel" value="<?php _e("Cancel", 'vuact_embed'); ?>" onclick="tinyMCEPopup.close();" />
				</div>

				<div style="float: right">
					<input type="submit" id="insert" name="insert" value="<?php _e("Insert", 'vuact_embed'); ?>" onclick="insertVuact_embedLink();" />
				</div>
			</div>

		</div>

		<div id="vuact_embed_addvideopanel" class="panel">
			<iframe type='text/html' width='540px' height='285px' style='border: 0px none;' src='http://www.vuact.com/embedded/addvideo'></iframe>
		</div>

	</div>

</form>
</body>
</html>
