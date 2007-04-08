/* 
	Common Javascript functions for Onnac administrative interface
*/

function toggle_hidden(item){
	var hidden = document.getElementById(item);
	
	if (!hidden.style.display || hidden.style.display == "none")
		hidden.style.display = "block";
	else
		hidden.style.display = "none";
}

// copied from http://www.quirksmode.org/js/cookies.html
function set_cookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}else 
		var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

// add an onload event
function attachOnload(o,fn){
	if (o.attachEvent){
		o.attachEvent('onload', fn);
	}else if (o.addEventListener){
		o.addEventListener('load', fn, false);
	}else{
		o.onload = fn;
	}
}

// modified from http://alistapart.com/articles/tableruler, by Christian Heilmann
function applyHighlighting(){
	if (document.getElementById && document.createTextNode){
		var tables=document.getElementsByTagName('table');
		for (var i=0;i<tables.length;i++){
			if(tables[i].className=='highlighted'){
				var trs=tables[i].getElementsByTagName('tr');
				for(var j=0;j<trs.length;j++){
					if(trs[j].parentNode.nodeName=='TBODY' && trs[j].parentNode.nodeName!='TFOOT'){
						trs[j].onmouseover=
							function(){
								this.className=this.className + 'highlighted';
								return false;
							}
						trs[j].onmouseout=function(){
							this.className=this.className.replace(/highlighted/,'');
							return false;
						}
					}
				}
			}
		}
	}
}

// taken from http://www.shawnolson.net/scripts/public_smo_scripts.js
function changecss(theClass,element,value) {
//documentation for this script at http://www.shawnolson.net/a/503/
	var cssRules;
	if (document.all) {
		cssRules = 'rules';
	}else if (document.getElementById) {
		cssRules = 'cssRules';
	}
	for (var S = 0; S < document.styleSheets.length; S++){
		for (var R = 0; R < document.styleSheets[S][cssRules].length; R++) {
			if (document.styleSheets[S][cssRules][R].selectorText == theClass) {
				document.styleSheets[S][cssRules][R].style[element] = value;
			}
		}
	}	
}

attachOnload(window,applyHighlighting);
