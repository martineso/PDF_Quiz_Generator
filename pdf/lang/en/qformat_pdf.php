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
 * Strings for component 'qformat_pdf', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qformat_pdf
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['invalidxml'] = 'Invalid PDF file - string expected (use CDATA?)';
$string['pluginname'] = 'Moodle PDF format';
$string['pluginname_help'] = 'This is a Moodle-specific format for importing and exporting questions.';
$string['pluginname_link'] = 'qformat/xml';
$string['truefalseimporterror'] = '<b>Warning</b>: The true/false question \'{$a->questiontext}\' could not be imported properly. It was not clear whether the correct answer is true or false. The question has been imported assuming that the answer is \'{$a->answer}\'. If this is not correct, you will need to edit the question.';
$string['unsupportedexport'] = 'Question type {$a} is not supported by XML export';
$string['xmlimportnoname'] = 'Missing question name in PDF file';
$string['xmlimportnoquestion'] = 'Missing question text in PDF file';
$string['xmltypeunsupported'] = 'Question type {$a} is not supported by PDF import';
