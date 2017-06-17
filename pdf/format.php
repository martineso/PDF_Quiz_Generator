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
 * PDF question exporter.
 *
 * @package    qformat_pdf
 * @copyright  2017 Martin Kontilov, Boyan Kushlev, Simeon Vasilev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * PDF question exporter.
 *
 * Exports questions from a question bank in a certain category
 * to a PDF file 
 *
 * @copyright  2005 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_pdf extends qformat_default {

    private $pdf = null;
    private $fonts = array();
    private $q_count = 1;
    private $alphabet = array();
    private $q_matches = array();
    private static $not_supported;



    public function __construct() {
        $this->pdf = $this->get_pdf_generator_instance();
        $this->alphabet = range('a', 'z'); 
        self::$not_supported = array();
	 
    	// add Name: and Date: <current date> at the top of the page
    	$student_name = get_string("student_name", "qformat_pdf");
        $date = get_string("date", "qformat_pdf");
        $this->pdf->Write(5, $student_name, '', 0, 'L', false, 0, false, false, 0); //ln=false to prevent going on a new line
        $this->pdf->Write(5, $date, '', 0, 'R', true, 0, false, false, 0);
        $this->pdf->Write(5, $this->gap_between_questions(), '', 0, true, 'L', false, 0, false, false, 0);
        $this->pdf->Write(5, $this->gap_between_questions(), '', 0, true, 'L', false, 0, false, false, 0);
    }

    public function provide_export() {
        return true;
    }

    protected function writequestion($question) {
        global $OUTPUT;
        // Turns question into string.
        // Question reflects database fields for general question and specific to type.

        // If a category switch, just ignore.
        if ($question->qtype=='category') {
            return '';
        }


        // Initialize variables.
        $expout = "";
        $id = $question->id;
        static $matches;
        // Reset columns to default
        $this->pdf->resetColumns();

        // ==================================
        // Format the question name and text.
        // This is the header of the question.
        switch($question->qtype) {
            case 'truefalse':
            case 'shortanswer':
            case 'numerical':
            case 'essay':
            case 'multichoice':
            case 'match':
                $this->write_question_index($this->q_count);
                $this->q_count++; // increment the index
                $expout .= strip_tags($question->questiontext); // the text of the question
                break;
            case 'calculated':
            case 'calculatedmulti':
            case 'calculatedsimple':
                
                $this->write_question_index($this->q_count);
                $this->q_count++; // increment the index
                $expout .= strip_tags($question->questiontext);

                $this->q_matches = $this->get_matches_array($question, $expout);
                foreach ($this->q_matches as $match) {
                    $expout = str_replace($match['raw'], $match['value'], $expout);
                }

                $expout = str_replace('&nbsp;', ' ', $expout);

                break;

            case 'gapselect':
                $this->write_question_index($this->q_count);
                $this->q_count++; // increment the index

                $text = $this->replace_placeholders_gapselect_q($question->questiontext);
                
                $expout .= strip_tags($text) . "\n";
                $expout = str_replace('&nbsp;', ' ', $expout);
                break;
            case 'description':
                break;

            // Skip as the next switch statement will deal with unsupported question types.
            default:
                break;
        }

        $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
        // Set a margin between the question header and the question's body
        $this->pdf->Ln(2);

        // ===============================================================
        // Format the body of the question.
        // In most cases these are the possible answers for test questions
        // or empty space for open- answer questions.

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
            case 'calculated';
            case 'calculatedsimple':

                $expout .= $this->tab() . str_repeat('_', 100); // Writes 100 undeline chars
                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;
            case 'match':
               
                $l_column = "";
                $r_column = "";

                $a_bet_counter = 0;
                $subq_counter = 1;
                foreach ($question->options->subquestions as $subquestion) {
                    // If we have an empty string, ignore
                    if(!empty($subquestion->questiontext)) {
                        $l_column .= $this->tab() . $subq_counter . '. ' . strip_tags($subquestion->questiontext) . ' ___' . "\n";
                        $subq_counter++;
                    }
                    if(!empty($subquestion->answertext)) {
                        $r_column .= $this->alphabet[$a_bet_counter] . ') ' . strip_tags($subquestion->answertext) . "\n";
                        $a_bet_counter++;
                    }
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
            case 'calculatedmulti':
                $index = 0;
                $expout = "";
                $answer_str = "";
                
                foreach ($question->options->answers as $answer) {
                    $answer_str = strip_tags($answer->answer);
                    
                    foreach ($this->q_matches as $match) {
                        $answer_str = str_replace($match['raw'], $match['value'], $answer_str);
                    }

                    $answer_str = str_replace('&nbsp;', ' ', $answer_str);
                    $expout .= $this->tab() . $this->alphabet[$index % 26] . '. ' . $answer_str . "\n";
                    $index++;
                }

                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;
            case 'description':
                
                $expout .= $this->tab() . strip_tags($question->questiontext) . "\n";
                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;

            case 'essay':
                
                $lines = $question->options->responsefieldlines;
                for($i = $lines; $i > 0; $i--) {
                    $expout .= "\n"; 
                }
                
                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;
            case 'gapselect':  
                $counter = 1;
                foreach ($question->options->answers as $answer) {
                    $expout .= $this->tab() . $counter . '. ' . strip_tags($answer->answer) . "\n";
                    $counter++;
                }

                $expout .= $this->gap_between_questions();
                $this->pdf->Write(5, $expout, '', 0, 'L', true, 0, false, false, 0);
                break;
            case 'multianswer':
                break;
                // If the question is not supported put it in the array with not supported questions
                // which will be displayed as an error message prior to the pdf file being exported.
            default:
                $expout = "- " . strip_tags($question->name);
                array_push(self::$not_supported, strip_tags($expout));
                break;
        }

        return $expout;
    }

    protected function presave_process($body) {
        /*
            If there are elements in the not_supported array - display an error.
            Check the /lang/en/qformat_pdf.php file for the error string!
        */
        if(!empty(self::$not_supported)){

            global $PAGE;
            $courseid = $PAGE->course->id;
            $return_url = new moodle_url("/question/export.php?courseid={$courseid}");

            $a = $this->get_not_supported_str(self::$not_supported);
            print_error('not_supported', 'qformat_pdf', $return_url, $a);
        }

        // If no errors are encountered continue with file download.
        // Convert to pdf.
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

    private function write_question_index($index) {
      $text = "";
      // The index of the question i.e. 
      // 1. Question text
      $text .= $index . '. ';
      
      $this->pdf->SetFont($this->fonts['bold'], 'B', 11);
      $this->pdf->Write(5, $text, '', 0, 'L', false, 0, false, false, 0, '');
      $this->pdf->SetFont($this->fonts['regular'], '', 11);
    }

    private function replace_placeholders_gapselect_q($text) {
        $regex = '(\[+[\d]+\]+)';

        $out = preg_replace($regex, " ___ ", $text);
        return $out;
    }
    private function get_matches_array($question, $expout) {
        $matches = array();
        $temp_arr = array();
        $moodle_placehodler_regex = '(\{[a-zA-Z]+\})';
        $q_data_sets = $question->options->datasets;

        preg_match_all($moodle_placehodler_regex, $expout, $matches);

        foreach ($matches[0] as $key => $match) {
            // trim the placeholder from the brackets
            $temp = (string) trim($match, '{}');
            $temp_arr[$temp]['clean'] = $temp;
            $temp_arr[$temp]['raw'] = $match;
            $temp_arr[$temp]['value'] = '';
        }

        $matches = $temp_arr;
        // Clear out the array
        $temp_arr = array();

        foreach ($q_data_sets as $set) {
            foreach ($matches as $match) {
                if($set->name == $match['clean']) {
                    foreach ($set->items as $item) {
                        $temp_arr[] = strip_tags($item->value);
                    }
                    // Shuffle the array and pick a random element
                    shuffle($temp_arr);
                    $value = array_rand($temp_arr);
                    $value = $temp_arr[$value];
                    $matches[$match['clean']]['value'] = (string)$value;
                    // Clear out the array
                    $temp_arr = array(); 
                }
            }
        }

        return $matches;
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
        $fontpath = $CFG->dirroot . "/question/format/pdf/fonts/OpenSans-Regular.ttf";
        $this->fonts['regular'] = TCPDF_FONTS::addTTFfont($fontpath, 'TrueTypeUnicode', '', 32);

        // Load the bold font
        $fontpath = $CFG->dirroot . "/question/format/pdf/fonts/OpenSans-Bold.ttf";
        $this->fonts['bold'] = TCPDF_FONTS::addTTFfont($fontpath, 'TrueTypeUnicode', '', 32);
    }

    /*  Print the question name for each question in the not_supported array of strings
        
    */
    private function get_not_supported_str($arr) {
        $str = "";
        foreach ($arr as $question_name) {
            $str .= "<strong>" . $question_name . "<br></strong> &#13;&#10;";
        }

        return $str;
    }

    public function export_file_extension() {
        return '.pdf';
    }
}

//echo "<pre>"; print_r($question); echo "</pre>"; die;
