var lusa={student:null,trad:null,semester:null,campus:null};
lusa.init=function(){lusa.updateOptions();
lusa.loadClasses();
updateFunction=function(a){return function(b){lusa[a]=this.value;
this.updateLocation()
}.bind(this)
};
Event.observe($("typeStudent"),"click",updateFunction("student"));
Event.observe($("typeProf"),"click",updateFunction("student"));
Event.observe($("typeTraditional"),"click",updateFunction("trad"));
Event.observe($("typeNonTraditional"),"click",updateFunction("trad"));
if($("campusSelect")){Event.observe($("campusSelect"),"change",updateFunction("campus"))
}Event.observe($("semesterSelect"),"change",updateFunction("semester"))
};
lusa.updateLocation=function(){params=lusa.getOptions();
params.choice=[];
Dropdown.instances.each(function(a){if(a.values&&a.values.length>0){params.choice.push(a.values)
}});
params.choice=params.choice.uniq();
document.location.hash=Object.toJSON(params);
date=new Date();
date.setTime(date.getTime()+(365*24*60*60*1000));
if(this.student=="student"){document.cookie=this.getCookieName()+"="+document.location.hash+"; expires="+date.toUTCString()
}};
lusa.updatePreview=function(){if($("scheduleImg")){tmp=new Array();
url="print.php?sem="+lusa.semester+"&trad="+lusa.trad+"&classes=";
Dropdown.classes.each(function(a){tmp.push(a[1])
});
url+=tmp.join("~");
$("scheduleImg").src=url
}};
lusa.updateOptions=function(){if($("typeStudent").checked){lusa.student=$("typeStudent").value
}else{lusa.student=$("typeProf").value
}if($("typeTraditional").checked){lusa.trad=$("typeTraditional").value
}else{lusa.trad=$("typeNonTraditional").value
}if($("campusSelect")){lusa.campus=$("campusSelect").value
}if(!lusa.campus){lusa.campus="MAIN"
}lusa.semester=$("semesterSelect").value
};
lusa.getOptions=function(){url=window.location.hash;
if(url){params=url.substring(1).evalJSON()
}else{params={}
}params.role=lusa.student;
params.trad=lusa.trad;
params.semester=lusa.semester;
params.campus=lusa.campus;
return params
};
lusa.loadClasses=function(){if($("classes")){cookie=lusa.getCookie(lusa.getCookieName());
if(cookie){params=cookie.substring(1).evalJSON();
params.choice.each(function(a){new Dropdown(a)
})
}}if(lusa.student=="student"){new Dropdown()
}};
lusa.getCookie=function(a){if(document.cookie.length>0){c_start=document.cookie.indexOf(a+"=");
if(c_start!=-1){c_start=c_start+a.length+1;
c_end=document.cookie.indexOf(";",c_start);
if(c_end==-1){c_end=document.cookie.length
}return unescape(document.cookie.substring(c_start,c_end))
}}return null
};
lusa.getCookieName=function(){return lusa.semester+lusa.trad+lusa.campus
};
var Dropdown=Class.create({initialize:function(a){this.container=new Element("div");
this.dept=new Element("select");
this.course=new Element("select");
this.hours=0;
this.courseMgr=null;
this.values=[];
this.course.setStyle({width:"350px"});
$("classDropdowns").appendChild(this.container);
this.container.appendChild(this.dept);
this.courseMgr=new Course(this.course);
this.dept.appendChild(new Element("option",{value:""}).update("----"));
if(!lusa.deptCache){new Ajax.Request("postback.php",{method:"post",parameters:{mode:"getDepartmentData"},onSuccess:function(b){data=b.responseText.evalJSON();
lusa.deptCache=data;
Object.values(data).each(function(c){this.dept.appendChild(new Element("option",{value:c}).update(c))
}.bind(this));
Event.observe(this.dept,"change",this.departmentSelected.bind(this))
}.bind(this),onComplete:function(){if(a){this.dept.value=a[0].substr(0,4);
this.departmentSelected(a)
}}.bind(this)})
}else{data=lusa.deptCache;
Object.values(data).each(function(b){this.dept.appendChild(new Element("option",{value:b}).update(b))
}.bind(this));
Event.observe(this.dept,"change",this.departmentSelected.bind(this));
if(a){this.dept.value=a.substr(0,4);
this.departmentSelected(a)
}}Dropdown.instances.push(this)
},departmentSelected:function(a){if(this.dept.value){if(!this.course.firstChild){if(!Object.isArray(a)){d=new Dropdown();
$("classDropdowns").appendChild(d.container)
}this.container.appendChild(this.course)
}this.course.value=0;
lusa.updateLocation();
this.populateCourse(a)
}else{this.course.value=0;
this.courseSelected();
Element.remove(this.container)
}},courseSelected:function(a){this.values=a.values;
hours=this.course.value.substr(-1);
$("schedHours").update(parseInt($("schedHours").innerHTML)+parseInt(hours)-this.hours);
this.hours=hours;
lusa.updateLocation();
this.courseMgr.reload()
},populateCourse:function(a){if(!Object.isArray(a)){a=[]
}url=window.location.hash;
params=url.substring(1).toQueryParams();
new Ajax.Request("postback.php",{method:"post",parameters:{mode:"getCourseData",dept:this.dept.value},onSuccess:function(b){data=b.responseText.evalJSON();
if(this.course.children){$A(this.course.children).each(function(c){Element.remove(c)
})
}this.course.appendChild(new Element("option",{value:"0"}).update("----"));
Object.keys(data).each(function(c){option=new Element("option",{value:c}).update(data[c]["class"]);
if(data[c].error){option.setAttribute("disabled","disabled")
}this.course.appendChild(option)
}.bind(this));
this.course.activate();
Event.observe(this.course,"change",this.courseSelected.bind(this));
goodSelect=new SelectMultiple(this.course,{defaultText:"Select a class",defaultOption:"0",hoverDisabledCallback:function(c){$("scheduleImg").src+="&overlayClasses="+data[c.element().getAttribute("data-value")].error
}.bind(this),defaultValue:a});
a.each(function(c){goodSelect.update(c)
})
}.bind(this)})
}});
Dropdown.instances=new Array();
Dropdown.classes=new Hash();
var Course=Class.create({initialize:function(a){this.course=a;
this.value=this.course.value;
this.reload()
},reload:function(){if(this.course.value){if(this.value&&this.course.value!=this.value){Dropdown.classes.unset(this.value);
lusa.updatePreview()
}new Ajax.Updater("classes","postback.php",{parameters:{mode:"updateClasses"},onSuccess:function(a){if(this.value){$$("."+this.value).each(function(b){Element.remove(b)
}.bind(this))
}}.bind(this),onComplete:function(a){this.value=this.course.value;
Dropdown.instances.each(function(b){if(b.courseMgr){b.courseMgr.update()
}});
lusa.updatePreview()
}.bind(this)})
}},update:function(){if(this.course.value&&this.course.value!="0"){rows=$$("."+this.course.value);
if(rows.length>0&&(rows.length==1||typeof rows[1].down("input")=="undefined")){Dropdown.classes.set(this.value,rows[0].id)
}}}});
Course.toggle=function(a){sections=$$("."+a);
sections.shift();
tmp=sections.first();
if(tmp.getStyle("display")!="none"){state="none";
$(a).update("+")
}else{state="table-row";
$(a).update("-")
}sections.each(function(b){if(b.style.cursor!="pointer"){b.setStyle({display:state})
}})
};
Course.selected=function(a,b){Dropdown.classes.set(a,b);
lusa.updatePreview()
};
function profSelected(a){new Ajax.Updater("schedule","postback.php",{parameters:{mode:"updateClasses",data:Object.toQueryString(lusa.getOptions())+"&prof="+a.value}})
};