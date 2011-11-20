/**
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

(function() {
    var SelectMultiple, root;
    root = this;
    SelectMultiple = (function() {
        /**
         * Replaces a select multiple element with a widget that allows selecting multiple elements in a way that doesn't completely suck.
         * Note that any onChange events registered on the select element by prototype will receive a "sm:change" event instead.
         *
         * @param ELEMENT select HTML SELECT element to replace.
         * @param OBJECT options:
         * defaultText - STRING - text to use when no option is selected
         * defaultOption - STRING - value of the default (empty) option
         * optionValueField - STRING - Option field to use for display in the list of selected items. data-* is the recommended format.
         * hoverDisabledCallback - FUNCTION - function to call for onHover events on select elements
         * defaultValue - ARRAY - List of values to select by default
         */
        function SelectMultiple(select, options) {
            this.select = select;
            this.selectDiv = null;
            this.config = options != null ? options : {};
            this.selected = [];
            this.selectedDisplay = [];
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
            if(!("hoverDisabledCallback" in this.config)) {
                this.config.hoverDisabledCallback = null;
            }
            if(!("optionValueField" in this.config)) {
                this.config.optionValueField = "value";
            }
        };

        SelectMultiple.prototype.init = function() {
            this.eventListeners = this.select.getStorage().get('prototype_event_registry').get('change');
            this.select.stopObserving('change');
            this.select.observe('sm:change', this.selectChanged.bind(this));

            //replace the select widget
            this.select.setStyle({
                display: "none"
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
                item.setAttribute("data-display", option.getAttribute(this.config.optionValueField));
                list.appendChild(item);
                if(!option.disabled) {
                    item.observe("mouseover", this.optionMouseOver.bind(this));
                    item.observe("mouseout", this.optionMouseOut.bind(this));
                    item.observe("click", this.optionMouseClick.bind(this));
                } else {
                    if(this.config.hoverDisabledCallback) {
                        item.observe("mouseover", this.config.hoverDisabledCallback);
                    }
                    item.addClassName("chsn-disabled-option");
                    item.observe("click", this.optionDisabledMouseClick.bind(this));
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
            Event.fire(this.select, "sm:change");
        };

        SelectMultiple.prototype.update = function(newValue) {
            this.select.value = newValue;
            Event.fire(this.select, "sm:change");
        };

        SelectMultiple.prototype.displaySelected = function(event) {
            if(this.selected.length == 0) {
                this.selectDiv.down().down().update(this.config.defaultText);
            } else {
                this.selectDiv.down().down().update(this.selectedDisplay.join(","));
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
            if(value != this.config.defaultOption) {
                if(this.selected.indexOf(value) == -1) {
                    this.selected.push(value);
                    if(this.config.optionValueField) {
                        dispValue = this.select[this.select.options.selectedIndex].getAttribute(this.config.optionValueField);
                    } else {
                        dispValue = value;
                    }
                    this.selectedDisplay.push(dispValue)
                } else {
                    this.selected = this.selected.without(value);
                    if(this.config.optionValueField) {
                        this.select.childElements().each(function(option) {
                            if(option.value == value) {
                                this.selectedDisplay = this.selectedDisplay.without(option.getAttribute(this.config.optionValueField));
                            }
                        }.bind(this));
                    } else {
                        this.selectedDisplay = this.selectedDisplay.without(value)
                    }
                }
                if(this.selected.length == 0) {
                    this.select.value = this.config.defaultOption;
                }
            }

            this.displaySelected();
            event.values = this.selected;
            this.eventListeners.each(function(listener, index) {
                listener(event);
            }.bind(this));
        };

        return SelectMultiple;
    })();
    root.SelectMultiple = SelectMultiple;
}).call(this)