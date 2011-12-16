//java -jar layout/yuicompressor-2.4.2.jar --type js -o layout/functions.js --line-break 0 layout/functions-orig.js

/*
 *	Copyright 2009 Bion Oren
 *
 *	Licensed under the Apache License, Version 2.0 (the "License");
 *	you may not use this file except in compliance with the License.
 *	You may obtain a copy of the License at
 *		http://www.apache.org/licenses/LICENSE-2.0
 *	Unless required by applicable law or agreed to in writing, software
 *	distributed under the License is distributed on an "AS IS" BASIS,
 *	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *	See the License for the specific language governing permissions and
 *	limitations under the License.
 */

/**
 * Class to mange general settings and application updates.
 *
 * @author Bion Oren
 * @version 1.0
 */
var lusa = {
    /** STRING - 'student' if this is the student view. */
    student: null,
    /** STRING - 'trad' if this is the trad view. */
    trad: null,
    /** STRING - The currently selected semester. */
    semester: null,
    /** STRING - The currently selected campus. */
    campus: null,
    /** BOOL - True if all the initial data loading is finished. */
    loaded: false
};

/**
 * Initializes the application.
 *
 * @return VOID
 */
lusa.init = function() {
    lusa.updateOptions();
    lusa.loadClasses();
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
            lusa[type] = event.element().value;
            Dropdown.instances = [];
            lusa.updateLocation(true);
        }.bind(this);
    };
    Event.observe($('typeStudent'), 'click', updateFunction("student"));
    Event.observe($('typeProf'), 'click', updateFunction("student"));
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
lusa.updateLocation = function(urlOnly) {
    params = lusa.getOptions();
    params.choice = [];
    Dropdown.instances.each(function(dropdown) {
        if(dropdown.values && dropdown.values.length > 0) {
            params.choice.push(dropdown.values);
        }
    });
    params.choice = params.choice.uniq();
    if(!urlOnly && this.student == "student") {
        date = new Date();
        date.setTime(date.getTime()+(365*24*60*60*1000));
        document.cookie = this.getCookieName()+"="+Object.toJSON(params)+"; expires="+date.toUTCString();
    }
    delete params.choice;
    newSearch = "?"+Object.toQueryString(params);
    if(document.location.search != newSearch) {
        document.location.search = newSearch;
    }
};

/**
 * Updates the schedule preview picture.
 *
 * @return VOID
 */
