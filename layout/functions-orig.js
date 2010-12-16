//java -jar layout/yuicompressor-2.4.2.jar --type js -o layout/functions.js --line-break 0 layout/functions-orig.js

var lusa = {
    /** BOOLEAN - True if this is the student view. */
    student: null,
    /** BOOLEAN - True if this is the trad view. */
    trad: null,
    /** STRING - The currently selected semester. */
    semester: null,
    /** STRING - The currently selected campus. */
    campus: null
};

lusa.init = function() {
    lusa.updateOptions();
    lusa.loadClasses();
    Event.observe($('typeStudent'), 'click', function(event) {
        lusa.student = this.value;
    });
/*    Event.observe($('typeProf'), 'click', function(event) {
        lusa.student = this.value;
    });*/
    Event.observe($('typeTraditional'), 'click', function(event) {
        lusa.trad = this.value;
    });
/*    Event.observe($('typeNonTraditional'), 'click', function(event) {
        lusa.trad = this.value;
    });*/
    if($('campusSelect')) {
        Event.observe($('campusSelect'), 'change', function(event) {
            lusa.campus = this.value;
        });
    }
    Event.observe($('semesterSelect'), 'change', function(event) {
        lusa.semester = this.value;
    });
}

/**
 * Updates the location in the URL hash.
 *
 * @return VOID
 */
lusa.updateLocation = function() {
    str = lusa.getOptions();
    Dropdown.instances.each(function(dropdown) {
        if(dropdown.course.value && dropdown.course.value != "0") {
            str += "&choice[] = "+dropdown.course.value;
        }
    });
    document.location.hash = str;
    document.cookie = this.getCookieName()+"="+str;
};

lusa.updateCampus = function(campus) {
    this.campus = campus;
};

lusa.updateOptions = function() {
    lusa.student = $('typeStudent').value;
    lusa.trad = $('typeTraditional').value;
    if($('campusSelect')) {
        lusa.campus = $('campusSelect').value;
    }
    if(!lusa.campus) {
        lusa.campus = "MAIN";
    }
    lusa.semester = $('semesterSelect').value;
};

lusa.getOptions = function() {
    return "role="+lusa.student+"&type="+lusa.trad+"&semester="+lusa.semester;
}

lusa.loadClasses = function() {
    //create dropdowns
    d = new Dropdown();
    $('classDropdowns').appendChild(d.container);
    //create classes
    if($('classes')) {
        $A($('classes').children).each(function(row) {
            if(!row.id) {
                return;
            }
            cs = new Course(row, row.id);
            lusa.classes.push(cs);
        });
    }
};

/**
 * Returns the contents of the named cookie.
 *
 * @param c_name STRING - Name of the cookie to get data for.
 * @return STRING - Cookie contents.
 */
lusa.getCookie = function(c_name) {
    if (document.cookie.length>0) {
        c_start=document.cookie.indexOf(c_name + "=");
        if (c_start!=-1) {
            c_start=c_start + c_name.length+1;
            c_end=document.cookie.indexOf(";",c_start);
            if (c_end==-1) {
                c_end=document.cookie.length;
            }
            return unescape(document.cookie.substring(c_start,c_end));
        }
    }
    return "";
 };

/**
 * Returns the name of the cookie currently storing user data.
 *
 * @return STRING - cookie name.
 */
lusa.getCookieName = function() {
   campus = "MAIN";
   if($('campusSelect')) {
       campus = $('campusSelect').value;
   }
   return $('semesterSelect').value+Number($('typeTraditional').checked)+campus;
};

