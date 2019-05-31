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
 * api_class_test.php - Test class of the new REST API calls
 *
 * @package    plagiarism_compilatio
 * @copyright  2019 Compilatio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/plagiarism/compilatio/api.class.php');

/**
 * Class api_class_test
 *
 * @copyright  2019 Compilatio.net {@link https://www.compilatio.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_class_test extends advanced_testcase {

    /**
     * Clé de l'API
     * @var string
     */
    const API_KEY = "646022b3de50939edccc46f9009924651022036a";

    /**
     * URL de l'API REST
     * @var string
     */
    const API_URL = "https://beta.compilatio.net";

    /**
     * ID d'un document déjà inséré en base
     * @var string
     */
    const ID_DOC = "a1de867a6fca9852e5995ff9c46d809cbedf083d";

    /**
     * URL du rapport d'un document déjà analysé
     * @var string
     */
    const DOC_REPORT_URL = "https://beta.compilatio.net/api/report/redirect/eb9be05b3345ac4542de1fcfbb93e41420fd96d5";

    // Différentes instances de compilatioservice pour les tests...
    /**
     * @var \compilatioservice  $compilatio             Classe compilatioservice valide
     */
    protected static $compilatio;

    /**
     * @var \compilatioservice  $compilatioinvkey1      Classe compilatio avec une clé d'API invalide
     */
    protected static $compilatioinvkey1;
    /**
     * @var \compilatioservice  $compilatioinvkey2      Classe compilatio avec une clé d'API invalide
     */
    protected static $compilatioinvkey2;

    /**
     * @var \compilatioservice  $compilatioauthreq1     Classe compilatio avec une clé d'API non spécifiée
     */
    protected static $compilatioauthreq1;
    /**
     * @var \compilatioservice  $compilatioauthreq2     Classe compilatio avec une clé d'API non spécifiée
     */
    protected static $compilatioauthreq2;

    /**
     * @var array   $senddocvalues  Variables pour la fonction send_doc
     */
    private $senddocvalues = array(
        'title' => 'Title--UnitTest',
        'filename' => 'Filename--UnitTest.txt',
        'content' => 'Test d\'un upload de fichier avec l\'API REST -- Test Unitaire'
    );

    /**
     * @var array   $postconfigurationvalues    Variables pour la fonction post_configuration
     */
    private $postconfigurationvalues = array(
        'releasephp' => '7.0.33-0ubuntu0.16.04.3',
        'releasemoodle' => '3.6 (Build: 20181203)',
        'releaseplugin' => '2019030500',
        'language' => 'fr',
        'cronfrequency' => 1
    );

    /**
     * setUpBeforeClass() -> fonction lancée avant tous les tests
     * On instancie des 'compilatioservice' avec différentes clé d'API
     */
    public static function setUpBeforeClass() {

        self::$compilatio = new compilatioservice(self::API_KEY, self::API_URL);
        self::$compilatioinvkey1 = new compilatioservice("abcdef", self::API_URL);
        self::$compilatioinvkey2 = new compilatioservice(42, self::API_URL);
        self::$compilatioauthreq1 = new compilatioservice("", self::API_URL);
        self::$compilatioauthreq2 = new compilatioservice(null, self::API_URL);
    }

    // TESTS FONCTION GET_TECHNICAL_NEWS...

    /**
     * Test fonction get_technical_news -> attend un tableau avec des news
     */
    public function test_get_technical_news_ok() {

        $news = self::$compilatio->get_technical_news();

        $this->assertTrue(isset($news));
        $this->assertEquals('array', gettype($news));
    }

    /**
     * Test fonction get_technical_news -> attend une erreur (clé d'API invalide ou non spécifiée (authentification requise))
     */
    public function test_get_technical_news_errorsapikey() {

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->get_technical_news());
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->get_technical_news());

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->get_technical_news());
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->get_technical_news());
    }

    // TESTS FONCTION GET_ALLOWED_FILE_MAX_SIZE...

    /**
     * Test fonction get_allowed_file_max_size -> attend un tableau avec les tailles maximales
     */
    public function test_get_allowed_file_max_size_ok() {

        $size = self::$compilatio->get_allowed_file_max_size();

        $this->assertEquals('array', gettype($size));
        $this->assertNotCount(0, $size);
        $this->assertArrayHasKey("Ko", $size);
        $this->assertArrayHasKey("Mo", $size);
    }

    // TESTS FONCTION GET_ALLOWED_FILE_TYPES...

    /**
     * Test fonction get_allowed_file_types -> attend un tableau
     */
    public function test_get_allowed_file_types_ok() {

        $types = self::$compilatio->get_allowed_file_types();

        $this->assertEquals('array', gettype($types));
        $this->assertNotCount(0, $types);
        $this->assertCount(24, $types);

        $this->assertArrayHasKey('type', $types[0]);
        $this->assertArrayHasKey('title', $types[5]);
        $this->assertArrayHasKey('mimetype', $types[15]);

        $this->assertEquals('docx', $types[2]['type']);
        $this->assertEquals('xml', $types[23]['type']);
        $this->assertEquals('Web Page', $types[4]['title']);
        $this->assertEquals('application/pdf', $types[10]['mimetype']);
    }

    // TESTS FONCTION GET_QUOTAS...

    /**
     * Test fonction get_quotas -> attend un array
     */
    public function test_get_quotas_ok() {

        $arrayquotas = self::$compilatio->get_quotas();

        $this->assertEquals('array', gettype($arrayquotas));
        $this->assertNotCount(0, $arrayquotas);

        $quotas = $arrayquotas['quotas'];

        $this->assertEquals('array', gettype($quotas));
        $this->assertNotCount(0, $quotas);

        $this->assertArrayHasKey('space', $quotas);
        $this->assertArrayHasKey('freespace', $quotas);
        $this->assertArrayHasKey('usedSpace', $quotas);
        $this->assertArrayHasKey('credits', $quotas);
        $this->assertArrayHasKey('remainingCredits', $quotas);
        $this->assertArrayHasKey('usedCredits', $quotas);
    }

    // TESTS FONCTION POST_CONFIGURATION...

    /**
     * Test fonction post_configuration -> attend true
     */
    public function test_post_configuration_ok() {

        $releasephp = $this->postconfigurationvalues['releasephp'];
        $releasemoodle = $this->postconfigurationvalues['releasemoodle'];
        $releaseplugin = $this->postconfigurationvalues['releaseplugin'];
        $language = $this->postconfigurationvalues['language'];
        $cronfrequency = $this->postconfigurationvalues['cronfrequency'];

        $this->assertTrue(self::$compilatio->post_configuration($releasephp,
            $releasemoodle, $releasemoodle, $language, $cronfrequency));
    }

    /**
     * Test fonction post_configuration avec différents paramètres -> attend des erreurs selon les paramètres
     *
     * @param   string  $releasephp     PHP version
     * @param   string  $releasemoodle  Moodle version
     * @param   string  $releaseplugin  Plugin version
     * @param   string  $language       Language
     * @param   int     $cronfrequency  CRON frequency
     * @param   mixed   $expected       Result expected
     *
     * @dataProvider post_configuration_dataprovider
     */
    public function test_post_configuration_invalidparameters($releasephp,
        $releasemoodle, $releaseplugin, $language, $cronfrequency, $expected) {

        $this->assertEquals($expected, self::$compilatio->post_configuration($releasephp,
            $releasemoodle, $releaseplugin, $language, $cronfrequency));
    }

    /**
     * DataProvider de test_post_configuration_invalidparameters
     */
    public function post_configuration_dataprovider() {

        $releasephp = $this->postconfigurationvalues['releasephp'];
        $releasemoodle = $this->postconfigurationvalues['releasemoodle'];
        $releaseplugin = $this->postconfigurationvalues['releaseplugin'];
        $language = $this->postconfigurationvalues['language'];
        $cronfrequency = $this->postconfigurationvalues['cronfrequency'];

        return [
            'PHP version not defined'       => [null, $releasemoodle, $releaseplugin,
                $language, $cronfrequency, "Invalid parameter : 'PHP version' is not defined"],
            'PHP version empty'             => ["", $releasemoodle, $releaseplugin,
                $language, $cronfrequency, "Invalid parameter : 'PHP version' is empty"],
            'PHP version not a string'      => [42, $releasemoodle, $releaseplugin,
                $language, $cronfrequency, "Invalid parameter : 'PHP version' is not a string"],

            'Moodle version not defined'    => [$releasephp, null, $releaseplugin,
                $language, $cronfrequency, "Invalid parameter : 'Moodle version' is not defined"],
            'Moodle version empty'          => [$releasephp, "", $releaseplugin,
                $language, $cronfrequency, "Invalid parameter : 'Moodle version' is empty"],
            'Moodle version not a string'   => [$releasephp, 42, $releaseplugin,
                $language, $cronfrequency, "Invalid parameter : 'Moodle version' is not a string"],

            'Plugin version not defined'    => [$releasephp, $releasemoodle, null,
                $language, $cronfrequency, "Invalid parameter : 'Plugin version' is not defined"],
            'Plugin version empty'          => [$releasephp, $releasemoodle, "",
                $language, $cronfrequency, "Invalid parameter : 'Plugin version' is empty"],
            'Plugin version not a string'   => [$releasephp, $releasemoodle, 42,
                $language, $cronfrequency, "Invalid parameter : 'Plugin version' is not a string"],

            'Language not defined'          => [$releasephp, $releasemoodle, $releaseplugin,
                null, $cronfrequency, "Invalid parameter : 'Language' is not defined"],
            'Language empty'                => [$releasephp, $releasemoodle, $releaseplugin,
                "", $cronfrequency, "Invalid parameter : 'Language' is empty"],
            'Language not a string'         => [$releasephp, $releasemoodle, $releaseplugin,
                42, $cronfrequency, "Invalid parameter : 'Language' is not a string"],

            'CRON frequency not defined'    => [$releasephp, $releasemoodle, $releaseplugin,
                $language, null, "Invalid parameter : 'CRON frequency' is not defined"],
            'CRON frequency empty'          => [$releasephp, $releasemoodle, $releaseplugin,
                $language, "", "Invalid parameter : 'CRON frequency' is empty"],
            'CRON frequency not an int'     => [$releasephp, $releasemoodle, $releaseplugin,
                $language, "1", "Invalid parameter : 'CRON frequency' is not an int"]
        ];
    }

    /**
     * Test fonction post_configuration -> attend une erreur (clé d'API invalide ou non spécifiée (authentification requise))
     */
    public function test_post_configuration_errorsapikey() {

        $releasephp = $this->postconfigurationvalues['releasephp'];
        $releasemoodle = $this->postconfigurationvalues['releasemoodle'];
        $releaseplugin = $this->postconfigurationvalues['releaseplugin'];
        $language = $this->postconfigurationvalues['language'];
        $cronfrequency = $this->postconfigurationvalues['cronfrequency'];

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->post_configuration($releasephp,
            $releasemoodle, $releaseplugin, $language, $cronfrequency));
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->post_configuration($releasephp,
            $releasemoodle, $releaseplugin, $language, $cronfrequency));

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->post_configuration($releasephp,
            $releasemoodle, $releaseplugin, $language, $cronfrequency));
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->post_configuration($releasephp,
            $releasemoodle, $releaseplugin, $language, $cronfrequency));
    }

    // TESTS FONCTION SEND_DOC...

    /**
     * Test fonction send_doc -> attend l'ID du document inséré
     */
    public function test_send_doc_ok() {

        $title = $this->senddocvalues['title'];
        $filename = $this->senddocvalues['filename'];
        $content = $this->senddocvalues['content'];

        $iddoc = self::$compilatio->send_doc($title, $filename, $content);

        $this->assertEquals('string', gettype($iddoc));
        $this->assertEquals(40, strlen($iddoc));

        return $iddoc;
    }

    /**
     * Test fonction send_doc avec différents paramètres -> attend des erreurs selon les paramètres
     *
     * @param   string  $title          Document's title
     * @param   string  $filename       Filename
     * @param   string  $content        Document's content
     * @param   string  $expected       Result expected
     *
     * @dataProvider send_doc_dataprovider
     */
    public function test_send_doc_invalidparameters($title, $filename, $content, $expected) {

        $this->assertEquals($expected, self::$compilatio->send_doc($title, $filename, $content));
    }

    /**
     * DataProvider de test_send_doc_invalidparameters
     */
    public function send_doc_dataprovider() {

        $title = $this->senddocvalues['title'];
        $filename = $this->senddocvalues['filename'];
        $content = $this->senddocvalues['content'];

        return [
            'Title not defined'         => [null, $filename, $content, "Invalid parameter : 'title' is not defined"],
            'Title empty'               => ["", $filename, $content, "Invalid parameter : 'title' is empty"],
            'Title not a string'        => [42, $filename, $content, "Invalid parameter : 'title' is not a string"],

            'Filename not defined'      => [$title, null, $content, "Invalid parameter : 'filename' is not defined"],
            'Filename empty'            => [$title, "", $content, "Invalid parameter : 'filename' is empty"],
            'Filename not a string'     => [$title, 42, $content, "Invalid parameter : 'filename' is not a string"],

            'Content not defined'       => [$title, $filename, null, "Invalid parameter : 'content' is not defined"],
            'Content empty'             => [$title, $filename, "", "Invalid parameter : 'content' is empty"],
            'Content not a string'      => [$title, $filename, 42, "Invalid parameter : 'content' is not a string"],
        ];
    }

    /**
     * Test fonction send_doc -> attend une erreur (clé d'API invalide ou non spécifiée (authentification requise))
     */
    public function test_send_doc_apikey() {

        $title = $this->senddocvalues['title'];
        $filename = $this->senddocvalues['filename'];
        $content = $this->senddocvalues['content'];

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->send_doc($title, $filename, $content));
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->send_doc($title, $filename, $content));

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->send_doc($title, $filename, $content));
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->send_doc($title, $filename, $content));
    }

    // TESTS FONCTION START_ANALYSE...

    /**
     * Test fonction start_analyse -> attend true
     * Cette fois, on ne fait pas réellement le call API pour ne pas lancer une vraie analyse et surcharger les serveurs
     *
     * @param   string  $iddoc      Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_start_analyse_ok($iddoc) {

        // Create a stub for the compilatioservice class.
        $stub = $this->createMock(compilatioservice::class);

        // Configure the stub.
        $stub->method('start_analyse')->willReturn(true);

        // Calling $stub->start_analyse() will now return true.
        $this->assertTrue($stub->start_analyse($iddoc));
    }

    /**
     * Test fonction start_analyse -> attend des erreurs selon les paramètres
     *
     * @param   string  $iddoc      Document's ID
     * @param   string  $expected   Result expected
     *
     * @dataProvider start_analyse_dataprovider
     */
    public function test_start_analyse_invalidparameters($iddoc, $expected) {

        $this->assertEquals($expected, self::$compilatio->start_analyse($iddoc));
    }

    /**
     * DataProvider de start_analyse_invalidparameters
     */
    public function start_analyse_dataprovider() {
        return [
            'ID not defined'        => [null, "Invalid parameter : 'document's ID' is not defined"],
            'ID empty'              => ["", "Invalid parameter : 'document's ID' is empty"],
            'ID not a string'       => [42, "Invalid parameter : 'document's ID' is not a string"],
            'Invalid ID'            => ["abcdef", "Invalid document id"]
        ];
    }

    /**
     * Test fonction start_analyse -> attend une erreur (clé d'API invalide ou non spécifiée (authentification requise))
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_start_analyse_errorsapikey($iddoc) {

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->start_analyse($iddoc));
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->start_analyse($iddoc));

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->start_analyse($iddoc));
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->start_analyse($iddoc));
    }

    // TESTS FONCTION GET_DOC...

    /**
     * Test fonction get_doc -> attend une classe avec différentes propriétés
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_get_doc_ok($iddoc) {

        // On vérifie différentes propriétés pour un document qui vient d'être uploadé pour les tests.
        $docobject = self::$compilatio->get_doc($iddoc);
        $doc = $docobject->documentProperties;

        $this->assertEquals('object', gettype($doc));
        $this->assertEquals($iddoc, $doc->idDocument);
        $this->assertEmpty($doc->title);
        $this->assertEquals($this->senddocvalues['filename'], $doc->filename);
        $this->assertEquals(strlen($this->senddocvalues['content']), $doc->textLength);
        $this->assertEquals(11, $doc->wordCount);
        $this->assertEquals(str_word_count($this->senddocvalues['content']), $doc->wordCount);

        // On vérifie différentes propriétés pour un document déjà en base.
        $docobjectbase = self::$compilatio->get_doc(self::ID_DOC);
        $docbase = $docobjectbase->documentProperties;
        $docstatusbase = $docobjectbase->documentStatus;

        $this->assertEquals('object', gettype($docbase));
        $this->assertEquals(self::ID_DOC, $docbase->idDocument);
        $this->assertEmpty($docbase->title);
        $this->assertEquals("ANALYSE_COMPLETE", $docstatusbase->status);
        $this->assertEquals("100", $docstatusbase->progression);
    }

    /**
     * Test fonction get_doc -> attend des erreurs selon les paramètres
     *
     * @param   string  $iddoc      Document's ID
     * @param   string  $expected   Result expected
     *
     * @dataProvider get_doc_dataprovider
     */
    public function test_get_doc_invalidparameters($iddoc, $expected) {

        $this->assertEquals($expected, self::$compilatio->get_doc($iddoc));
    }

    /**
     * DataProvider de test_get_doc_invalidparameters
     */
    public function get_doc_dataprovider() {
        return [
            'ID not defined'        => [null, "Invalid parameter : 'document's ID' is not defined"],
            'ID empty'              => ["", "Invalid parameter : 'document's ID' is empty"],
            'ID not a string'       => [42, "Invalid parameter : 'document's ID' is not a string"],
            'Document not found'    => ["abcdef", "Not Found"]
        ];
    }

    /**
     * Test fonction get_doc -> attend une erreur (clé d'API invalide ou non spécifiée (authentification requise))
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_get_doc_errorsapikey($iddoc) {

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->get_doc($iddoc));
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->get_doc($iddoc));

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->get_doc($iddoc));
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->get_doc($iddoc));
    }

    // TESTS FONCTION GET_REPORT_URL...

    /**
     * Test fonction get_report_url -> attend l'URL de l'analyse
     */
    public function test_get_report_url_ok() {

        $url = self::$compilatio->get_report_url(self::ID_DOC);

        $this->assertEquals('string', gettype($url));
        $this->assertStringStartsWith('https://', $url);
        $this->assertEquals(self::DOC_REPORT_URL, $url);
    }

    /**
     * Test fonction get_report_url -> attend des erreurs selon les paramètres
     *
     * @param   string  $iddoc      Document's ID
     * @param   string  $expected   Result expected
     *
     * @dataProvider get_report_url_dataprovider
     */
    public function test_get_report_url_invalidparameters($iddoc, $expected) {

        $this->assertEquals($expected, self::$compilatio->get_report_url($iddoc));
    }

    /**
     * DataProvider de test_get_report_url_invalidparameters
     */
    public function get_report_url_dataprovider() {
        return [
            'ID not defined'        => [null, "Invalid parameter : 'document's ID' is not defined"],
            'ID empty'              => ["", "Invalid parameter : 'document's ID' is empty"],
            'ID not a string'       => [42, "Invalid parameter : 'document's ID' is not a string"],
            'Document not found'    => ["abcdef", "Not Found"]
        ];
    }

    /**
     * Test fonction get_report_url -> attend une erreur
     * (par exemple le document n'a pas fini d'être / n'a pas été analysé)
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_get_report_url_badrequest($iddoc) {

        $this->assertEquals("Bad Request", self::$compilatio->get_report_url($iddoc));
    }

    /**
     * Test fonction get_doc -> attend une erreur
     * (clé d'API invalide ou non spécifiée (authentification requise))
     */
    public function test_get_report_url_errorsapikey() {

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->get_report_url(self::ID_DOC));
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->get_report_url(self::ID_DOC));

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->get_report_url(self::ID_DOC));
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->get_report_url(self::ID_DOC));
    }

    // TESTS FONCTION GET_INDEXING_STATE & SET_INDEXING_STATE...

    /**
     * Test fonction set_indexing_state -> attend  true
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_set_indexing_state_trueok($iddoc) {

        $this->assertTrue(self::$compilatio->set_indexing_state($iddoc, true));
        sleep(5); // Pause pour laisser le temps au paramètre d'être mis à jour.
    }

    /**
     * Test fonction get_indexing_state -> attend true
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     * @depends test_set_indexing_state_trueok
     */
    public function test_get_indexing_state_trueok($iddoc) {

        $this->assertTrue(self::$compilatio->get_indexing_state($iddoc));
    }

    /**
     * Test fonction set_indexing_state -> attend false
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_set_indexing_state_falseok($iddoc) {

        $this->assertTrue(self::$compilatio->set_indexing_state($iddoc, false));
        sleep(5); // Pause pour laisser le temps au paramètre d'être mis à jour.
    }

    /**
     * Test fonction get_indexing_state -> attend false
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     * @depends test_set_indexing_state_falseok
     */
    public function test_get_indexing_state_falseok($iddoc) {

        $this->assertFalse(self::$compilatio->get_indexing_state($iddoc));
    }

    /**
     * Test fonction set_indexing_state -> attend des erreurs selon les paramètres
     *
     * @param   string  $iddoc      Document's ID
     * @param   bool    $indexed    Indexing state
     * @param   string  $expected   Result expected
     *
     * @dataProvider set_indexing_state_dataprovider
     */
    public function test_set_indexing_state_invalidparameters($iddoc, $indexed, $expected) {

        $this->assertEquals($expected, self::$compilatio->set_indexing_state($iddoc, $indexed));
    }

    /**
     * DataProvider de test_set_indexing_state_invalidparameters
     */
    public function set_indexing_state_dataprovider() {

        $iddoc = self::ID_DOC;
        $indexed = true;

        return [
            'ID not defined'            => [null, $indexed, "Invalid parameter : 'document's ID' is not defined"],
            'ID empty'                  => ["", $indexed, "Invalid parameter : 'document's ID' is empty"],
            'ID not a string'           => [42, $indexed, "Invalid parameter : 'document's ID' is not a string"],
            'Document not found'        => ["abcdef", $indexed, "Not Found"],

            'Indexing state not bool, int'      => [$iddoc, 42, "Invalid parameter : indexing state is not a boolean"],
            'Indexing state not bool, string'   => [$iddoc, "abcdef", "Invalid parameter : indexing state is not a boolean"]
        ];
    }

    /**
     * Test fonction get_indexing_state -> attend des erreurs selon les paramètres
     *
     * @param   string  $iddoc      Document's ID
     * @param   string  $expected   Result expected
     *
     * @dataProvider get_indexing_state_dataprovider
     */
    public function test_get_indexing_state_invalidparameters($iddoc, $expected) {

        $this->assertEquals($expected, self::$compilatio->get_indexing_state($iddoc));
    }

    /**
     * DataProvider de test_get_indexing_state_invalidparameters
     */
    public function get_indexing_state_dataprovider() {

        return [
            'ID not defined'                => [null, "Invalid parameter : 'document's ID' is not defined"],
            'ID empty'                      => ["", "Invalid parameter : 'document's ID' is empty"],
            'ID not a string'               => [42, "Invalid parameter : 'document's ID' is not a string"],
            'Document not found'            => ["abcdef", "Not Found"],
        ];
    }

    /**
     * Test fonction get & set_indexing_state -> attend une erreur
     * (clé d'API invalide ou non spécifiée (authentification requise))
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_get_set_indexing_state_errorsapikey($iddoc) {

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->get_indexing_state($iddoc));
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->get_indexing_state($iddoc));

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->get_indexing_state($iddoc));
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->get_indexing_state($iddoc));

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->set_indexing_state($iddoc, true));
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->set_indexing_state($iddoc, true));

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->set_indexing_state($iddoc, true));
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->set_indexing_state($iddoc, true));
    }

    // TESTS FONCTION DEL_DOC...

    /**
     * Test fonction del_doc -> attend des erreurs selon les paramètres
     *
     * @param   string  $iddoc      Document's ID
     * @param   string  $expected   Result expected
     *
     * @dataProvider del_doc_dataprovider
     */
    public function test_del_doc_invalidparameters($iddoc, $expected) {

        $this->assertEquals($expected, self::$compilatio->del_doc($iddoc));
    }

    /**
     * DataProvider de test_del_doc_invalidparameters
     */
    public function del_doc_dataprovider() {
        return [
            'ID not defined'        => [null, "Invalid parameter : 'document's ID' is not defined"],
            'ID empty'              => ["", "Invalid parameter : 'document's ID' is empty"],
            'ID not a string'       => [42, "Invalid parameter : 'document's ID' is not a string"],
            'Document not found'    => ["abcdef", "Not Found"]
        ];
    }

    /**
     * Test fonction del_doc -> attend une erreur
     * (clé d'API invalide ou non spécifiée (authentification requise))
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_del_doc_errorsapikey($iddoc) {

        $this->assertEquals("Invalid API key", self::$compilatioinvkey1->del_doc($iddoc));
        $this->assertEquals("Invalid API key", self::$compilatioinvkey2->del_doc($iddoc));

        $this->assertEquals("Authentication Required", self::$compilatioauthreq1->del_doc($iddoc));
        $this->assertEquals("Authentication Required", self::$compilatioauthreq2->del_doc($iddoc));
    }

    /**
     * Test fonction del_doc -> attend une erreur (document déjà indexé)
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_del_doc_docindexed($iddoc) {

        self::$compilatio->set_indexing_state($iddoc, true);
        sleep(5); // Pause pour laisser le temps au paramètre d'être mis à jour.

        $this->assertEquals("You can't remove an indexed document, please remove it from your references database before",
            self::$compilatio->del_doc($iddoc));
    }

    /**
     * Test fonction del_doc -> attend true
     *
     * @param   string  $iddoc  Document's ID
     *
     * @depends test_send_doc_ok
     */
    public function test_del_doc_ok($iddoc) {

        self::$compilatio->set_indexing_state($iddoc, false);
        sleep(5); // Pause pour laisser le temps au paramètre d'être mis à jour.

        $this->assertTrue(self::$compilatio->del_doc($iddoc));
    }
}

/*
    Commande pour exécuter tous les tests unitaires :
        vendor/bin/phpunit plagiarism/compilatio/tests/api_class_test.php

    Commande pour exécuter un seul test unitaire :
        vendor/bin/phpunit --filter test_send_doc_ok  plagiarism/compilatio/tests/api_class_test.php
*/