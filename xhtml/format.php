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
    private $fonts = array();

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

        // Reset columns to default
        $this->pdf->resetColumns();
        // format the question name and text
        switch($question->qtype) {
            case 'truefalse':
            case 'shortanswer':
            case 'numerical':
                $this->write_question_name($question->name);
                $expout .= strip_tags($question->questiontext); // the text of the question
                break;
            case 'multichoice':
            case 'match':
                $this->write_question_name($question->name);
                $expout .= $this->tab() . strip_tags($question->questiontext);
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
        // Set a margin between the question header and the question's body
        $this->pdf->Ln(2);


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
                
                // echo "<pre>"; print_r($question); echo "</pre>"; die();
                $index = 1;
                foreach ($question->options->answers as $answer) {
                    $expout .= $this->tab() . $index . '. ' . strip_tags($answer->answer) . "\n";
                    $index++;
                }

                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;
            case 'shortanswer':
            case 'numerical':
                $expout .= $this->tab() . str_repeat('_', 100); // Writes 100 undeline chars
                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;
            case 'match':
               
                $l_column = "";
                $r_column = "";
                foreach ($question->options->subquestions as $subquestion) {
                    // If we have an empty string, ignore
                    if(empty($subquestion->questiontext)) {
                        continue;
                    }
                    $l_column .= $this->tab() . strip_tags($subquestion->questiontext) . "\n";
                    $r_column .= strip_tags($subquestion->answertext) . "\n";
                 }

                // Display
                $this->pdf->setEqualColumns(2, 100);
                // Write first column
                $this->pdf->selectColumn(0);
                $this->pdf->Write(5, $l_column, '', 0, 'L', false, 0, false, false, 0);

                // Write second column
                $this->pdf->selectColumn(1);
                $this->pdf->Write(5, $r_column, '', 0, true, 'L', false, 0, false, false, 0);

                $this->pdf->resetColumns();
                // A new line is required since the TCPDF lib does not reset the columns until
                // a new line is encountered
                $this->pdf->Write(5, $this->gap_between_questions(), '', 0, true, 'L', false, 0, false, false, 0);
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
        $this->load_fonts();
        $pdf->SetFont($this->fonts['regular'], '', 11, '', false);

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
        return "\n\n";
    }

    private function write_question_name($question_name) {
      $text = "";
      $text .= $question_name . "\n";  // the "name" of the question

      $this->pdf->SetFont($this->fonts['bold'], 'B', 11);
      $this->pdf->Write(5, $text, '', 0, 'L', false, 0, false, false, 0, '');
      $this->pdf->SetFont($this->fonts['regular'], '', 11);
    }
   
    /*
        Loads the fonts array with the names of the two Open Sans fonts i.e
        Array
        (
            [regular] => opensans
            [bold] => opensansb
        )
        
        Usage example:
        $this->pdf->SetFont($this->fonts['regular'], '', 11);

    */
    private function load_fonts() {
        global $CFG;

        $fontpath = "";

        // load the regular font
        $fontpath = $CFG->dirroot . "/question/format/xhtml/fonts/OpenSans-Regular.ttf";
        $this->fonts['regular'] = TCPDF_FONTS::addTTFfont($fontpath, 'TrueTypeUnicode', '', 32);

        // Load the bold font
        $fontpath = $CFG->dirroot . "/question/format/xhtml/fonts/OpenSans-Bold.ttf";
        $this->fonts['bold'] = TCPDF_FONTS::addTTFfont($fontpath, 'TrueTypeUnicode', '', 32);
    }



    public function export_file_extension() {
        return '.pdf';
    }
}
