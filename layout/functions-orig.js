//java -jar layout/yuicompressor-2.4.2.jar --type js -o layout/functions.js --line-break 0 layout/functions-orig.js

/**
 * Class to mange general settings and application updates.
 *
 * @author Bion Oren
 * @version 1.0
 */
var lusa = {
    /** STRING - student if this is the student view. */
    student: null,
    /** STRING - trad if this is the trad view. */
    trad: null,
    /** STRING - The currently selected semester. */
    semester: null,
    /** STRING - The currently selected campus. */
    campus: null
};

/**
 * Initializes the application.
 *
 * @return VOID
 */
lusa.init = function() {
    lusa.updateOptions();
    lusa.loadClasses();
    Event.observe($('typeStudent'), 'click', function(event) {
        lusa.student = this.value;
    });
/*    Event.observe($('typeProf'), 'click', function(event) {
        lusa.student = this.value;
    });*/
    /**
     * Generates a callback function to update the entire application state.
     *
     * @param STRING type The instance variable to be updated by the callback function.
     * @return FUNCTION
     */
    updateFunction = function(type) {
        /**
         * Callback function to update a property and reload the app.
         *
         * @param EVENT Event that triggered the callback.
         * @return VOID
         */
        return function(event) {
            lusa[type] = this.value;
            cookie = lusa.getCookie(lusa.getCookieName());
            document.location = "index.php?"+lusa.getOptions()+"#"+cookie;
        }
    };
    Event.observe($('typeTraditional'), 'click', updateFunction("trad"));
    Event.observe($('typeNonTraditional'), 'click', updateFunction("trad"));
    if($('campusSelect')) {
        Event.observe($('campusSelect'), 'change', updateFunction("campus"));
    }
    Event.observe($('semesterSelect'), 'change', updateFunction("semester"));
};

/**
 * Updates the location in the URL hash and in the cookie.
 *
 * @return VOID
 */
lusa.updateLocation = function() {
    str = lusa.getOptions();
    Dropdown.instances.each(function(dropdown) {
        if(dropdown.course.value && dropdown.course.value != "0") {
            str += "&choice[]="+dropdown.course.value;
        }
    });
    document.location.hash = str;
    date = new Date();
    date.setTime(date.getTime()+(365*24*60*60*1000));
    document.cookie = this.getCookieName()+"="+str+"; expires="+date.toUTCString();
};

/**
 * Updates the schedule preview picture.
 *
 * @return VOID
 */
lusa.updatePreview = function() {
    if($('scheduleImg')) {
        tmp = new Array();
        url = "print.php?sem=2011SP&trad="+lusa.trad+"&classes=";
        Dropdown.classes.each(function(kvp) {
            tmp.push(kvp[1]);
        });
        url += tmp.join("~");
        $('scheduleImg').src = url;
    }
};

/**
 * Updates all the internal variables from their form controls.
 *
 * @return VOID
 */
lusa.updateOptions = function() {
    lusa.student = $('typeStudent').value;
    if($('typeTraditional').checked) {
        lusa.trad = $('typeTraditional').value;
    } else {
        lusa.trad = $('typeNonTraditional').value;
    }
    if($('campusSelect')) {
        lusa.campus = $('campusSelect').value;
    }
    if(!lusa.campus) {
        lusa.campus = "MAIN";
    }
    lusa.semester = $('semesterSelect').value;
};

/**
 * Returns a list of common options in a URL ready format.
 *
 * @return STRING List of options for a URL.
 */
lusa.getOptions = function() {
    return "role="+lusa.student+"&type="+lusa.trad+"&semester="+lusa.semester+"&campus="+lusa.campus;
};

/**
 * Loads classes from any visible class descriptions and creates a new empty dropdown.
 *
 * @return VOID
 */
lusa.loadClasses = function() {
    if($('classes')) {
        cookie = lusa.getCookie(lusa.getCookieName());
        if(cookie) {
            cookie.split("&").each(function(part) {
                if(part.startsWith("choice[]")) {
                    new Dropdown(part.split("=")[1]);
                }
            });
        }
    }
    //create dropdowns
    new Dropdown();
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
    return null;
 };

/**
 * Returns the name of the cookie currently storing user data.
 *
 * @return STRING - cookie name.
 */
lusa.getCookieName = function() {
   return lusa.semester+lusa.trad+lusa.campus;
};

/**
 * Manages class selection dropdowns.
 *
 * @author Bion Oren
 * @version 1.0
 */
