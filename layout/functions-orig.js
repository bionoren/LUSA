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
function selectClass(course, str, QS) {
    if(course != null) {
        items.set(course, str);
    }
    var url = "print.php?"+QS;
    items.each(function(pair) {
        url += "~"+pair.value;
    });
    $('scheduleImg').src = url;
    $('printer').href = url;
}

function selectCampusTrigger(event) {
    updateAll();
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

function updateAll() {
    new Ajax.Updater('body', 'postback.php', {
        parameters: { mode: 'updateAll', data: $('form').serialize(), submit: true }
    });
    setLocation($('form').serialize())
}

function setLocation(str) {
    str += "&submit=true";
    document.location.hash = str;
    campus = "MAIN";
    if($('campusSelect')) {
        campus = $('campusSelect').value;
    }
    document.cookie = $('semesterSelect').value+Number($('typeTraditional').checked)+campus+"="+str;
}