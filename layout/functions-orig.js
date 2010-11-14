//java -jar yuicompressor-2.4.2.jar --type js -o functions.js --line-break 0 functions-orig.js
function selectChange(semester, department, uid) {
    if(department != 0) {
        new Ajax.Updater('classChoice'+uid, 'postback.php', {
            parameters: { mode: 'createClassDropdown', data: $('form').serialize(), submit: true, department: department, selection: '----' }
        });
        $('choice'+uid).focus();
    }
}

items = new Hash();
function selectClass(id, uid, str, QS) {
    if(uid != null) {
        items.set(id, str);
    }
    var filterStr = "";
    items.each(function(pair) {
        filterStr += "&cf[]="+uid;
    });
    $('scheduleImg').src = url;
    setLocation($('form').serialize()+filterStr)
}

function selectCampusTrigger(event) {
    updateAll(false);
}

function departmentSelected(uid, semester) {
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
        selectChange(semester, $('classDD'+uid).value, uid);
    }
    courseSelected();
}

function courseSelected(ele) {
    new Ajax.Updater('schedule', 'postback.php', {
        parameters: { mode: 'updateSchedule', data: $('form').serialize(), submit: true }
    });
    setLocation($('form').serialize());

    updateHours();
}

function updateHours() {
    hours = 0;
    $$('.choiceDD').each(function(ele) {
        hours += Number(ele.value.substr(-1));
    });
    $('schedHours').innerHTML = hours;
}

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

function updateAllFromCookie() {
    updateAll(true, getCookie(getCookieName()));
}

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

function updateAllProf() {
    updateAll(true);
}

function setLocation(str) {
    str += "&submit=true";
    document.location.hash = str;
    document.cookie = getCookieName()+"="+str;
}

function getCookieName() {
    campus = "MAIN";
    if($('campusSelect')) {
        campus = $('campusSelect').value;
    }
    return $('semesterSelect').value+Number($('typeTraditional').checked)+campus;
}