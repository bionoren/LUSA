//java -jar yuicompressor-2.4.2.jar --type js -o functions.js --line-break 0 --nomunge functions-orig.js
function selectChange(semester, department, uid) {
    if(department != 0) {
        new Ajax.Updater('classChoice'+uid, 'createClassDropdown.php', {
            parameters: { semester: semester, department: department, selection: '----' }
        });
    } else {
        new Ajax.Updater('classChoice'+uid, 'createClassDropdown.php', {
            parameters: { }
        });
    }

    $('choice'+uid).focus();
}

function selectClass(course, str, QS) {
    if(course != null) {
        items.set(course, str);
    }
    var url = "print.php?"+QS;
    items.each(function(pair) {
        url += "~"+pair.value;
    });
    $('schedule').src = url;
    $('printer').href = url;
}

function selectCampusTrigger(event) {
    updateAll();
}

function departmentSelected(ele, uid, semester) {
    if($('choice'+uid).empty()) {
        new Ajax.Updater('classDropdowns', 'createClassDropdown.php', {
            parameters: { semester: semester },
            insertion: 'bottom'
        });
    }
    selectChange(semester, $('classDD'+uid).value, uid);
}

function courseSelected() {
    new Ajax.Updater('schedule', 'updateSchedule.php', {
        parameters: { data: $('form').serialize() }
    });
    document.location.hash = $('form').serialize()
}

function updateAll() {
    new Ajax.Updater('body', 'updateAll.php', {
        parameters: { data: $('form').serialize() }
    });
    document.location.hash = $('form').serialize()
}