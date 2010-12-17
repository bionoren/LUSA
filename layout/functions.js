var lusa={student:null,trad:null,semester:null,campus:null};
lusa.init=function(a){lusa.updateOptions();
lusa.loadClasses();
Event.observe($("typeStudent"),"click",function(b){lusa.student=this.value
});
Event.observe($("typeTraditional"),"click",function(b){lusa.trad=this.value
});
if($("campusSelect")){Event.observe($("campusSelect"),"change",function(b){lusa.campus=this.value
})
}Event.observe($("semesterSelect"),"change",function(b){lusa.semester=this.value;
a=lusa.getCookie(lusa.getCookieName());
document.location="index.php?semester="+lusa.semester+"#"+a
})
};
lusa.updateLocation=function(){str=lusa.getOptions();
Dropdown.instances.each(function(a){if(a.course.value&&a.course.value!="0"){str+="&choice[]="+a.course.value
}});
document.location.hash=str;
document.cookie=this.getCookieName()+"="+str
};
lusa.updatePreview=function(){if($("scheduleImg")){tmp=new Array();
url="print.php?sem=2011SP&trad=1&classes=";
Dropdown.classes.each(function(a){tmp.push(a[1])
});
url+=tmp.join("~");
$("scheduleImg").src=url
}};
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
lusa.loadClasses=function(){if($("classes")){temp=new Hash();
$A($("classes").children).each(function(a){if(a.classList.length){$A(a.classList).each(function(b){b.scan(/\w{4}-\d{4}/,function(c){temp.set(c,c)
})
})
}});
temp.each(function(a){d=new Dropdown(a[0])
})
}d=new Dropdown()
};
lusa.getCookie=function(a){if(document.cookie.length>0){c_start=document.cookie.indexOf(a+"=");
if(c_start!=-1){c_start=c_start+a.length+1;
c_end=document.cookie.indexOf(";",c_start);
if(c_end==-1){c_end=document.cookie.length
}return unescape(document.cookie.substring(c_start,c_end))
}}return null
};
lusa.getCookieName=function(){campus="MAIN";
if($("campusSelect")){campus=$("campusSelect").value
}return $("semesterSelect").value+Number($("typeTraditional").checked)+campus
};
var Dropdown=Class.create({initialize:function(a){this.container=document.createElement("div");
this.dept=document.createElement("select");
this.course=document.createElement("select");
this.hours=0;
this.courseMgr=null;
$("classDropdowns").appendChild(this.container);
this.container.appendChild(this.dept);
this.courseMgr=new Course(this.course);
option=document.createElement("option");
option.setAttribute("value","");
option.appendChild(document.createTextNode("----"));
this.dept.appendChild(option);
if(!lusa.deptCache){new Ajax.Request("postback.php",{method:"post",parameters:{mode:"getDepartmentData",data:lusa.getOptions(),submit:true},onSuccess:function(b){data=b.responseText.evalJSON();
lusa.deptCache=data;
Object.values(data).each(function(c){option=document.createElement("option");
option.setAttribute("value",c);
option.appendChild(document.createTextNode(c));
this.dept.appendChild(option)
}.bind(this));
Event.observe(this.dept,"change",this.departmentSelected.bind(this))
}.bind(this),onComplete:function(){if(a){this.dept.value=a.substr(0,4);
this.departmentSelected(a)
}}.bind(this)})
}else{data=lusa.deptCache;
Object.values(data).each(function(b){option=document.createElement("option");
option.setAttribute("value",b);
option.appendChild(document.createTextNode(b));
this.dept.appendChild(option)
}.bind(this));
Event.observe(this.dept,"change",this.departmentSelected.bind(this));
if(a){this.dept.value=a.substr(0,4);
this.departmentSelected(a)
}}Event.observe(this.course,"change",this.courseSelected.bind(this));
Dropdown.instances.push(this)
},departmentSelected:function(a){if(this.dept.value){if(!this.course.firstChild){if(!Object.isString(a)){d=new Dropdown();
$("classDropdowns").appendChild(d.container)
}this.container.appendChild(this.course)
}this.populateCourse(a)
}else{this.course.value=0;
this.courseSelected();
Element.remove(this.container)
}},courseSelected:function(){hours=this.course.value.substr(-1);
$("schedHours").innerHTML=parseInt($("schedHours").innerHTML)+parseInt(hours)-this.hours;
this.hours=hours;
lusa.updateLocation();
this.courseMgr.reload()
},populateCourse:function(a){new Ajax.Request("postback.php",{method:"post",parameters:{mode:"getCourseData",data:lusa.getOptions(),submit:true,dept:this.dept.value},onSuccess:function(b){data=b.responseText.evalJSON();
if(this.course.children){$A(this.course.children).each(function(c){Element.remove(c)
})
}option=document.createElement("option");
option.setAttribute("value",0);
option.appendChild(document.createTextNode("----"));
this.course.appendChild(option);
Object.keys(data).each(function(c){option=document.createElement("option");
option.setAttribute("value",c);
if(data[c].error){option.setAttribute("style","color:rgb(177, 177, 177);")
}option.appendChild(document.createTextNode(data[c]["class"]));
this.course.appendChild(option)
}.bind(this));
this.course.activate()
}.bind(this),onComplete:function(){if(a){this.course.value=a;
this.courseSelected()
}}.bind(this)})
}});
Dropdown.instances=new Array();
Dropdown.classes=new Hash();
var Course=Class.create({initialize:function(a){this.course=a;
this.value=this.course.value;
this.reload()
},reload:function(){if(this.course.value){if(this.value&&this.course.value!=this.value){Dropdown.classes.unset(this.value)
}new Ajax.Updater("classes","postback.php",{parameters:{mode:"updateClasses",data:document.location.hash,submit:true,id:this.course.value},onSuccess:function(a){if(this.value){$$("."+this.value).each(function(b){Element.remove(b)
}.bind(this))
}}.bind(this),onComplete:function(a){this.value=this.course.value;
Dropdown.instances.each(function(b){if(b.courseMgr){b.courseMgr.update()
}});
lusa.updatePreview()
}.bind(this)})
}},update:function(){if(this.course.value){rows=$$("."+this.course.value);
if(rows.length==1){Dropdown.classes.set(this.value,rows[0].id)
}}}});
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
lusa.updatePreview()
};