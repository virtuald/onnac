/* 
	$Id$
	
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

// taken from http://muffinresearch.co.uk/archives/2006/04/29/getelementsbyclassname-deluxe-edition/
function getElementsByClassName(strClass, strTag, objContElm) {
  strTag = strTag || "*";
  objContElm = objContElm || document;
  var objColl = objContElm.getElementsByTagName(strTag);
  if (!objColl.length &&  strTag == "*" &&  objContElm.all) objColl = objContElm.all;
  var arr = new Array();
  var delim = strClass.indexOf('|') != -1  ? '|' : ' ';
  var arrClass = strClass.split(delim);
  for (var i = 0, j = objColl.length; i < j; i++) {
    var arrObjClass = objColl[i].className.split(' ');
    if (delim == ' ' && arrClass.length > arrObjClass.length) continue;
    var c = 0;
    comparisonLoop:
    for (var k = 0, l = arrObjClass.length; k < l; k++) {
      for (var m = 0, n = arrClass.length; m < n; m++) {
        if (arrClass[m] == arrObjClass[k]) c++;
        if (( delim == '|' && c == 1) || (delim == ' ' && c == arrClass.length)) {
          arr.push(objColl[i]);
          break comparisonLoop;
        }
      }
    }
  }
  return arr;
}

// To cover IE 5.0's lack of the push method
Array.prototype.push = function(value) {
  this[this.length] = value;
}

attachOnload(window,applyHighlighting);
