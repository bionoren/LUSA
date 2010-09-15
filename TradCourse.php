<?php
    require_once("Course.php");

    class TradCourse extends Course {
        /**
		 * Displays this class in a table.
		 *
		 * @param BOOLEAN $optional True if this class is part of an optional set of classes.
		 * @return VOID
		 */
        public function display($optional=false) {
			print '<tr id="'.$this->getUID().'0" class="'.$this->getBackgroundStyle().'"';
            if($optional) {
                print ' style="visibility:collapse;"';
            }
            print '>';
				if($optional) {
					$qstring = Course::$QS.'%sf[]='.$this->getUID().'&amp;submit=Filter';
					$filterLink = '<a href="'.$qstring.'" style="color:blue; text-decoration:underline;"><strong>%s</strong></a>';
					print '<td headers="classHeader">';
						printf($filterLink, "c", "Choose");
						print ' or ';
						printf($filterLink, "r", "Remove");
					print '</td>';
					print '<td style="width:auto;" headers="classHeader">';
						if(!$this->isSpecial()) {
							print "<input type='radio' id='select".$this->getUID()."' name='".$this->getID()."' value='".$this->section."' onclick=\"selectClass('".$this->getID()."', '".$this->getPrintQS()."', '".Schedule::getPrintQS(Schedule::$common)."');\"/>";
							print "<label for='select".$this->getUID()."'>Preview</label>";
						}
					print "</td>";
				} else {
					print '<td headers="classHeader">'.$this->getID().'</td>';
					print '<td headers="classHeader">'.$this->title.'</td>';
				}
                print '<td headers="sectionHeader">'.$this->section.'</td>';
                $this->meetings[0]->display(false);
                print '<td headers="registeredHeader">'.$this->currentRegistered.'/'.$this->maxRegisterable.'</td>';
			print '</tr>';
            for($i = 1; $i < count($this->meetings); $i++) {
                print '<tr id="'.$this->getUID().$i.'" class="'.$this->getBackgroundStyle().'"';
                    if($optional) {
                        print ' style="visibility:collapse;"';
                    }
                    print '>';
                    print '<td colspan="3">&nbsp;</td>';
                    $this->meetings[$i]->display(false);
                    print '<td></td>';
                print '</tr>';
            }
        }
    }
?>