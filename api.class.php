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
 * ws_helper.php - Contains Plagiarism plugin helper methods for communicate with the web service.
 *
 * @since 2.0
 * @package    plagiarism_compilatio
 * @subpackage plagiarism
 * @author     Compilatio <support@compilatio.net>
 * @copyright  2017 Compilatio.net {@link https://www.compilatio.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * compilatioservice class
 * @copyright  2017 Compilatio.net {@link https://www.compilatio.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compilatioservice {

    /**
     * Clé d'identification pour le compte Compilatio
     * @var string
     */
    public $key;

    /**
     * URL du webservice REST
     * @var string
     */
    public $urlrest;

    /**
     * Constructor : Create the connexion with the webservice
     * MODIF 2009-03-19: passage des paramètres
     * MODIF 2017-06-23: MAJ PHP 7
     * MODIF 2019-04-08: passage API SOAP à API REST
     *
     * @param   string  $key        API key
     * @param   string  $urlrest    URL of the REST webservice
     */
    public function __construct($key, $urlrest) {

        $this->key = null;
        $this->urlrest = $urlrest;

        if (!empty($key)) {
            $this->key = $key;
        } else {
            return "API key not available";
        }
    }

    /* API Functions -------------------------------------------------------------*/

    /**
     * Load document on Compilation account
     *
     * @param   string  $title          Document's title
     * @param   string  $filename       Filename
     * @param   string  $content        Document's content
     * @return  string                  Return the document's ID, an error message otherwise
     */
    public function send_doc($title, $filename, $content) {

        $validtitle = $this->validatestringparameter($title, "title");
        if ($validtitle != "Valid string") {
            return $validtitle;
        }

        $validfilename = $this->validatestringparameter($filename, "filename");
        if ($validfilename != "Valid string") {
            return $validfilename;
        }

        $validcontent = $this->validatestringparameter($content, "content");
        if ($validcontent != "Valid string") {
            return $validcontent;
        }

        $handle = fopen('/tmp/' . date('Y-m-d H:i:s') . ".txt", 'w+');
        fwrite($handle, $content);

        $endpoint = "/api/document/";
        $params = array(
            'file' => new CurlFile(realpath(stream_get_meta_data($handle)['uri'])),
            'filename' => $filename,
            'title' => $title
        );

        $response = json_decode($this->curlpostupload($endpoint, $params));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function send_doc() : request response's status not found";
        }

        if ($response->status->code == 201) {
            return $response->data->document->id;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Get back information about a document
     *
     * @param  string   $compihash  External ID of the document
     * @return mixed               Return the document in an object if succeed, an error message otherwise
     */
    public function get_doc($compihash) {

        $validcompihash = $this->validatestringparameter($compihash, "document's ID");
        if ($validcompihash != "Valid string") {
            return $validcompihash;
        }

        $endpoint = "/api/document/".$compihash;
        $response = json_decode($this->curlget($endpoint));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function get_doc() : request response's status not found";
        }

        if ($response->status->code == 200) {

            $document = $response->data->document;

            $documentproperties = new \stdClass();
            $documentproperties->idDocument = $document->id;
            $documentproperties->title = "";
            $documentproperties->description = "";
            $documentproperties->filename = $document->filename;
            $documentproperties->filetype = explode('.', $document->filename)[1];
            $documentproperties->date = $document->upload_date;
            $documentproperties->textBeginning = '';
            $documentproperties->textLength = $document->length;
            $documentproperties->filesize = 0;
            $documentproperties->idFolder = "";
            $documentproperties->parts = 0;
            $documentproperties->Shortcut = "";
            $documentproperties->idParent = $document->id;
            $documentproperties->wordCount = $document->words_count;
            $documentstatus = new \stdClass();
            if (isset($document->analyses)) {
                $analysisbson = (array) $document->analyses;
            } else {
                $document->analyses = array();
            }

            // Status : ANALYSE_NOT_STARTED...
            if (!isset($analysisbson['anasim'])) {
                $documentstatus->cost = "1";
                $documentstatus->status = "ANALYSE_NOT_STARTED";
                $documentstatus->indice = "";
                $documentstatus->progression = "";
                $documentstatus->startDate = "";
                $documentstatus->finishDate = "";
            } else {
                $analysis = $analysisbson['anasim'];

                if ($analysis->state === 'waiting') {
                    // Status : ANALYSE_IN_QUEUE --> waiting...
                    $documentstatus->cost = "1";
                    $documentstatus->status = "ANALYSE_IN_QUEUE";
                    $documentstatus->indice = "";
                    $documentstatus->progression = "";
                    $documentstatus->startDate = "";
                    $documentstatus->finishDate = "";
                } else if ($analysis->state === 'running' || $analysis->state === 'degraded') {
                    // Status : ANALYSE_PROCESSING --> running...
                    $documentstatus->cost = "1";
                    $documentstatus->status = "ANALYSE_PROCESSING";
                    $documentstatus->indice = "";
                    $documentstatus->progression = "";
                    $documentstatus->startDate = $analysis->metrics->start;
                    $documentstatus->finishDate = "";
                } else if ($analysis->state === 'crashed' || $analysis->state === 'aborted' || $analysis->state === 'canceled') {
                    // Status : ANALYSE_CRASHED --> stopped...
                    $documentstatus->cost = "0";
                    $documentstatus->status = "ANALYSE_CRASHED";
                    $documentstatus->indice = "";
                    $documentstatus->progression = "";
                    $documentstatus->startDate = $analysis->metrics->start;
                    $documentstatus->finishDate = "";
                } else if ($analysis->state === 'finished') {
                    // Status : ANALYSE_COMPLETE --> finished...
                    $documentstatus->cost = "1";
                    $documentstatus->status = "ANALYSE_COMPLETE";
                    $reportbson = (array) $document->light_reports;
                    $lightreports = $reportbson['anasim'];
                    $documentstatus->indice = (string) $lightreports->plagiarism_percent;
                    $documentstatus->progression = "100";
                    $documentstatus->startDate = $analysis->metrics->start;
                    $documentstatus->finishDate = $analysis->metrics->end;
                }
            }
            $compilatiodocument = new \stdClass();
            $compilatiodocument->documentProperties = $documentproperties;
            $compilatiodocument->documentStatus = $documentstatus;

            return $compilatiodocument;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Get back the URL of a report document
     *
     * @param  string $compihash External ID of the document
     * @return string            Return the URL if succeed, an error message otherwise
     */
    public function get_report_url($compihash) {

        $validcompihash = $this->validatestringparameter($compihash, "document's ID");
        if ($validcompihash != "Valid string") {
            return $validcompihash;
        }

        $endpoint = "/api/document/".$compihash."/report-url";
        $response = json_decode($this->curlget($endpoint));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function get_report_url() : request response's status not found";
        }

        if ($response->status->code == 200) {
            return $response->data->url;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Delete a document on the Compilatio account
     *
     * @param  string   $compihash  External ID of the document
     * @return boolean              Return true if succeed, an error message otherwise
     */
    public function del_doc($compihash) {

        $validcompihash = $this->validatestringparameter($compihash, "document's ID");
        if ($validcompihash != "Valid string") {
            return $validcompihash;
        }

        $endpoint = "/api/document/".$compihash;
        $response = json_decode($this->curldelete($endpoint));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function del_doc() : request response's status not found";
        }

        if ($response->status->code == 200) {
            return true;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Start an analyse of a document
     *
     * @param  string   $compihash  External ID of the document
     * @return mixed                Return true if succeed, an error message otherwise
     */
    public function start_analyse($compihash) {

        $validcompihash = $this->validatestringparameter($compihash, "document's ID");
        if ($validcompihash != "Valid string") {
            return $validcompihash;
        }

        $endpoint = "/api/analysis/";
        $params = array(
            'doc_id' => $compihash,
            'recipe_name' => 'anasim'
        );

        $response = json_decode($this->curlpost($endpoint, json_encode($params)));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function start_analyse() : request response's status not found";
        }

        if ($response->status->code == 201) {
            return true;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Get back Compilatio account's quotas
     *
     * @return  array   Informations about quotas
     */
    public function get_quotas() {

        // Méthode pas encore codée dans l'API REST...

        $accountquotas = array(
            "quotas" => array(
                "space" => 100000000,
                "freespace" => 100000000,
                "usedSpace" => 0,
                "credits" => 100000,
                "remainingCredits" => 100000,
                "usedCredits" => 0
            )
        );

        return $accountquotas;
    }

    /**
     * Get expiration date of an account
     *
     * @return mixed Return the expiration date if succeed, false otherwise
     */
    public function get_account_expiration_date() {

        // Cette fonction semble renvoyer une erreur 404... Erreur dans le endpoint ?
        $endpoint = "/api/subscription/api-key";
        $response = json_decode($this->curlget($endpoint));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function get_account_expiration_date() : request response's status not found";
        }

        if ($response->status->code == 200) {
            return $response->data->subscription->validity_period->end;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Post Moodle Configuration to Compilatio
     *
     * @param  string   $releasephp     PHP version
     * @param  string   $releasemoodle  Moodle version
     * @param  string   $releaseplugin  Plugin version
     * @param  string   $language       Language
     * @param  int      $cronfrequency  CRON frequency
     * @return mixed                    Return true if succeed, an error message otherwise
     */
    public function post_configuration($releasephp,
                                       $releasemoodle,
                                       $releaseplugin,
                                       $language,
                                       $cronfrequency) {

        $validreleasephp = $this->validatestringparameter($releasephp, "PHP version");
        if ($validreleasephp != "Valid string") {
            return $validreleasephp;
        }

        $validreleasemoodle = $this->validatestringparameter($releasemoodle, "Moodle version");
        if ($validreleasemoodle != "Valid string") {
            return $validreleasemoodle;
        }

        $validreleaseplugin = $this->validatestringparameter($releaseplugin, "Plugin version");
        if ($validreleaseplugin != "Valid string") {
            return $validreleaseplugin;
        }

        $validlanguage = $this->validatestringparameter($language, "Language");
        if ($validlanguage != "Valid string") {
            return $validlanguage;
        }

        $validcronfrequency = $this->validateintparameter($cronfrequency, "CRON frequency");
        if ($validcronfrequency != "Valid int") {
            return $validcronfrequency;
        }

        $endpoint = "/api/moodle-configuration/add";
        $params = array(
            'php_version' => $releasephp,
            'moodle_version' => $releasemoodle,
            'compilatio_plugin_version' => $releaseplugin,
            'language' => $language,
            'cron_frequency' => $cronfrequency
        );

        $response = json_decode($this->curlpost($endpoint, json_encode($params)));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function post_configuration() : request response's status not found";
        }

        if ($response->status->code == 200) {
            return true;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Get a list of the current Compilatio news.
     *
     * @return array    serviceInfos    Return an array of news, an error message otherwise
     */
    public function get_technical_news() {

        $endpoint = "/api/service-info/list?limit=5";
        $response = json_decode($this->curlget($endpoint));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function get_technical_news() : request response's status not found";
        }

        if ($response->status->code == 200) {

            $serviceinfos = [];
            $languages = ['fr', 'es', 'en', 'it', 'de'];

            foreach ($response->data->service_infos as $info) {

                $serviceinfo = new \stdClass();
                $serviceinfo->id = $info->id;

                switch ($info->level) {
                    case '1':
                        $serviceinfo->type = 'info';
                        break;
                    case '4':
                        $serviceinfo->type = 'critical';
                        break;
                    default:
                        $serviceinfo->type = 'warning';
                        break;
                }

                foreach ($languages as $language) {
                    $serviceinfo->{'message_' . $language} = $info->message->{$language};
                }

                $serviceinfo->begin_display_on = strtotime($info->metrics->start);
                $serviceinfo->end_display_on = strtotime($info->metrics->end);

                array_push($serviceinfos, $serviceinfo);
            }

            return $serviceinfos;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Get the maximum size authorized by Compilatio.
     *
     * @return array    Return an array of the max size
     */
    public function get_allowed_file_max_size() {

        // Fonction pas encore codée dans l'API REST -- On renvoie un ce tableau-ci de toutes manières.
        $sizemo = 20;
        $allowedfilemaxsize = [
            'bits' => $sizemo * 10 ** 6 * 8,
            'octets' => $sizemo * 10 ** 6,
            'Ko' => $sizemo * 10 ** 3,
            'Mo' => $sizemo
        ];

        return $allowedfilemaxsize;
    }

    /**
     * Get a list of the allowed file types by Compilatio.
     *
     * @return  array   Return an array of the different allowed file types
     */
    public function get_allowed_file_types() {

        $endpoint = "/public_api/file/allowed-extensions";
        $response = json_decode($this->curlget($endpoint), true);

        $extensionnamemapping = [
            "doc" => "Microsoft Word",
            "docx" => "Microsoft Word",
            "xls" => "Microsoft Excel",
            "xlsx" => "Microsoft Excel",
            "ppt" => "Microsoft Powerpoint",
            "pptx" => "Microsoft Powerpoint",

            "xml" => "XML File",
            "xhtml" => "Web Page",
            "htm" => "Web Page",
            "html" => "Web Page",

            "csv" => "Comma Separated Values File",

            "odt" => "OpenDocument Text",
            "ods" => "OpenDocument Sheet",
            "odp" => "OpenDocument Presentation",

            "pdf" => "Adobe Portable Document File",
            "rtf" => "Rich Text File",
            "txt" => "Plain Text File",
            "tex" => "LaTeX source File",
        ];

        $list = [];

        foreach ($response as $extension => $mimecontenttypes) {
            foreach ($mimecontenttypes as $mimecontenttype) {
                $filetype = [];
                $filetype['type'] = $extension;
                $filetype['title'] = $extensionnamemapping[$extension];
                $filetype['mimetype'] = $mimecontenttype;
                $list[] = $filetype;
            }
        }

        sort($list);

        return $list;
    }

    /**
     * Get back the indexing state of a document
     *
     * @param   string      $compid     Document ID
     * @return  mixed                   Return the indexing state if succeed, an error message otherwise
     */
    public function get_indexing_state($compid) {

        $validcompid = $this->validatestringparameter($compid, "document's ID");
        if ($validcompid != "Valid string") {
            return $validcompid;
        }

        $endpoint = "/api/document/".$compid;
        $response = json_decode($this->curlget($endpoint));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function get_indexing_state() : request response's status not found";
        }

        if ($response->status->code == 200) {
            return $response->data->document->indexed;
        } else {
            return $response->status->message;
        }
    }

    /**
     * Set the indexing state of a document
     *
     * @param   string  $compid     Document ID
     * @param   bool    $indexed    Indexing state
     * @return  mixed               Return true if succeed, an error message otherwise
     */
    public function set_indexing_state($compid, $indexed) {

        $validcompid = $this->validatestringparameter($compid, "document's ID");
        if ($validcompid != "Valid string") {
            return $validcompid;
        }

        $validindexes = array("0", "1", "false", "true");
        if (!in_array($indexed, $validindexes)) {
            return "Invalid parameter : indexing state is not a boolean";
        }

        $endpoint = "/api/document/".$compid;
        $params = array(
            'indexed' => $indexed
        );
        $response = json_decode($this->curlpatch($endpoint, json_encode($params)));

        if (!isset($response->status->code, $response->status->message)) {
            return "Error in function set_indexing_state() : request response's status not found";
        }

        if ($response->status->code == 200) {
            return true;
        } else {
            return $response->status->message;
        }
    }

    // Fonctions de validation de paramètres.

    /**
     * Verify is the parameter is a valid string (defined, not empty and a string type)
     *
     * @param   mixed   $var    The parameter to verify (usually a string)
     * @param   string  $name   The name of the parameter (to have a nice message error just in case)
     * @return  string          Return a message
     */
    private function validatestringparameter($var, $name) {

        $errormessage = "Invalid parameter : '".$name."' is ";
        if (!isset($var)) {
            return $errormessage."not defined";
        } else if (empty($var)) {
            return $errormessage."empty";
        } else if (!is_string($var)) {
            return $errormessage."not a string";
        } else {
            return "Valid string";
        }
    }

    /**
     * Verify is the parameter is a valid int (defined, not empty and an int type)
     *
     * @param   mixed   $var    The parameter to verify (usually an int)
     * @param   string  $name   The name of the parameter (to have a nice message error just in case)
     * @return  string          Return a message
     */
    private function validateintparameter($var, $name) {

        $errormessage = "Invalid parameter : '".$name."' is ";
        if (!isset($var)) {
            return $errormessage."not defined";
        } else if (empty($var)) {
            return $errormessage."empty";
        } else if (!is_int($var)) {
            return $errormessage."not an int";
        } else {
            return "Valid int";
        }
    }

    // Fonctions cURL.

    /**
     * Send a GET request with cURL
     *
     * @param   string  $endpoint   URL of the ressource
     * @param   string  $params     Parameters of the request
     * @return  string              Return the result of the request
     */
    private function curlget($endpoint, $params="") {

        $url = $this->urlrest.$endpoint."?".$params;
        $token = $this->key;

        $ch = curl_init();

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('X-Auth-Token: '.$token, 'Content-Type: application/json'),
        );

        curl_setopt_array($ch, $curloptions);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Send a POST request with cURL
     *
     * @param   string  $endpoint   URL of the ressource
     * @param   string  $load       Parameters of the request
     * @return  string              Return the result of the request
     */
    private function curlpost($endpoint, $load) {

        $url = $this->urlrest.$endpoint;
        $token = $this->key;

        $ch = curl_init();

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('X-Auth-Token: '.$token, 'Content-Type: application/json'),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $load,
        );

        curl_setopt_array($ch, $curloptions);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Send a POST request with cURL for uploading a document
     *
     * @param   string  $endpoint   URL of the ressource
     * @param   string  $load       Parameters of the request
     * @return  string              Return the result of the request
     */
    private function curlpostupload($endpoint, $load) {

        $url = $this->urlrest.$endpoint;
        $token = $this->key;

        $ch = curl_init();

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('X-Auth-Token: '.$token),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $load,
        );

        curl_setopt_array($ch, $curloptions);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Send a PATCH request with cURL
     *
     * @param   string  $endpoint   URL of the ressource
     * @param   string  $load       Parameters of the request
     * @return  string              Return the result of the request
     */
    private function curlpatch($endpoint, $load) {

        $url = $this->urlrest.$endpoint;
        $token = $this->key;

        $ch = curl_init();

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('X-Auth-Token: '.$token, 'Content-Type: application/json'),
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $load,
        );

        curl_setopt_array($ch, $curloptions);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * Send a DELETE request with cURL
     *
     * @param   string  $endpoint   URL of the ressource
     * @param   string  $params     Parameters of the request
     * @return  string              Return the result of the request
     */
    private function curldelete($endpoint, $params="") {

        $url = $this->urlrest.$endpoint."?".$params;
        $token = $this->key;

        $ch = curl_init();

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array('X-Auth-Token: '.$token, 'Content-Type: application/json'),
        );

        curl_setopt_array($ch, $curloptions);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}