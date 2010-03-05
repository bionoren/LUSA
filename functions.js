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
        var txt = document.createTextNode(pair.value);
        myEle.appendChild(txt);
        select.appendChild(myEle);
    });
    //add select box
    $(controlToPopulate).appendChild(select);
}