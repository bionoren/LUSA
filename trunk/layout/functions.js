function selectChange(d,b){if($(b).children!=null){$(b).innerHTML=""}var a=document.createElement("select");a.setAttribute("name","choice[]");var c=document.createElement("option");theText=document.createTextNode("----");c.appendChild(theText);a.appendChild(c);if(arrItems.get(d.value)==null){$(b).innerHTML="";return}arrItems.get($(d).value).each(function(f){c=document.createElement("option");c.setAttribute("value",f.key);if(!f.value[1]){c.setAttribute("style","color:rgb(177, 177, 177);")}var e=document.createTextNode(f.value[0]);c.appendChild(e);a.appendChild(c)});$(b).appendChild(a)}var items=new Hash();function selectClass(a,d,b){if(a!=null){items.set(a,d)}var c="print.php?"+b;items.each(function(e){c+="~"+e.value});$("schedule").src=c;$("printer").href=c}function selectCampusTrigger(a){var b=window.location.protocol+"//"+window.location.host+window.location.pathname;window.location=b+"?type="+($("typeTraditional").checked==true?"trad":"non")+"&campus="+escape(this.value)+"&submit=Change&semester="+escape($("semesterSelect").value)};