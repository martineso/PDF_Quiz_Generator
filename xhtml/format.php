<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * XHTML question exporter.
 *
 * @package    qformat_xhtml
 * @copyright  2005 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * XHTML question exporter.
 *
 * Exports questions as static HTML.
 *
 * @copyright  2005 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_xhtml extends qformat_default {

    private $pdf = null;
	
	public function __construct() {
        $this->pdf = $this->get_pdf_generator_instance();
    }
	
    public function provide_export() {
        return true;
    }

    protected function repchar($text) {
        return $text;
    }

    protected function writequestion($question) {
        global $OUTPUT;
        // Turns question into string.
        // Question reflects database fields for general question and specific to type.

        // If a category switch, just ignore.
        if ($question->qtype=='category') {
            return '';
        }
        
        
        // Initial string.
        $expout = "";
        $id = $question->id;

        $expout .= $question->name . " ";
		$expout .= strip_tags($question->questiontext);
        $expout .= "\n";
		
        // Selection depends on question type.
        switch($question->qtype) {
            case 'truefalse':
            	$sttrue = get_string('true', 'qtype_truefalse');
                $stfalse = get_string('false', 'qtype_truefalse');
                
                $expout .= $this->tab() . $sttrue . "\n";
                $expout .= $this->tab() . $stfalse . "\n";
                $expout .= "\n";
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
               
                break;
            case 'multichoice':
                $expout .= "<ul class=\"multichoice\">\n";
                foreach ($question->options->answers as $answer) {
                    $answertext = $this->repchar( $answer->answer );
                    if ($question->options->single) {
                        $expout .= "  <li><input name=\"quest_{$id}\" type=\"radio\" value=\""
                                . s($answertext) . "\" />{$answertext}</li>\n";
                    } else {
                        $expout .= "  <li><input name=\"quest_{$id}\" type=\"checkbox\" value=\""
                                . s($answertext) . "\" />{$answertext}</li>\n";
                    }
                }
                $expout .= "</ul>\n";
                break;
            case 'shortanswer':
                $expout .= html_writer::start_tag('ul', array('class' => 'shortanswer'));
                $expout .= html_writer::start_tag('li');
                $expout .= html_writer::label(get_string('answer'), 'quest_'.$id, false, array('class' => 'accesshide'));
                $expout .= html_writer::empty_tag('input', array('id' => "quest_{$id}", 'name' => "quest_{$id}", 'type' => 'text'));
                $expout .= html_writer::end_tag('li');
                $expout .= html_writer::end_tag('ul');
                break;
            case 'numerical':
                $expout .= html_writer::start_tag('ul', array('class' => 'numerical'));
                $expout .= html_writer::start_tag('li');
                $expout .= html_writer::label(get_string('answer'), 'quest_'.$id, false, array('class' => 'accesshide'));
                $expout .= html_writer::empty_tag('input', array('id' => "quest_{$id}", 'name' => "quest_{$id}", 'type' => 'text'));
                $expout .= html_writer::end_tag('li');
                $expout .= html_writer::end_tag('ul');
                break;
            case 'match':
                $expout .= html_writer::start_tag('ul', array('class' => 'match'));
				
                // Build answer list.
                $answerlist = array();
                foreach ($question->options->subquestions as $subquestion) {
                    $answerlist[] = $this->repchar( $subquestion->answertext );
                }
                shuffle( $answerlist ); // Random display order.

                // Build select options.
                $selectoptions = array();
                foreach ($answerlist as $ans) {
                    $selectoptions[s($ans)] = s($ans);
                }

                // Display.
                $option = 0;
                foreach ($question->options->subquestions as $subquestion) {
                    // Build drop down for answers.
                    $questiontext = $this->repchar( $subquestion->questiontext );
                    if ($questiontext != '') {
                        $dropdown = html_writer::label(get_string('answer', 'qtype_match', $option+1), 'quest_'.$id.'_'.$option,
                                false, array('class' => 'accesshide'));
                        $dropdown .= html_writer::select($selectoptions, "quest_{$id}_{$option}", '', false,
                                array('id' => "quest_{$id}_{$option}"));
                        $expout .= html_writer::tag('li', $questiontext);
                        $expout .= $dropdown;
                        $option++;
                    }
                }
                $expout .= html_writer::end_tag('ul');
                break;
            case 'description':
                break;
            case 'multianswer':
            default:
        		
                $expout .= "<!-- export of {$question->qtype} type is not supported  -->\n";
        }
		$expout .= "</tr>";
        return $expout;
    }

    protected function presave_process($body) {
        // Convert to pdf
		$pdf_file = $this->pdf->Output('questions.pdf', 's');
        return $pdf_file;
    }
    
    private function get_pdf_generator_instance() {
        global $CFG;

        require_once $CFG->libdir . "/tcpdf/tcpdf.php";
        $fontpath = $CFG->dirroot . "/question/format/xhtml/fonts/OpenSans-Regular.ttf";
    
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetCellPadding(0);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $fontname = TCPDF_FONTS::addTTFfont($fontpath, 'TrueTypeUnicode', '', 32);
        $pdf->SetFont($fontname, '', 11, '', false);
        // add a page
        $pdf->AddPage();

        return $pdf;
    }

    private function tab() {
        return "    ";
    }

    public function export_file_extension() {
        return '.pdf';
    }
}
