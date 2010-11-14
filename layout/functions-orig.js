//java -jar yuicompressor-2.4.2.jar --type js -o functions.js --line-break 0 functions-orig.js
/**
 * Displays a new class selection dropdown and focuses it.
 *
 * @param department STRING - The name of the selected department
 * @param uid STRING - The unique ID for this department / class dropdown pair
 * @return VOID
 */
function selectChange(department, uid) {
    if(department != 0) {
        new Ajax.Updater('classChoice'+uid, 'postback.php', {
            parameters: { mode: 'createClassDropdown', data: $('form').serialize(), submit: true, department: department, selection: '----' }
        });
        $('choice'+uid).focus();
    }
}

items = new Hash();
/**
 * Updates the schedule preview to include the given selection
 * NOTE: Requires a global items hash (above)
 *
 * @param id STRING - ID of the class
 * @param uid STRING - Unique ID of the class section
 * @param str STRING - The class info prepped for the print script
 * @param QS STRING - Any variables to prepend to the print request
 * @return VOID
 */
function selectClass(id, uid, str, QS) {
    if(id != null) {
        items.set(id, str);
    }
    var url = "print.php?"+QS;
    var filterStr = "";
    items.each(function(pair) {
        url += "~"+pair.value;
        filterStr += "&cf[]="+uid;
    });
    $('scheduleImg').src = url;
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
 * @param uid The unique ID of the department dropdown.
 * @return VOID
 */
function departmentSelected(uid) {
    if($('choice'+uid).empty()) {
        new Ajax.Updater('classDropdowns', 'postback.php', {
            parameters: { mode: 'createClassDropdown', data: $('form').serialize(), submit: true },
            insertion: 'bottom'
        });
    }
    if($('classDD'+uid).value == "0") {
        blanks = false;
        $$('.classDD').each(function(ele) {
            if(blanks && ele.firstChild.value == "0") {
                ele.remove();
            } else if(!ele.firstChild || ele.firstChild.value == "0") {
                blanks = true;
            }
        });
        if($('choice'+uid)) {
            $('choice'+uid).innerHTML = "";
        }
    } else {
        selectChange($('classDD'+uid).value, uid);
    }
    courseSelected();
}

/**
 * Called when a specific course is selected.
 *
 * @return VOID
 */
function courseSelected() {
    new Ajax.Updater('schedule', 'postback.php', {
        parameters: { mode: 'updateSchedule', data: $('form').serialize(), submit: true }
    });
    setLocation($('form').serialize());

    updateHours();
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
 * @param noLocationUpdate BOOLEAN If false, updates the url.
 * @param data Optional data to send instead of the serialized form.
 * @return VOID
 */
function updateAll(noLocationUpdate, data) {
    if(!data) {
        data = $('form').serialize();
    }
    new Ajax.Updater('body', 'postback.php', {
        parameters: { mode: 'updateAll', data: data, submit: true }
    });
    if(!noLocationUpdate) {
        setLocation($('form').serialize())
    }
}

/**
 * Updates everything with no location update from cookie data.
 *
 * @return VOID
 */
function updateAllFromCookie() {
    updateAll(true, getCookie(getCookieName()));
}

/**
 * Returns the contents of the named cookie.
 *
 * @param c_name STRING Name of the cookie to get data for.
 * @return STRING Cookie contents.
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
 * Updates everything without updating the current location.
 *
 * @return VOID
 */
function updateAllProf() {
    updateAll(true);
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
 * @return STRING cookie name.
 */
function getCookieName() {
    campus = "MAIN";
    if($('campusSelect')) {
        campus = $('campusSelect').value;
    }
    return $('semesterSelect').value+Number($('typeTraditional').checked)+campus;
}