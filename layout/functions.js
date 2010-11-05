function selectChange(control,controlToPopulate){if($(controlToPopulate).children!=null){$(controlToPopulate).innerHTML=""
}var select=document.createElement("select");
select.setAttribute("name","choice[]");
var myEle=document.createElement("option");
theText=document.createTextNode("----");
myEle.appendChild(theText);
select.appendChild(myEle);
if(arrItems.get(control.value)==null){$(controlToPopulate).innerHTML="";
return
}arrItems.get($(control).value).each(function(pair){myEle=document.createElement("option");
myEle.setAttribute("value",pair.key);
if(!pair.value[1]){myEle.setAttribute("style","color:rgb(177, 177, 177);")
}var txt=document.createTextNode(pair.value[0]);
myEle.appendChild(txt);
select.appendChild(myEle)
});
$(controlToPopulate).appendChild(select);
select.focus()
}var items=new Hash();
function selectClass(course,str,QS){if(course!=null){items.set(course,str)
}var url="print.php?"+QS;
items.each(function(pair){url+="~"+pair.value
});
$("schedule").src=url;
$("printer").href=url
}function selectCampusTrigger(event){var path=window.location.protocol+"//"+window.location.host+window.location.pathname;
window.location=path+"?type="+($("typeTraditional").checked==true?"trad":"non")+"&campus="+escape(this.value)+"&submit=Change&semester="+escape($("semesterSelect").value)
}function classSelected(ele,uid,semester){if($("choice"+uid).empty()){new Ajax.Updater("classDropdowns","createClassDropdown.php",{parameters:{semester:semester},insertion:"bottom"})
}selectChange(ele,"choice"+uid)
};