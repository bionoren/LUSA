(function() {
    var SelectMultiple, root;
    root = this;
    SelectMultiple = (function() {
        function SelectMultiple(select, options) {
            this.select = select;
            this.selectDiv = null;
            this.config = options != null ? options : {};
            this.selected = [];
            this.eventListeners = {};
            this.visible = false;
            this.setDefaults();
            this.init();
        }

        SelectMultiple.prototype.setDefaults = function() {
            if(!("defaultText" in this.config)) {
                this.config.defaultText = "Select an option";
            }
            if(!("defaultOption" in this.config)) {
                this.config.defaultOption = "";
            }
        };

        SelectMultiple.prototype.init = function() {
            this.eventListeners = this.select.getStorage().get('prototype_event_registry').get('change');
            this.select.stopObserving('change');
            this.select.observe('sm:change', this.selectChanged.bind(this));

            //replace the select widget
            this.select.setStyle({
//                display: "none"
            });

            this.selectDiv = new Element("div", {
                class: "chzn-container chzn-container-single"
            });
            this.selectDiv.observe("click", this.selectClicked.bind(this));
            this.selectDiv.setStyle({
                width: this.select.getStyle("width")
            });
            this.select.insert({
                after: this.selectDiv
            });
            var anchor = new Element("a", {
                href:"javascript:void(0)",
                class:"chzn-single"
            });
            this.selectDiv.appendChild(anchor);
            anchor.appendChild(new Element("span", {
                class:"selectedText"
            }));
            anchor.appendChild(new Element("div").update("<b></b>"));

            //add our own dropdown
            var dropdown = new Element("div", {
                class: "chzn-drop"
            });
            dropdown.setStyle({
                width:(this.selectDiv.getStyle('width').substring(0, this.selectDiv.getStyle('width').length-2)-2)+"px",
                top: "27px",
                display: "none"
            });
            this.selectDiv.appendChild(dropdown);
            list = new Element("ul", {
                class: "chzn-results"
            });
            dropdown.appendChild(list);
            this.select.childElements().each(function(option) {
                if(option.value == this.config.defaultOption) {
                    return;
                }
                var item = new Element("li", {
                    class: "active-result",
                    "data-value": option.value
                }).update(option.innerHTML);
                list.appendChild(item);
                if(!option.disabled) {
                    item.observe("mouseover", this.optionMouseOver.bind(this));
                    item.observe("mouseout", this.optionMouseOut.bind(this));
                    item.observe("click", this.optionMouseClick.bind(this));
                } else {
                    item.addClassName("chsn-disabled-option");
                    item.observe("click", this.optionDisabledMouseClick.bind(this));
                }
                if(option.selected) {
                    this.selected.push(item.getAttribute("data-value"));
                }
            }.bind(this));
            document.observe("click", function(event) {
                if(event.element() != this.selectDiv && !event.element().descendantOf(this.selectDiv)) {
                    this.visible = true;
                    this.selectClicked(event);
                }
            }.bind(this));

            //show the selected options (if any)
            this.displaySelected();
        };

        SelectMultiple.prototype.displaySelected = function(event) {
            if(this.selected.length == 0) {
                this.selectDiv.firstChild.update(this.config.defaultText);
            } else {
                this.selectDiv.firstChild.update(this.selected.join(","));
            }
        };

        //option events
        SelectMultiple.prototype.optionMouseOver = function(event) {
            event.toElement.addClassName("highlighted");
        };

        SelectMultiple.prototype.optionMouseOut = function(event) {
            event.fromElement.removeClassName("highlighted");
        };

        SelectMultiple.prototype.optionMouseClick = function(event) {
            this.select.setValue(event.element().getAttribute("data-value"));
            Event.fire(this.select, "sm:change");
            event.stopPropagation();
        };

        SelectMultiple.prototype.optionDisabledMouseClick = function(event) {
            event.stopPropagation();
        };

        //select events
        SelectMultiple.prototype.selectClicked = function(event) {
            if(!this.visible) {
                this.selectDiv.down(".chzn-drop").setStyle({
                    display: "inherit"
                });
                this.selectDiv.down().addClassName("chzn-single-with-drop");
                this.selectDiv.addClassName("chzn-container-active");
            } else {
                this.selectDiv.down(".chzn-drop").setStyle({
                    display: "none"
                });
                this.selectDiv.removeClassName("chzn-container-active");
                this.selectDiv.down().removeClassName("chzn-single-with-drop");
            }
            this.visible = !this.visible;
        };

        SelectMultiple.prototype.selectChanged = function(event) {
            value = this.select.value
            index = this.selected.indexOf(value);
            if(index == -1) {
                this.selected.push(value);
            } else {
                this.selected = this.selected.without(value);
            }
            if(this.selected.length == 0) {
                this.select.value = "0";
            }

            this.displaySelected();
            this.eventListeners.each(function(listener, index) {
                listener(event, this.selected);
            }.bind(this));
        };

        return SelectMultiple;
    })();
    root.SelectMultiple = SelectMultiple;
}).call(this)