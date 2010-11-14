function createJSToggle(a){sections=$$("."+a);
tmp=sections.first();
if(tmp.style.visibility=="visible"){state="collapse";
$(a).innerHTML="+"
}else{state="visible";
$(a).innerHTML="-"
}sections.each(function(b){b.style.visibility=state
})
}function selectChange(a,b){if(a!=0){new Ajax.Updater("classChoice"+b,"postback.php",{parameters:{mode:"createClassDropdown",data:$("form").serialize(),submit:true,department:a,selection:"----"}});
$("choice"+b).focus()
}}items=new Hash();
QS="";
function setClassInfo(c,a,b){if(c!=null){items.set(c,[b,a])
}}function selectClass(e,b,c){setClassInfo(e,b,c);
var a="print.php?"+QS;
var d="";
items.each(function(f){a+="~"+f.value[0];
d+="&cf[]="+f.value[1]
});
$("scheduleImg").src=a;
setLocation($("form").serialize()+d)
}function selectCampusTrigger(a){updateAll(false)
}function departmentSelected(a){if($("choice"+a).empty()){new Ajax.Updater("classDropdowns","postback.php",{parameters:{mode:"createClassDropdown",data:$("form").serialize(),submit:true},insertion:"bottom"})
}if($("classDD"+a).value=="0"){blanks=false;
$$(".classDD").each(function(b){if(blanks&&b.firstChild.value=="0"){b.remove()
}else{if(!b.firstChild||b.firstChild.value=="0"){blanks=true
}}});
if($("choice"+a)){$("choice"+a).innerHTML=""
}}else{selectChange($("classDD"+a).value,a)
}courseSelected()
}function courseSelected(){new Ajax.Updater("schedule","postback.php",{parameters:{mode:"updateSchedule",data:$("form").serialize(),submit:true}});
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
}function setQS(a){QS=a.replace(/&amp;/g,"&")
}function profSelected(a){if(!a.empty()){new Ajax.Updater("schedule","postback.php",{parameters:{mode:"updateSchedule",data:$("form").serialize(),submit:true}})
}};