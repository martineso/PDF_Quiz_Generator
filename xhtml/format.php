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

        // format the question name and text
        switch($question->qtype) {
            case 'truefalse':
            case 'shortanswer':
            case 'numerical':
                $this->write_question_name($question);
                $expout .= $this->tab() . strip_tags($question->questiontext); // the text of the question
                $expout .= "\n\n";
                break;
            case 'multichoice':
            case 'match':
                $this->write_question_name($question);
                $expout .= $this->tab() . strip_tags($question->questiontext); // the text of the question
                break;
            case 'description':
                break;
            case 'multianswer':
                break;
            case 'calculated':
                break;
            case 'calculatedmulti':
                break;
            case 'calculatedsimple':
                break;
            case 'essay':
                break;
            case 'gapselect':
                break;
            // for all unsupported question types add an HTML comment (just in case) and return nothing
            default:
                $expout .= "<!-- export of {$question->qtype} type is not supported  -->\n";
                $this->pdf->WriteHTML($expout, true, false, true, false, '');
                return '';

        }

        $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
        $expout = "";

        // Selection depends on question type.
        switch($question->qtype) {
            case 'truefalse':
                $sttrue = get_string('true', 'qtype_truefalse');
                $stfalse = get_string('false', 'qtype_truefalse');
                $expout .= $this->tab() . $sttrue . $this->tab();
                $expout .= $this->tab() . $stfalse;
                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;
            case 'multichoice':
                $expout .= html_writer::start_tag('ol', array('class' => 'match', 'style' => 'list-style-type:lower-alpha'));
                foreach ($question->options->answers as $answer) {
                    $answertext = $this->repchar($answer->answer);
                    $expout .= html_writer::tag('li', $answertext);
                }
                $expout .= html_writer::end_tag('ol');
                $this->pdf->WriteHTML($expout, false, false, true, false, '');
                break;
            case 'shortanswer':
            case 'numerical':
                $expout .= $this->tab() . '_______________________________________________________________________________'; // ????
                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;
            case 'match':
                $expout .= html_writer::start_tag('ol', array('class' => 'match'));

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
                foreach ($question->options->subquestions as $subquestion) {
                    // Build drop down for answers.
                    $questiontext = $this->repchar( $subquestion->questiontext );
                    if ($questiontext != '') {
                        $expout .= html_writer::tag('li', $questiontext . '___'); // ????????
                    }
                }
                $expout .= html_writer::end_tag('ol');

                $expout .= html_writer::start_tag('ol', array('class' => 'answers', 'style' => 'list-style-type:lower-alpha'));
                foreach ($selectoptions as $opt) {
                    $expout .= html_writer::tag('li', $this->tab() . $opt);
                }
                $expout .= html_writer::end_tag('ol');
                $this->pdf->WriteHTML($expout, false, false, true, false, '');
                break;
            case 'description':
                break;
            case 'multianswer':
                break;
            case 'calculated':
                break;
            case 'calculatedmulti':
                break;
            case 'calculatedsimple':
                break;
            case 'essay':
                break;
            case 'gapselect':
                break;
            default:
                $expout .= "<!-- export of {$question->qtype} type is not supported  -->\n";
        }
		// $expout .= "</tr>";
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

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set up the font
        $fontpath = $CFG->dirroot . "/question/format/xhtml/fonts/OpenSans-Regular.ttf";
        global $fontname;
        $fontname = TCPDF_FONTS::addTTFfont($fontpath, 'TrueTypeUnicode', '', 32);
        $pdf->SetFont($fontname, '', 11, '', false);

        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetCellPadding(0);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // add a page
        $pdf->AddPage();

        return $pdf;
    }

    private function tab() {
        return "    ";
    }

    private function gap_between_questions() {
        return "\n\n\n\n";
    }

    private function write_question_name($question) {
      $text = "";
      $text .= $question->name . ":\n";  // the "name" of the question
      $this->pdf->SetFont($fontname, 'B', 13);
      $this->pdf->Write(5, $text, '', 0, 'L', true, 0, false, false, 0);
      $this->pdf->SetFont($fontname, '', 11);
    }

    public function export_file_extension() {
        return '.pdf';
    }
}
