var lusa={student:null,trad:null,semester:null,campus:null};
lusa.init=function(){lusa.updateOptions();
lusa.loadClasses();
Event.observe($("typeStudent"),"click",function(a){lusa.student=this.value
});
Event.observe($("typeTraditional"),"click",function(a){lusa.trad=this.value
});
if($("campusSelect")){Event.observe($("campusSelect"),"change",function(a){lusa.campus=this.value
})
}Event.observe($("semesterSelect"),"change",function(a){lusa.semester=this.value
})
};
lusa.updateLocation=function(){str=lusa.getOptions();
Dropdown.instances.each(function(a){if(a.course.value&&a.course.value!="0"){str+="&choice[] = "+a.course.value
}});
document.location.hash=str;
document.cookie=this.getCookieName()+"="+str
};
lusa.updateCampus=function(a){this.campus=a
};
lusa.updateOptions=function(){lusa.student=$("typeStudent").value;
lusa.trad=$("typeTraditional").value;
if($("campusSelect")){lusa.campus=$("campusSelect").value
}if(!lusa.campus){lusa.campus="MAIN"
}lusa.semester=$("semesterSelect").value
};
lusa.getOptions=function(){return"role="+lusa.student+"&type="+lusa.trad+"&semester="+lusa.semester
};
lusa.loadClasses=function(){d=new Dropdown();
$("classDropdowns").appendChild(d.container);
if($("classes")){$A($("classes").children).each(function(a){if(!a.id){return
}cs=new Course(a,a.id);
lusa.classes.push(cs)
})
}};
lusa.getCookie=function(a){if(document.cookie.length>0){c_start=document.cookie.indexOf(a+"=");
if(c_start!=-1){c_start=c_start+a.length+1;
c_end=document.cookie.indexOf(";",c_start);
if(c_end==-1){c_end=document.cookie.length
}return unescape(document.cookie.substring(c_start,c_end))
}}return""
};
lusa.getCookieName=function(){campus="MAIN";
if($("campusSelect")){campus=$("campusSelect").value
}return $("semesterSelect").value+Number($("typeTraditional").checked)+campus
};
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
new Ajax.Request("postback.php",{method:"post",parameters:{mode:"getDepartmentData",data:lusa.getOptions(),submit:true},onSuccess:function(a){data=a.responseText.evalJSON();
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
});
lusa.updateLocation()
},populateCourse:function(){new Ajax.Request("postback.php",{method:"post",parameters:{mode:"getCourseData",data:lusa.getOptions(),submit:true,dept:this.dept.value},onSuccess:function(a){data=a.responseText.evalJSON();
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
},update:function(){if(this.course.value){if(this.value&&this.course.value!=this.value){Dropdown.classes.unset(this.value);
Dropdown.updatePreview()
}new Ajax.Updater("classes","postback.php",{parameters:{mode:"addClass",data:lusa.getOptions(),submit:true,id:this.course.value},insertion:Insertion.Bottom,onSuccess:function(a){if(this.value){$$("."+this.value).each(function(b){Element.remove(b)
}.bind(this))
}}.bind(this),onComplete:function(a){rows=$$("."+this.course.value);
if(rows.length==1){Dropdown.classes.set(this.course.value,rows[0].id);
Dropdown.updatePreview()
}this.value=this.course.value
}.bind(this)})
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