lusa.updatePreview = function() {
    if($('scheduleImg')) {
        tmp = new Array();
        url = "print.php?sem="+lusa.semester+"&trad="+lusa.trad+"&classes=";
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
    if($('typeStudent').checked) {
        lusa.student = $('typeStudent').value;
    } else {
        lusa.student = $('typeProf').value;
    }
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
    cookie = lusa.getCookie(lusa.getCookieName());
    if(cookie) {
        params = cookie.evalJSON();
    } else {
        params = {};
    }
    params.role = lusa.student;
    params.trad = lusa.trad;
    params.semester = lusa.semester;
    params.campus = lusa.campus;
    return params;
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
            Dropdown.primeCache(function() {
                params = cookie.evalJSON();
                params.choice.each(function(choice) {
                    new Dropdown(choice);
                });
                Course.forceRefresh();
                new Dropdown();
            });
        } else {
            new Dropdown();
        }
    } else {
        if(lusa.student == "student") {
            new Dropdown();
        }
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
        this.container = new Element("div");
        /** @var OBJECT - Reference to the department dropdown. */
        this.dept = new Element("select");
        /** @var OBJECT - Reference to the class dropdown. */
        this.course = new Element("select");
        /** @var INTEGER - The number of hours the current class is worth. */
        this.hours = 0;
        /** @var COURSE - An object to manage the actual display of course info. */
        this.courseMgr = null;
        /** @var ARRAY - List of values for this field. */
        this.values = [];

        this.course.setStyle({
            width: "350px"
        })
        $('classDropdowns').appendChild(this.container);
        this.container.appendChild(this.dept);
        this.courseMgr = new Course(this.course);
        this.dept.appendChild(new Element("option", {
            value: ""
        }).update("----"));
        if(!lusa.deptCache) {
            new Ajax.Request('postback.php', {
                method: 'post',
                parameters: { mode: 'getDepartmentData' },
                onSuccess: function(transport) {
                    data = transport.responseText.evalJSON();
                    lusa.deptCache = data;
                    Object.values(data).each(function(dept) {
                        this.dept.appendChild(new Element("option", {
                            value: dept
                        }).update(dept));
                    }.bind(this));

                    Event.observe(this.dept, 'change', this.departmentSelected.bind(this));
                }.bind(this),
                onComplete: function() {
                    if(value) {
                        this.dept.value = value[0].substr(0, 4);
                        this.departmentSelected(value);
                    }
                }.bind(this)
            });
        } else {
            data = lusa.deptCache;
            Object.values(data).each(function(dept) {
                this.dept.appendChild(new Element("option", {
                    value: dept
                }).update(dept));
            }.bind(this));

            Event.observe(this.dept, 'change', this.departmentSelected.bind(this));

            if(value) {
                this.dept.value = value[0].substr(0, 4);
                this.departmentSelected(value);
            }
        }

        Dropdown.instances.push(this);
    },

    /**
     * Called when a department dropdown is selected.
     *
     * @param value STRING Optional default value of the form DEPT-####. Or, it could be an EVENT object. Bad wharf, bad design!
     * @return VOID
     */
    departmentSelected: function(value) {
        if(this.dept.value) {
            if(!this.course.firstChild) {
                if(!Object.isArray(value)) {
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
    courseSelected: function(event) {
        //update values
        if(event) {
            this.values = event.values;
        } else {
            this.values = [];
        }

        //update hours
        hours = this.course.value.substr(-1);
        $('schedHours').update(parseInt($('schedHours').innerHTML) + parseInt(hours) - this.hours);
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
    populateCourse: function(values) {
        if(!Object.isArray(values)) {
            values = [];
        }
        new Ajax.Request('postback.php', {
            method: 'post',
            parameters: { mode: 'getCourseData', dept: this.dept.value },
            onSuccess: function(transport) {
                data = transport.responseText.evalJSON();
                if(this.course.children) {
                    $A(this.course.children).each(function(ele) {
                        Element.remove(ele);
                    });
                }
                this.course.appendChild(new Element("option", {
                    value: "0"
                }).update("----"));
                Object.keys(data).each(function(course) {
                    option = new Element("option", {
                        value: course
                    }).update(data[course]["class"]);
                    if(data[course].error) {
                        option.setAttribute("disabled", "disabled");
                    }
                    this.course.appendChild(option);
                }.bind(this));
                this.course.activate();
                Event.observe(this.course, 'change', this.courseSelected.bind(this));
                goodSelect = new SelectMultiple(this.course, {
                    defaultText: "Select a class",
                    defaultOption: "0",
                    hoverDisabledCallback: function(event) {
                        $('scheduleImg').src += "&overlayClasses="+data[event.element().getAttribute("data-value")].error;
                    }.bind(this),
                    defaultValue: values
                });
                values.each(function(value) {
                    goodSelect.update(value);
                });
            }.bind(this)
        });
    }
});

/** ARRAY List of dropdown class instances. */
Dropdown.instances = new Array();
/** HASH Mapping from courses to their print script query string argument. */
Dropdown.classes = new Hash();
/**
 * Primes the department dropdown cache
 *
 * @param FUNCTION callback Callback function to run when the cache is update.
 */
Dropdown.primeCache = function(callback) {
    new Ajax.Request('postback.php', {
        method: 'post',
        parameters: { mode: 'getDepartmentData' },
        onSuccess: function(transport) {
            lusa.deptCache = transport.responseText.evalJSON();
            callback();
        }
    });
};

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
                lusa.updatePreview();
            }
            new Ajax.Updater('classes', 'postback.php', {
                parameters: { mode: 'updateClasses' },
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
        if(this.course.value && this.course.value != "0") {
            rows = $$("."+this.course.value);
            if(rows.length > 0 && (rows.length == 1 || typeof rows[1].down("input") == "undefined")) {
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
    if(tmp.getStyle("display") != "none") {
        state = "none";
        $(key).update("+");
    } else {
        state = "table-row";
        $(key).update("-");
    }
    sections.each(function(section) {
        if(section.style.cursor != "pointer") {
            section.setStyle({
                display:state
            });
        }
    });
};

Course.forceRefresh = function() {
    new Ajax.Updater('classes', 'postback.php', {
        parameters: { mode: 'updateClasses' },
        onComplete: function(transport) {
            Dropdown.instances.each(function(dropdown) {
                if(dropdown.courseMgr) {
                    dropdown.courseMgr.update();
                }
            });
            lusa.updatePreview();
            lusa.loaded = true;
        }.bind(this)
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

/**
 * Callback for when a professor is selected.
 *
 * @param OBJECT Reference to the select list that fired this event.
 * @return VOID
 */
function profSelected(ele) {
    new Ajax.Updater('schedule', 'postback.php', {
        parameters: { mode:'updateClasses', data:Object.toQueryString(lusa.getOptions())+"&prof="+ele.value }
    });
}