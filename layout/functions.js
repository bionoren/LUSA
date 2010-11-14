function selectChange(c,a,b){if(a!=0){new Ajax.Updater("classChoice"+b,"postback.php",{parameters:{mode:"createClassDropdown",data:$("form").serialize(),submit:true,department:a,selection:"----"}});
$("choice"+b).focus()
}}items=new Hash();
function selectClass(e,b,c,a){if(b!=null){items.set(e,c)
}var d="";
items.each(function(f){d+="&cf[]="+b
});
$("scheduleImg").src=url;
setLocation($("form").serialize()+d)
}function selectCampusTrigger(a){updateAll(false)
}function departmentSelected(a,b){if($("choice"+a).empty()){new Ajax.Updater("classDropdowns","postback.php",{parameters:{mode:"createClassDropdown",data:$("form").serialize(),submit:true},insertion:"bottom"})
}if($("classDD"+a).value=="0"){blanks=false;
$$(".classDD").each(function(c){if(blanks&&c.firstChild.value=="0"){c.remove()
}else{if(!c.firstChild||c.firstChild.value=="0"){blanks=true
}}});
if($("choice"+a)){$("choice"+a).innerHTML=""
}}else{selectChange(b,$("classDD"+a).value,a)
}courseSelected()
}function courseSelected(a){new Ajax.Updater("schedule","postback.php",{parameters:{mode:"updateSchedule",data:$("form").serialize(),submit:true}});
setLocation($("form").serialize());
updateHours()
}function updateHours(){hours=0;
$$(".choiceDD").each(function(a){hours+=Number(a.value.substr(-1))
});
$("schedHours").innerHTML=hours
}function updateAll(a,b){if(!b){b=$("form").serialize()
}new Ajax.Updater("body","postback.php",{parameters:{mode:"updateAll",data:b,submit:true}});
if(!a){setLocation($("form").serialize())
}}function updateAllFromCookie(){updateAll(true,getCookie(getCookieName()))
}function getCookie(a){if(document.cookie.length>0){c_start=document.cookie.indexOf(a+"=");
if(c_start!=-1){c_start=c_start+a.length+1;
c_end=document.cookie.indexOf(";",c_start);
if(c_end==-1){c_end=document.cookie.length
}return unescape(document.cookie.substring(c_start,c_end))
}}return""
}function updateAllProf(){updateAll(true)
}function setLocation(a){a+="&submit=true";
document.location.hash=a;
document.cookie=getCookieName()+"="+a
}function getCookieName(){campus="MAIN";
if($("campusSelect")){campus=$("campusSelect").value
}return $("semesterSelect").value+Number($("typeTraditional").checked)+campus
};