var Dropdown = Class.create({
    /**
     * Constructs a new department dropdown in a div container.
     *
     * @param value STRING Optional default value of the form DEPT-####.
     */
    initialize: function(value) {
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

        $('classDropdowns').appendChild(this.container);
        this.container.appendChild(this.dept);
        this.courseMgr = new Course(this.course);
        option = document.createElement("option");
        option.setAttribute("value", "");
        option.appendChild(document.createTextNode("----"));
        this.dept.appendChild(option);
        if(!lusa.deptCache) {
            new Ajax.Request('postback.php', {
                method: 'post',
                parameters: { mode: 'getDepartmentData', data: lusa.getOptions() },
                onSuccess: function(transport) {
                    data = transport.responseText.evalJSON();
                    lusa.deptCache = data;
                    Object.values(data).each(function(dept) {
                        option = document.createElement("option");
                        option.setAttribute("value", dept);
                        option.appendChild(document.createTextNode(dept));
                        this.dept.appendChild(option);
                    }.bind(this));

                    Event.observe(this.dept, 'change', this.departmentSelected.bind(this));
                }.bind(this),
                onComplete: function() {
                    if(value) {
                        this.dept.value = value.substr(0, 4);
                        this.departmentSelected(value);
                    }
                }.bind(this)
            });
        } else {
            data = lusa.deptCache;
            Object.values(data).each(function(dept) {
                option = document.createElement("option");
                option.setAttribute("value", dept);
                option.appendChild(document.createTextNode(dept));
                this.dept.appendChild(option);
            }.bind(this));

            Event.observe(this.dept, 'change', this.departmentSelected.bind(this));

            if(value) {
                this.dept.value = value.substr(0, 4);
                this.departmentSelected(value);
            }
        }

        Event.observe(this.course, 'change', this.courseSelected.bind(this));

        Dropdown.instances.push(this);
    },

    /**
     * Called when a department dropdown is selected.
     *
     * @param value STRING Optional default value of the form DEPT-####.
     * @return VOID
     */
    departmentSelected: function(value) {
        if(this.dept.value) {
            if(!this.course.firstChild) {
                if(!Object.isString(value)) {
                    d = new Dropdown();
                    $('classDropdowns').appendChild(d.container);
                }
                this.container.appendChild(this.course);
            }
            this.course.value = 0; //reset the course value for each dropdown selection
            lusa.updateLocation();
            this.populateCourse(value);
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

        //update url
        lusa.updateLocation();

        //update class list
        this.courseMgr.reload();
    },

    /**
     * Populates the course dropdown list with the currently selected department.
     *
     * @param value STRING Optional default value of the form DEPT-####.
     * @return VOID
     */
    populateCourse: function(value) {
        new Ajax.Request('postback.php', {
            method: 'post',
            parameters: { mode: 'getCourseData', data: document.location.hash, dept: this.dept.value },
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
                    if(data[course].error) {
                        option.setAttribute("disabled", "disabled");
                    }
                    option.appendChild(document.createTextNode(data[course]["class"]));
                    this.course.appendChild(option);
                }.bind(this));
                this.course.activate();
            }.bind(this),
            onComplete: function() {
                if(value) {
                    this.course.value = value;
                    this.courseSelected();
                }
            }.bind(this)
        });
    }
});

/** ARRAY List of dropdown class instances. */
Dropdown.instances = new Array();
/** HASH Mapping from courses to their print script query string argument. */
Dropdown.classes = new Hash();

/**
 * Class to manage interactions with class dropdowns and their interactions with the schedule preview and class information table row(s).
 *
 * @author Bion Oren
 * @version 1.0
 */
var Course = Class.create({
    /**
     * Initializes the class.
     *
     * @param OBJECT course Reference to a class dropdown.
     */
    initialize: function(course) {
        this.course = course;
        this.value = this.course.value;
        this.reload();
    },

    /**
     * Reloads the class dropdown with the current department information.
     *
     * @return VOID
     */
    reload: function() {
        if(this.course.value) {
            if(this.value && this.course.value != this.value) {
                Dropdown.classes.unset(this.value);
            }
            new Ajax.Updater('classes', 'postback.php', {
                parameters: { mode: 'updateClasses', data: document.location.hash },
                onSuccess: function(transport) {
                    if(this.value) {
                        $$("."+this.value).each(function(ele) {
                            Element.remove(ele);
                        }.bind(this));
                    }
                }.bind(this),
                onComplete: function(transport) {
                    this.value = this.course.value;
                    Dropdown.instances.each(function(dropdown) {
                        if(dropdown.courseMgr) {
                            dropdown.courseMgr.update();
                        }
                    });
                    lusa.updatePreview();
                }.bind(this)
            });
        }
    },

    /**
     * Updates the Dropdown.classes hash for the course this class is managing.
     *
     * @return VOID
     */
    update: function() {
        if(this.course.value) {
            rows = $$("."+this.course.value);
            if(rows.length == 1) {
                Dropdown.classes.set(this.value, rows[0].id);
            }
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

/**
 * Callback for when a class is selected among options.
 *
 * @param STRING name Class name.
 * @param STRING printStr Query parameter for the print script.
 * @return VOID
 */
Course.selected = function(name, printStr) {
    Dropdown.classes.set(name, printStr);
    lusa.updatePreview();
};