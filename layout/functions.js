function selectChange(c,a,b){if(a!=0){new Ajax.Updater("classChoice"+b,"postback.php",{parameters:{mode:"createClassDropdown",semester:c,department:a,selection:"----"}});
$("choice"+b).focus()
}}items=new Hash();
function selectClass(a,d,b){if(a!=null){items.set(a,d)
}var c="print.php?"+b;
items.each(function(e){c+="~"+e.value
});
$("scheduleImg").src=c;
$("printer").href=c
}function selectCampusTrigger(a){updateAll()
}function departmentSelected(a,b){if($("choice"+a).empty()){new Ajax.Updater("classDropdowns","postback.php",{parameters:{mode:"createClassDropdown",data:$("form").serialize()},insertion:"bottom"})
}if($("classDD"+a).value=="0"){blanks=false;
$$(".classDD").each(function(c){if(blanks&&c.firstChild.value=="0"){c.remove()
}if(c.firstChild.value=="0"){blanks=true
}});
if($("choice"+a)){$("choice"+a).innerHTML=""
}}else{selectChange(b,$("classDD"+a).value,a)
}courseSelected()
}function courseSelected(a){hours=0;
$$(".choiceDD").each(function(b){hours+=Number(b.value.substr(-1))
});
$("schedHours").innerHTML=hours;
new Ajax.Updater("schedule","postback.php",{parameters:{mode:"updateSchedule",data:$("form").serialize(),submit:true}});
document.location.hash=$("form").serialize()
}function updateAll(){new Ajax.Updater("body","postback.php",{parameters:{mode:"updateAll",data:$("form").serialize()}});
document.location.hash=$("form").serialize()
};