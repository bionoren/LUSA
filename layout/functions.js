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
Event.observe(this.course,"change",this.courseSelected.bind(this));
Dropdown.instances.push(this)
},departmentSelected:function(){if(this.dept.value){if(!this.course.firstChild){d=new Dropdown();
$("classDropdowns").appendChild(d.container);
this.container.appendChild(this.course)
}this.populateCourse()
}else{this.course.value=0;
this.courseSelected();
Element.remove(this.container)
}},courseSelected:function(){hours=this.course.value.substr(-1);
$("schedHours").innerHTML=parseInt($("schedHours").innerHTML)+parseInt(hours)-this.hours;
this.hours=hours;
this.courseMgr.update();
Dropdown.instances.each(function(a){course=a.course.value
})
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
Dropdown.instances=new Array();
Dropdown.classes=new Hash();
Dropdown.updatePreview=function(){if($("scheduleImg")){tmp=new Array();
url="print.php?sem=2011SP&trad=1&classes=";
Dropdown.classes.each(function(a){tmp.push(a[1])
});
url+=tmp.join("~");
$("scheduleImg").src=url
}};
var Course=Class.create({initialize:function(a){this.course=a;
this.value=this.course.value;
this.update()
},update:function(){if(this.course.value){new Ajax.Updater("classes","postback.php",{parameters:{mode:"addClass",data:$("form").serialize(),submit:true,id:this.course.value},insertion:Insertion.Bottom,onSuccess:function(a){if(this.value){$$("."+this.value).each(function(b){Element.remove(b)
}.bind(this))
}}.bind(this),onComplete:function(a){rows=$$("."+this.course.value);
if(rows.length==1){Dropdown.classes.set(this.course.value,rows[0].id);
Dropdown.updatePreview()
}this.value=this.course.value
}.bind(this)})
}if(this.course.value=="0"){Dropdown.classes.unset(this.value);
Dropdown.updatePreview()
}}});
Course.toggle=function(a){sections=$$("."+a);
sections.shift();
tmp=sections.first();
if(tmp.style.visibility=="visible"){state="collapse";
$(a).innerHTML="+"
}else{state="visible";
$(a).innerHTML="-"
}sections.each(function(b){if(b.style.cursor!="pointer"){b.style.visibility=state
}})
};
Course.selected=function(a,b){Dropdown.classes.set(a,b);
Dropdown.updatePreview()
};
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