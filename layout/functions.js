var LUSA=Class.create({initialize:function(){this.student=null;
this.trad=null;
this.semester=null;
this.campus=null;
this.classes=new Array();
this.updateOptions();
this.loadClasses()
},updateLocation:function(){str+="&submit=true";
document.location.hash=str;
document.cookie=getCookieName()+"="+str
},updateCampus:function(a){this.campus=a
},updateHours:function(){hours=0;
this.dropdowns.each(function(a){hours+=Number(a.course.value.substr(-1))
});
$("schedHours").innerHTML=hours
},updateSchedule:function(){var a="print.php?sem="+this.semester+"&trad="+this.trad+"&classes=";
this.dropdowns.each(function(b){if(b.course.value){a+="~"+b.course.value
}});
if($("scheduleImg")){$("scheduleImg").src=a
}this.updateLocation()
},updateOptions:function(){this.student=$("typeStudent").value;
this.trad=$("typeTraditional").value;
if($("campusSelect")){this.campus=$("campusSelect").value
}if(!this.campus){this.campus="MAIN"
}this.semester=$("semesterSelect").value
},loadClasses:function(){d=new Dropdown();
$("classDropdowns").appendChild(d.container);
if($("classes")){$A($("classes").children).each(function(a){if(!a.id){return
}cs=new Course(a,a.id);
this.classes.push(cs)
}.bind(this))
}}});
var Dropdown=Class.create({initialize:function(){this.container=document.createElement("div");
this.dept=document.createElement("select");
this.course=document.createElement("select");
this.hours=0;
this.courseMgr=null;
this.container.appendChild(this.dept);
this.courseMgr=new Course(this.course);
option=document.createElement("option");
option.setAttribute("value","");
option.appendChild(document.createTextNode("----"));
this.dept.appendChild(option);
new Ajax.Request("postback.php",{method:"post",parameters:{mode:"getDepartmentData",data:$("form").serialize(),submit:true},onSuccess:function(a){data=a.responseText.evalJSON();
Object.values(data).each(function(b){option=document.createElement("option");
option.setAttribute("value",b);
option.appendChild(document.createTextNode(b));
this.dept.appendChild(option)
}.bind(this));
Event.observe(this.dept,"change",this.departmentSelected.bind(this))
}.bind(this)});
Event.observe(this.course,"change",this.courseSelected.bind(this))
},departmentSelected:function(){if(this.dept.value){if(!this.course.firstChild){d=new Dropdown();
$("classDropdowns").appendChild(d.container);
this.container.appendChild(this.course)
}this.populateCourse()
}else{Form.Element.setValue(this.course,0);
this.courseSelected();
Element.remove(this.container)
}},courseSelected:function(){hours=this.course.value.substr(-1);
$("schedHours").innerHTML=parseInt($("schedHours").innerHTML)+parseInt(hours)-this.hours;
this.hours=hours
},populateCourse:function(){new Ajax.Request("postback.php",{method:"post",parameters:{mode:"getCourseData",data:$("form").serialize(),submit:true,dept:this.dept.value},onSuccess:function(a){data=a.responseText.evalJSON();
if(this.course.children){$A(this.course.children).each(function(b){Element.remove(b)
})
}option=document.createElement("option");
option.setAttribute("value",0);
option.appendChild(document.createTextNode("----"));
this.course.appendChild(option);
Object.keys(data).each(function(b){option=document.createElement("option");
option.setAttribute("value",b);
if(data[b]["error"]){option.setAttribute("style","color:rgb(177, 177, 177);")
}option.appendChild(document.createTextNode(data[b]["class"]));
this.course.appendChild(option)
}.bind(this));
this.course.activate()
}.bind(this)})
}});
var Course=Class.create({initialize:function(a){this.course=a
},toggle:function(){sections=$$("."+this.id);
tmp=sections.first();
if(tmp.style.visibility=="visible"){state="collapse";
$(this.id).innerHTML="+"
}else{state="visible";
$(this.id).innerHTML="-"
}sections.each(function(a){a.style.visibility=state
})
}});
function getCookie(a){if(document.cookie.length>0){c_start=document.cookie.indexOf(a+"=");
if(c_start!=-1){c_start=c_start+a.length+1;
c_end=document.cookie.indexOf(";",c_start);
if(c_end==-1){c_end=document.cookie.length
}return unescape(document.cookie.substring(c_start,c_end))
}}return""
}function getCookieName(){campus="MAIN";
if($("campusSelect")){campus=$("campusSelect").value
}return $("semesterSelect").value+Number($("typeTraditional").checked)+campus
}function profSelected(a){if(!a.empty()){new Ajax.Updater("schedule","postback.php",{parameters:{mode:"updateSchedule",data:$("form").serialize(),submit:true}})
}};