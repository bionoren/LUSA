//java -jar yuicompressor-2.4.2.jar --type js -o functions.js --line-break 0 functions-orig.js

/**
 * Toggles the visibility of class sections in the class defined by key.
 *
 * @param key STRING - Class ID of the form DEPT-####
 * @return VOID
 */
function createJSToggle(key) {
    sections = $$('.'+key);
    tmp = sections.first();
    if(tmp.style.visibility == "visible") {
        state = "collapse";
        $(key).innerHTML = "+";
    } else {
        state = "visible";
        $(key).innerHTML = "-";
    }
    sections.each(function(section) {
        section.style.visibility = state;
    });
}

/**
 * Displays a new class selection dropdown.
 *
 * @param department STRING - The name of the selected department
 * @param uid STRING - The unique ID for this department / class dropdown pair
 * @return VOID
 */
function selectChange(department, uid) {
    if(department != 0) {
        new Ajax.Updater('classChoice'+uid, 'postback.php', {
            parameters: { mode: 'createClassDropdown', data: $('form').serialize(), submit: true, department: department, selection: '----' },
            onComplete: function() {
                $('choice'+uid).focus();
            }
        });
    }
}

items = new Hash();
/**
 * Sets class information in the items hash.
 * NOTE: Requires a global items hash (above)
 *
 * @param id STRING - ID of the class
 * @param uid STRING - Unique ID of the class section
 * @param str STRING - The class info prepped for the print script
 * @return VOID
 */
function setClassInfo(id, uid, str) {
    if(id != null) {
        items.set(id, [str, uid]);
    }
}

/**
 * Updates the schedule preview to include the given selection
 * NOTE: Requires a global items hash (above)
 *
 * @param id STRING - ID of the class
 * @param uid STRING - Unique ID of the class section
 * @param str STRING - The class info prepped for the print script
 * @return VOID
 */
function selectClass(id, uid, str) {
    setClassInfo(id, uid, str);
    var url = "print.php?sem="+$('semesterSelect').value+"&trad="+$('typeTraditional').value+"&classes=";
    var filterStr = "";
    items.each(function(pair) {
        url += "~"+pair.value[0];
        filterStr += "&cf[]="+pair.value[1];
    });
    if($('scheduleImg')) {
        $('scheduleImg').src = url;
    }
    setLocation($('form').serialize()+filterStr)
}

/**
 * Updates everything with a new campus.
 *
 * @return VOID
 */
function selectCampusTrigger(event) {
    updateAll(false);
}

/**
 * Called when a department dropdown is selected.
 *
 * @param uid STRING - The unique ID of the department dropdown.
 * @return VOID
 */
function departmentSelected(uid) {
    //if there isn't a course dropdown with this department yet, create a new blank department dropdown
    if($('choice'+uid).empty()) {
        new Ajax.Updater('classDropdowns', 'postback.php', {
            parameters: { mode: 'createClassDropdown', data: $('form').serialize(), submit: true },
            insertion: 'bottom'
        });
    }
    //if the selected department is blank, then we aren't selecting a department here
    if($('classDD'+uid).value == "0") {
        $('classDD'+uid).parentNode.remove();
        setLocation($('form').serialize());
        courseSelected();
    } else {
        //otherwise, we selected a dropdown and need to populate the course dropdown
        selectChange($('classDD'+uid).value, uid);
    }
}

/**
 * Called when a specific course is selected.
 *
 * @return VOID
 */
function courseSelected() {
    new Ajax.Updater('schedule', 'postback.php', {
        parameters: { mode: 'updateSchedule', data: document.location.hash, submit: true },
        evalScripts: true,
        onComplete: function() {
            setLocation($('form').serialize());
            updateHours();
        }
    });
}

/**
 * Updates the number of credit hours.
 *
 * @return VOID
 */
function updateHours() {
    hours = 0;
    $$('.choiceDD').each(function(ele) {
        hours += Number(ele.value.substr(-1));
    });
    $('schedHours').innerHTML = hours;
}

/**
 * Updates everything.
 *
 * @param update BOOLEAN - If true, updates the url.
 * @param data STRING - Optional data to send instead of the serialized form.
 * @return VOID
 */
function updateAll(event) {
    update = this['do'];
    //try getting data from the parameter, falling back to the cookie, falling back to the form
    if(update) {
        foo = function() {
            setLocation($('form').serialize())
        }
        data = getCookie(getCookieName());
    } else {
        foo = function () {}
        data = $('form').serialize();
    }

    new Ajax.Updater('body', 'postback.php', {
        parameters: { mode: 'updateAll', data: data, submit: true },
        onComplete: foo
    });
}

/**
 * Returns the contents of the named cookie.
 *
 * @param c_name STRING - Name of the cookie to get data for.
 * @return STRING - Cookie contents.
 */
function getCookie(c_name) {
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
}

/**
 * Sets the user's current location in the URL and in a cookie.
 *
 * @param str STRING - the new location.
 * @return VOID
 */
function setLocation(str) {
    str += "&submit=true";
    document.location.hash = str;
    document.cookie = getCookieName()+"="+str;
}

/**
 * Returns the name of the cookie currently storing user data.
 *
 * @return STRING - cookie name.
 */
function getCookieName() {
    campus = "MAIN";
    if($('campusSelect')) {
        campus = $('campusSelect').value;
    }
    return $('semesterSelect').value+Number($('typeTraditional').checked)+campus;
}

/**
 * Called when a professor is selected
 *
 * @param ele OBJECT - The select dropdown that was changed
 * @return VOID
 */
function profSelected(ele) {
    if(!ele.empty()) {
        new Ajax.Updater('schedule', 'postback.php', {
            parameters: { mode: 'updateSchedule', data: $('form').serialize(), submit: true }
        });
    }
}