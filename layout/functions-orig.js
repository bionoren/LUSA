//java -jar yuicompressor-2.4.2.jar --type js -o functions.js --line-break 0 --nomunge functions-orig.js
function selectChange(control, controlToPopulate) {
    // Empty the second drop down box of any choices
    if($(controlToPopulate).children != null) {
        $(controlToPopulate).innerHTML = "";
    }
    //create select box
    var select = document.createElement("select");
    select.setAttribute("name", "choice[]");
    // ADD Default Choice - in case there are no values
    var myEle = document.createElement("option");
    theText = document.createTextNode("----");
    myEle.appendChild(theText);
    select.appendChild(myEle);
    if(arrItems.get(control.value) == null) {
        //some browsers (read some versions of some browsers) feel obligated to pass
        //on empty values if a select statement is populated with an empty option
        //Therefore, we make empty fields truly empty here.
        $(controlToPopulate).innerHTML = "";
        return;
    }
    arrItems.get($(control).value).each(function(pair) {
        myEle = document.createElement("option");
        myEle.setAttribute("value", pair.key);
        if(!pair.value[1]) {
            myEle.setAttribute("style", "color:rgb(177, 177, 177);");
        }
        var txt = document.createTextNode(pair.value[0]);
        myEle.appendChild(txt);
        select.appendChild(myEle);
    });
    //add select box
    $(controlToPopulate).appendChild(select);
    select.focus();
}

var items = new Hash();
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
    var path = window.location.protocol + '//' + window.location.host + window.location.pathname;
    window.location = path + '?type=' + ($('typeTraditional').checked == true ? 'trad' : 'non') + '&campus=' + escape(this.value) + '&submit=Change&semester=' + escape($('semesterSelect').value);
}