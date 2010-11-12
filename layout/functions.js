function selectChange(c,a,b){if(a!=0){new Ajax.Updater("classChoice"+b,"postback.php",{parameters:{mode:"createClassDropdown",data:$("form").serialize(),submit:true,department:a,selection:"----"}});
$("choice"+b).focus()
}}items=new Hash();
function selectClass(a,d,b){if(a!=null){items.set(a,d)
}var c="print.php?"+b;
items.each(function(e){c+="~"+e.value
});
$("scheduleImg").src=c;
$("printer").href=c
}function selectCampusTrigger(a){updateAll()
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
if(!a){alert("setting location with "+a);
setLocation($("form").serialize())
}}function updateAllFromCookie(){updateAll(true,document.cookie)
}function updateAllProf(){updateAll(true)
}function setLocation(a){a+="&submit=true";
document.location.hash=a;
campus="MAIN";
if($("campusSelect")){campus=$("campusSelect").value
}document.cookie=$("semesterSelect").value+Number($("typeTraditional").checked)+campus+"="+a
};