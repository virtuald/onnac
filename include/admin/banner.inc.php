<?php
/*
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

	Administration tool -- manage banners
	
*/

function manage_banners(){

	global $cfg;
	
	include('./include/admin/adm_item.php');
	
	$manager = new adm_item();
	
	$manager->type =				'banner';
	$manager->type_plural =			'banners';
	$manager->url = 				'##pageroot##/?mode=banner';
	
	// initialize fields
	$manager->sql_item_table = 		$cfg['t_banner_items'];		
	$manager->sql_item_id = 		'item_id';			
	$manager->sql_item_data = 		array('alt','src');			
	$manager->sql_item_desc = 		array('Display Text','URL');

	$manager->sql_item_unique_keys = array(0,1);
	
	$manager->sql_item_hidden =		array();
	
	$manager->sql_join_table = 		$cfg['t_banner_groups'];		
	$manager->sql_order_field = 	false;
	
	$manager->sql_group_table = 	$cfg['t_banners'];		
	$manager->sql_group_id =		'banner_id';			
	$manager->sql_group_name = 		'name';

	$manager->do_content_update = 	true;
	
	// hooks
	//$manager->item_delete_hook = 	'user_delete';
	//$manager->remove_hook = 		'user_remove';
	//$manager->edit_item_hook = 	'user_edititem';
	
	$manager->ShowItems();
	
}

?>
