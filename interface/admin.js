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
								this.className='highlighted';
								return false;
							}
						trs[j].onmouseout=function(){
							this.className='';
							return false;
						}
					}
				}
			}
		}
	}
}

attachOnload(window,applyHighlighting);