var Dropdown = Class.create({
    /**
     * Constructs a new department dropdown in a div container.
     */
    initialize: function() {
        /** @var OBJECT - Container div for these options. */
        this.container = document.createElement("div");
        /** @var OBJECT - Reference to the department dropdown. */
        this.dept = document.createElement("select");
        /** @var OBJECT - Reference to the class dropdown. */
        this.course = document.createElement("select");
        /** @var INTEGER - The number of hours the current class is worth. */
        this.hours = 0;
        /** @var COURSE - An object to manage the actual display of course info. */
        this.courseMgr = null;

        this.container.appendChild(this.dept);
        this.courseMgr = new Course(this.course);
        option = document.createElement("option");
        option.setAttribute("value", "");
        option.appendChild(document.createTextNode("----"));
        this.dept.appendChild(option);
        new Ajax.Request('postback.php', {
            method: 'post',
            parameters: { mode: 'getDepartmentData', data: lusa.getOptions(), submit: true },
            onSuccess: function(transport) {
                data = transport.responseText.evalJSON();
                Object.values(data).each(function(dept) {
                    option = document.createElement("option");
                    option.setAttribute("value", dept);
                    option.appendChild(document.createTextNode(dept));
                    this.dept.appendChild(option);
                }.bind(this));

                Event.observe(this.dept, 'change', this.departmentSelected.bind(this));
            }.bind(this)
        });

        Event.observe(this.course, 'change', this.courseSelected.bind(this));

        Dropdown.instances.push(this);
    },

    /**
     * Called when a department dropdown is selected.
     *
     * @return VOID
     */
    departmentSelected: function() {
        if(this.dept.value) {
            if(!this.course.firstChild) {
                d = new Dropdown();
                $('classDropdowns').appendChild(d.container);
                this.container.appendChild(this.course);
            }
            this.populateCourse();
        } else {
            this.course.value = 0;
            this.courseSelected();
            Element.remove(this.container);
        }
    },

    /**
     * Called when a specific course is selected.
     *
     * @return VOID
     */
    courseSelected: function() {
        //update hours
        hours = this.course.value.substr(-1);
        $('schedHours').innerHTML = parseInt($('schedHours').innerHTML) + parseInt(hours) - this.hours;
        this.hours = hours;

        //update class list (call to another helper class)
        this.courseMgr.update();

        //update schedule preview (if necessary) (call to another helper class)
        Dropdown.instances.each(function(dropdown) {
            course = dropdown.course.value;
        });

        //update url (call to another helper class)
        lusa.updateLocation();
    },

    /**
     * Populates the course dropdown list with the currently selected department.
     *
     * @return VOID
     */
    populateCourse: function() {
        new Ajax.Request('postback.php', {
            method: 'post',
            parameters: { mode: 'getCourseData', data: lusa.getOptions(), submit: true, dept: this.dept.value },
            onSuccess: function(transport) {
                data = transport.responseText.evalJSON();
                if(this.course.children) {
                    $A(this.course.children).each(function(ele) {
                        Element.remove(ele);
                    });
                }
                option = document.createElement("option");
                option.setAttribute("value", 0);
                option.appendChild(document.createTextNode("----"));
                this.course.appendChild(option);
                Object.keys(data).each(function(course) {
                    option = document.createElement("option");
                    option.setAttribute("value", course);
                    if(data[course]["error"]) {
                        option.setAttribute("style", "color:rgb(177, 177, 177);");
                    }
                    option.appendChild(document.createTextNode(data[course]["class"]));
                    this.course.appendChild(option);
                }.bind(this));
                this.course.activate();
            }.bind(this)
        });
    }
});

Dropdown.instances = new Array();
Dropdown.classes = new Hash();
Dropdown.updatePreview = function() {
    if($('scheduleImg')) {
        tmp = new Array();
        url = "print.php?sem=2011SP&trad=1&classes=";
        Dropdown.classes.each(function(kvp) {
            tmp.push(kvp[1]);
        });
        url += tmp.join("~");
        $('scheduleImg').src = url;
    }
};

var Course = Class.create({
    initialize: function(course) {
        this.course = course;
        this.value = this.course.value;
        this.update();
    },

    update: function() {
        if(this.course.value) {
            if(this.value && this.course.value != this.value) {
                Dropdown.classes.unset(this.value);
                Dropdown.updatePreview();
            }
            new Ajax.Updater('classes', 'postback.php', {
                parameters: { mode: 'addClass', data: lusa.getOptions(), submit: true, id: this.course.value },
                insertion: Insertion.Bottom,
                onSuccess: function(transport) {
                    if(this.value) {
                        $$("."+this.value).each(function(ele) {
                            Element.remove(ele);
                        }.bind(this));
                    }
                }.bind(this),
                onComplete: function(transport) {
                    rows = $$("."+this.course.value);
                    if(rows.length == 1) {
                        Dropdown.classes.set(this.course.value, rows[0].id);
                        Dropdown.updatePreview();
                    }
                    this.value = this.course.value;
                }.bind(this)
            });
        }
    }
});

/**
 * Toggles the visibility of class sections in the class defined by key.
 *
 * @param key STRING - Class ID of the form DEPT-####
 * @return VOID
 */
Course.toggle = function(key) {
    sections = $$('.'+key);
    sections.shift(); // remove the toggle header from the list
    tmp = sections.first();
    if(tmp.style.visibility == "visible") {
        state = "collapse";
        $(key).innerHTML = "+";
    } else {
        state = "visible";
        $(key).innerHTML = "-";
    }
    sections.each(function(section) {
        if(section.style.cursor != "pointer") {
            section.style.visibility = state;
        }
    });
};

Course.selected = function(name, printStr) {
    Dropdown.classes.set(name, printStr);
    Dropdown.updatePreview();
};