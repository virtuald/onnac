<?php
/*
* $Id$
*
* Copyright (c) 2006-2007, Dustin Spicuzza
* All rights reserved.
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of Dustin Spicuzza nor the
*       names of any contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY DUSTIN SPICUZZA AND CONTRIBUTORS ``AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL DUSTIN SPICUZZA AND CONTRIBUTORS BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	Page editor for templates and pages 
		
*/



/*

	editor_render_editor
	
	This function outputs a form that can be used to modify webpages and such.
	
		Type: edtemplate or edurl
		unique_id: template_id or url

*/
function editor_render($type,$unique_id,$title,$execute,$bannerID,$templateID,$menuID,$content){

	global $cfg;
	
	$enable_fck = true;
	
	$cplang = $ealang = "html";
	$cp_idx = 1;
	
	if ($type == 'edurl'){
	
		global $render;
		$render['title'] = htmlentities($unique_id);
	
		// what language should we set?
		
		$info = pathinfo($unique_id);
		if (array_key_exists('extension',$info)){
			switch($info['extension']){
				case "php":
				case "php4":
				case "php5":
				case "phtml":
					$ealang = "php";
					$cp_idx = 3;
					break;
				case "js":
					$ealang = "js";
					$cp_idx = 2;
					break;
				case "css":
					$ealang = "css";
					$cp_idx = 0;
					break;
				case "txt":
					$ealang = '';
					$cp_idx = 4;
					break;
			}		
		}
		
		// override setting if execute is set
		if ($execute){
			$ealang = "php";
			$cp_idx = 3;
		}
	}
?><noscript>This editor will most likely NOT work without javascript enabled.</noscript>
<script type="text/javascript" src="##pageroot##/tw-sack.js"></script>
<script type="text/javascript"><!--

	var curEditor = '';
	var initialCode = unescape("<?php echo rawurlencode($content); ?>");
	var lastSavedCode = initialCode;

	var cpLoaded = false, fckLoaded = false, eaLoaded = false;
	var cpLoading = false, fckLoading = false, eaLoading = false;
	var ajax = new sack();

	function getCode() {
		switch (curEditor){
			case "fck":
				if (fckLoaded)
					return FCKeditorAPI.GetInstance('FCKeditor').GetXHTML(true);
				break;
				
			case "cp":
				if (cpLoaded)
					return cp.getCode();
				break;
			
			case "ea":
				if (eaLoaded)
					return editAreaLoader.getValue('ea');
				break; 
				
			default:
				return initialCode;
		}
	}
	
	function setCode(text) {
		
		var oElement = document.getElementById('editor_syntax');
		
		switch (curEditor){
			case "fck":
				FCKeditorAPI.GetInstance('FCKeditor').SetHTML(text);
				break;
				
			case "cp":
				var lang = oElement.options[oElement.selectedIndex].value;
				cp.setCode(text,lang);
				break;
				
			case "ea":
				editAreaLoader.setValue('ea',text);
				editAreaLoader.execCommand('resync_highlight');
				break;
		}
	}
	
	function switchLanguage(){
		setCode(getCode());
	}
	
	function switchEditor(newEditor){
		
		if (newEditor == curEditor || newEditor == "")
			return;
		
		var cpe = document.getElementById("div_codepress");
		var fck = document.getElementById("div_fckedit");
		var ea = document.getElementById("div_ea");
		
		cpe.style.display = 'none';
		fck.style.display = 'none';
		ea.style.display = 'none';
		
		var oElement = document.getElementById('editor_syntax');
		
		switch (newEditor){
			case "cp":
			
				if (!cpLoaded){
					if (cpLoading)
						return;
				
					cpLoading = true;
					setMessage("Loading codepress...");
				
					// load the javascript file dynamically
					loadJSFile(
						"##pageroot##/codepress/codepress.js",
						function(){
							
							document.getElementById("div_codepress").style.display = "block";
							document.getElementById('cp_container').innerHTML = '<text' + 'area id="cp" class="codepress autocomplete-off ' + oElement.options[oElement.selectedIndex].value + '"></text' + 'area>';
							
							document.getElementById('cp').value = getCode();
							curEditor = 'cp';
							
							CodePress.run();
							cpLoaded = true;
							cp.setSaveHandler(saveBegin);
							hideMessage();
						}
					);
					return;
				}
				cpe.style.display = 'block';
				break;
			case "fck":
				
				if (!fckLoaded){
					if (fckLoading)
						return;
					fckLoading = true;
					setMessage("Loading FCKEditor...");
					
					loadJSFile(
						"##pageroot##/FCKeditor/fckeditor.js",
						function(){
							var div = document.getElementById("fck_container");
							var fck = new FCKeditor("FCKeditor");
							fck.BasePath = "##pageroot##/FCKeditor/";
							fck.Height = "450";
							div.innerHTML = fck.CreateHtml();
							fckLoaded = true;
						}
					);
					
					return;
				}
				
				fck.style.display = 'block';
				break;
				
			case "ea":
			
			
				if (!eaLoaded){
					if (eaLoading)
						return;
					eaLoading = true;
					setMessage("Loading Editarea...");
					
					loadJSFile(
						<?php 
						if ($cfg['editarea_compress'])
							echo '"##pageroot##/editarea/edit_area_compressor.php"';
						else
							echo '"##pageroot##/editarea/edit_area_loader.js"';
					?>,
						function(){
							
							try{
								editAreaLoader.window_loaded();
							}catch(e){
								setMessage("Editarea could not be loaded! You should try to set $cfg['editarea_compress'] to false in config.inc.php!");
								return;
							}
							
							document.getElementById("div_ea").style.display = "block";
							editAreaLoader.init({
								id: "ea",
								syntax: "<?php echo $ealang; ?>",
								start_highlight: true,
								toolbar: "save, |, fullscreen, |, search, go_to_line, |, undo, redo, |, select_font, |, change_smooth_selection, highlight, reset_highlight, |, help",
								allow_toggle: false,
								save_callback: "saveBegin",
								EA_load_callback: "hideMessage"
							});
							sw_setCode('ea');
							eaLoaded = true;
						}
					);
					return;
				}
				
				ea.style.display = 'block';
				break;
		}
		
		sw_setCode(newEditor);
	}

	// special function
	function sw_setCode(newEditor){
		var code = getCode();	
		curEditor = newEditor;
		setCode(code);
	}
	
	
	// loads an external javascript file
	function loadJSFile(file,onload){
		try {
			var x = document.createElement("script");
			
			x.onload = onload;
			x.called = false;
			x.onreadystatechange = function(){
				if (!x.called && (this.readyState == "complete" || this.readyState == "loaded")){
					this.called = false;
					this.onload();
				}
			};
				
			x.type = "text/javascript";
			x.src = file;
			document.getElementsByTagName("head")[0].appendChild(x);
		} catch(e) {
			alert(e);
		}
	}
	
	// called when FCKeditor is done starting..
	function FCKeditor_OnComplete( editorInstance ){
		editorInstance.LinkedField.form.onsubmit = saveBegin;
		sw_setCode('fck');
		hideMessage();
		document.getElementById("div_fckedit").style.display = "block";
	}
	
	// revert editor contents
	function revert_text(revert_changes_since_open){
		if (revert_changes_since_open){
			if (window.confirm("Revert to the original contents?")){
				setCode(initialCode);
			}
		}else{
			if (window.confirm("Revert to last saved contents?")){
				setCode(lastSavedCode);
			}
		}
	}
	
	function ed_load(){
		document.getElementById('editor_syntax').options[<?php echo $cp_idx; ?>].selected = true;
	}

	attachOnload(window,ed_load);
	
	// keystroke stuff
	if (window.addEventListener)
		window.addEventListener('keypress', saveHandler, true);
	else
		document.attachEvent('onkeydown', saveHandler);

	window.onbeforeunload = page_unload;
	function page_unload(){
		if (getCode() != lastSavedCode){
			var msg = "If you did not save your data, then it will be lost!"
			return msg;
		}
	}		
	
	function saveHandler(e){
		
		if (!e) var e = window.event;
		
		// catch CTRL-S
		if((e.charCode !== undefined ? e.charCode == 115 : e.keyCode == 83) && e.ctrlKey) {
			
			saveBegin();
			if (e.preventDefault)
				e.preventDefault();
			else
				e.returnValue = false;
		}
		
	}
	
	var saving = false;
	
	// called on save
	function saveBegin(){
		if (saving)
			return false;
	
		saving = true;
	
		var edArea = document.getElementById('adm_edarea_msg');
		edArea.innerHTML = 'Saving...';
		edArea.style.display = 'block';
		lastSavedCode = getCode();
		
		var oElement = document.forms['editor'];
<?php if ($type == 'edtemplate'){ ?>
		// set all the appropriate variables
		ajax.setVar('editor_content',lastSavedCode);
		ajax.setVar('editor_title',oElement.editor_title.value);
		
		ajax.requestFile = "##pageroot##/?mode=edtemplate&template_id=<?php echo htmlentities($unique_id);?>&ed_action=change&ajax=true";

<?php }else{ ?>
		// set all the appropriate variables
		ajax.setVar('editor_content',lastSavedCode);
		ajax.setVar('editor_template',oElement.editor_template[oElement.editor_template.selectedIndex].value);
		ajax.setVar('editor_banner',oElement.editor_banner[oElement.editor_banner.selectedIndex].value);
		ajax.setVar('editor_menu',oElement.editor_menu[oElement.editor_menu.selectedIndex].value);
		ajax.setVar('editor_title',oElement.editor_title.value);
		ajax.setVar('editor_execute',oElement.editor_execute[oElement.editor_execute.selectedIndex].value);
		ajax.setVar('editor_url','<?php echo htmlentities($unique_id);?>');
		
		ajax.requestFile = "##pageroot##/?mode=edurl&page_url=<?php echo htmlentities($unique_id);?>&ed_action=change&ajax=true";
		
<?php } ?>
		ajax.method = 'POST';
		ajax.element = 'adm_edarea_msg';
		ajax.onCompletion = saveComplete;
		ajax.runAJAX();
		
		return false;
	}
	
	function saveComplete(){
		saving = false;
		execJS(document.getElementById('adm_edarea_msg'));	// execute any script elements
	}
	
	function setMessage(msg){
		var s = document.getElementById('adm_edarea_msg');
		s.innerHTML = msg;
		s.style.display = 'block';
	}

	function hideMessage(){
		document.getElementById('adm_edarea_msg').style.display = 'none';
	}

	function formSubmit(){
		document.editor.target = '_self';
		document.editor.preview.value = 'no';
		document.editor.editor_content.value = getCode();
		window.onbeforeunload = null;
		document.editor.submit();
	}
	
	function formPreview(){
		document.editor.target = '_blank';
		document.editor.preview.value = 'yes';
		document.editor.editor_content.value = getCode();
		document.editor.submit();
	}

	function previewWindow(type,groupid){
		window.open("##pageroot##/?mode=preview&type=" + type + "&group=" + groupid ,"AdminPreview","");
	}
	
//--></script>

<?php if ($type == 'edurl'){ ?>
<form name="editor" action="##pageroot##/?mode=edurl&amp;page_url=<?php echo htmlentities($unique_id);?>&amp;ed_action=change" method="post">
<table>
	<tr><td>URL</td><td><input type="text" name="editor_url" size="40" value="<?php echo htmlentities($unique_id); ?>"/></td>
	
	<td>Template</td><td><?php 

	$query = "SELECT template_id,template_name FROM $cfg[t_templates] ORDER BY template_name ASC";
	generate_select_option('editor_template',$templateID,$query,true); 

	?></td></tr>
	<tr><td>Page Title</td><td><input type="text" name="editor_title" size="40" value="<?php echo htmlentities($title,ENT_NOQUOTES);?>"/></td>
	
	<td>Banner Group</td><td><?php

	$query = "SELECT banner_id,name FROM $cfg[t_banners] ORDER BY name ASC";
	generate_select_option('editor_banner',$bannerID,$query,true);

?>&nbsp;<a href="" onclick="previewWindow('banner',document.editor.editor_banner.value); return false">Show</a></td></tr>

	<tr><td>Execute PHP Code</td><td><select name="editor_execute"><option value="yes" <?php if ($execute) echo "selected";?>>Yes</option><option value="no" <?php if (!$execute) echo "selected";?>>No</option></select></td>
	
	<td>Menu ID</td><td><?php

	$query = "SELECT menu_id,name FROM $cfg[t_menus] ORDER BY name ASC";
	generate_select_option('editor_menu',$menuID,$query,true);
	
?>
	</td></tr>
</table>

<?php }else{ ?>

<form name="editor" action="##pageroot##/?mode=edtemplate&amp;template_id=<?php echo htmlentities($unique_id);?>&amp;ed_action=change" method="post" onsubmit="formSubmit()">
	Template Name <input type="text" name="editor_title" size="50" value="<?php echo htmlentities($title); ?>"/>


<?php } ?>
<input type="hidden" value="" name="editor_content"/>
<input type="hidden" value="no" name="preview" />
</form>

<p><em>Special strings:</em><br/>
&#35;&#35;pageroot&#35;&#35; - Root directory of current page (No trailing /)<br/>
&#35;&#35;rootdir&#35;&#35; - Root directory of website (No trailing /)<br/>
&#35;&#35;title&#35;&#35; - Title of page<br/>
&#35;&#35;menu&#35;&#35; - Page menu<br/>
&#35;&#35;banner&#35;&#35; - Page banner<?php 
	if ($type == 'edtemplate')
		echo '<br/>&#35;&#35;content&#35;&#35; - Content of page -- MUST be included somewhere!!';
?>
</p>
<div id="adm_edarea_msg"></div>
<p>
	<ul id="adm_list">
		<?php 
		if ($type != 'edtemplate'){
			echo '<li><a href="" onclick="switchEditor(\'fck\');return false">FCKEditor (HTML)</a></li>';
		}
		?>
		<li><a href="" onclick="switchEditor('cp');return false;">Codepress (Source)</a></li>
		<li><a href="" onclick="switchEditor('ea');return false;">Editarea (Source)</a></li>
	</ul>
</p>
<div id="adm_edarea_editor">

<?php if ($enable_fck){ ?>
	<div id="div_fckedit" style="display: none">
		<p><strong>Warning</strong>: If you use FCKEditor to modify non-HTML content, then your content may become corrupted!</p>
		<form>
		<div id="fck_container"></div>
		</form>
		<br/>Revert: <a href="" onclick="revert_text(true);return false;">Changes Since Open</a> | <a href="" onclick="revert_text(false);return false;">Changes since last save</a>
	</div>
<?php }?>

	<div id="div_codepress" style="display: none">
		<p>Highlighting type:
		<select id="editor_syntax" onchange="switchLanguage()">
			<option value="css">CSS</option>
			<option value="html">HTML</option>
			<option value="javascript">Javascript</option>
			<option value="php">PHP</option>
			<option value="text">Plain Text</option>
		</select> Toggle: <a href="" onclick="cp.toggleAutoComplete();return false;">Autocomplete</a> | <a href="" onclick="cp.toggleEditor();return false;">Highlighting</a></p>
		<div id="cp_container"></div>
		<br/>Revert: <a href="" onclick="revert_text(true);return false;">Changes Since Open</a> | <a href="" onclick="revert_text(false);return false;">Changes since last save</a>
	</div>
	
	<div id="div_ea" style="display: none">
		<div id="ea_container"><textarea id="ea"></textarea></div>
		<br/>Revert: <a href="" onclick="revert_text(true);return false;">Changes Since Open</a> | <a href="" onclick="revert_text(false);return false;">Changes since last save</a>
	</div>
	
</div>
<p><em>Warning: any changes made here, and submitted, will immediately show on the website!</em></p>

<input type="button" value="Change content" onclick="formSubmit()">
<input type="button" value="Preview" onclick="formPreview()">
<?php	

}

?>