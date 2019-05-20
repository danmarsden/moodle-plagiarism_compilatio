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

use core_privacy\local\metadata\collection;
use plagiarism_compilatio\privacy\provider;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;

class plagiarism_compilatio_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    /**
     * Test fonction _get_metadata
     */
    public function test_get_metadata() {

        $this->resetAfterTest();

        // On charge la liste des données personnelles que Compilatio stocke
        $collection = new collection('plagiarism_compilatio');
        $newcollection = provider::_get_metadata($collection);
        $itemcollection = $newcollection->get_collection();

        // On vérifie qu'il y a bien quatre items
        $this->assertCount(5, $itemcollection);

        // On vérifie que core_files est retourné
        $this->assertEquals('core_files', $itemcollection[0]->get_name());
        $this->assertEquals('privacy:metadata:core_files', $itemcollection[0]->get_summary());

        // On vérifie que core_plagiarism est retourné
        $this->assertEquals('core_plagiarism', $itemcollection[1]->get_name());
        $this->assertEquals('privacy:metadata:core_plagiarism', $itemcollection[1]->get_summary());

        // On vérifie que plagiarism_compilatio_files est retourné
        $this->assertEquals('plagiarism_compilatio_files', $itemcollection[2]->get_name());

        // On vérifie que le tableau des champs retournés possède bien les bonnes clés
        $privacyfields = $itemcollection[2]->get_privacy_fields();
        $this->assertArrayHasKey('id', $privacyfields);
        $this->assertArrayHasKey('cm', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('identifier', $privacyfields);
        $this->assertArrayHasKey('filename', $privacyfields);
        $this->assertArrayHasKey('externalid', $privacyfields);
        $this->assertArrayHasKey('reporturl', $privacyfields);
        $this->assertArrayHasKey('statuscode', $privacyfields);
        $this->assertArrayHasKey('similarityscore', $privacyfields);
        $this->assertArrayHasKey('attempt', $privacyfields);
        $this->assertArrayHasKey('errorresponse', $privacyfields);
        $this->assertArrayHasKey('timesubmitted', $privacyfields);

        // On vérifie que External Compilatio Document est retourné
        $this->assertEquals('External Compilatio Document', $itemcollection[3]->get_name());

        // On vérifie que le tableau des champs retournés possède bien les bonnes clés
        $privacyfields = $itemcollection[3]->get_privacy_fields();
        $this->assertArrayHasKey('lastname', $privacyfields);
        $this->assertArrayHasKey('firstname', $privacyfields);
        $this->assertArrayHasKey('email_adress', $privacyfields);
        $this->assertArrayHasKey('user_id', $privacyfields);
        $this->assertArrayHasKey('filename', $privacyfields);
        $this->assertArrayHasKey('upload_date', $privacyfields);
        $this->assertArrayHasKey('id', $privacyfields);
        $this->assertArrayHasKey('indexed', $privacyfields);

        // On vérifie que External Compilatio Report est retourné
        $this->assertEquals('External Compilatio Report', $itemcollection[4]->get_name());

        // On vérifie que le tableau des champs retournés possède bien les bonnes clés
        $privacyfields = $itemcollection[4]->get_privacy_fields();
        $this->assertArrayHasKey('id', $privacyfields);
        $this->assertArrayHasKey('doc_id', $privacyfields);
        $this->assertArrayHasKey('user_id', $privacyfields);
        $this->assertArrayHasKey('start', $privacyfields);
        $this->assertArrayHasKey('end', $privacyfields);
        $this->assertArrayHasKey('state', $privacyfields);
        $this->assertArrayHasKey('plagiarism_percent', $privacyfields);
    }

    /**
     * Test fonction get_contexts_for_userid
     */
    public function test_get_contexts_for_userid() {

        $this->resetAfterTest();

        // On crée un étudiant
        $student = $this->getDataGenerator()->create_user();

        // On crée cinq modules de cours, cinq contexts et cinq plagiarism files
        for ($i = 0; $i < 5; $i++) {
            $coursemodule = $this->create_partial_coursemodule();
            $context = $this->create_partial_context($coursemodule->id);
            $this->create_partial_plagiarismfile($coursemodule->id, $student->id);
        }

        // On vérifie que la liste des contextes retournée est bien égale à 5
        $contextlist = provider::get_contexts_for_userid($student->id);

        $this->assertCount(5, $contextlist);
    }

    /**
     * Test fonction get_users_in_context
     */
    public function test_get_users_in_context() {

        $this->resetAfterTest();

        // On crée trois étudiants
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();

        // On crée un module de cours et un contexte
        $coursemodule = $this->create_partial_coursemodule();
        $context = $this->create_partial_context($coursemodule->id);

        // On crée dix plagiarismfiles, cinq pour l'étudiant 1 et cinq autres pour l'étudiant 2
        for($i = 0; $i < 5; $i++) {
            $this->create_partial_plagiarismfile($coursemodule->id, $student1->id);
            $this->create_partial_plagiarismfile($coursemodule->id, $student2->id);
        }

        // On crée une userlist vide (sans utilisateurs, mais avec un contexte)
        $context = context_module::instance($coursemodule->id);
        $userlistEmpty = new userlist($context, 'plagiarism_compilatio');

        // On récupère la liste des utilisateurs qui ont des données dans le contexte, et on regarde si l'on a bien les IDs des étudiants 1 et 2
        $userlist = provider::get_users_in_context($userlistEmpty);
        $userids = $userlist->get_userids();
        
        $this->assertEquals(array($student1->id, $student2->id), $userids);
    }

    /**
     * Test fonction _export_plagiarism_user_data
     */
    public function test_export_plagiarism_user_data() {

        $this->resetAfterTest();

        // On crée un étudiant
        $student = $this->getDataGenerator()->create_user();

        // On crée cinq modules de cours, cinq contexts et cinq plagiarismfiles
        // Et on vérifie que la liste des contextes retournée est bien égale à 5
        for($i = 0; $i < 5; $i++) {
            $coursemodule = $this->create_partial_coursemodule();
            $context = $this->create_partial_context($coursemodule->id);
            $this->create_partial_plagiarismfile($coursemodule->id, $student->id);
        }

        $context = context_module::instance($coursemodule->id);

        // On vérifie que, à l'exportation des données, il y a bien quelque chose à visualiser pour l'utilisateur
        provider::_export_plagiarism_user_data($student->id, $context, array(), array());
        $writer = writer::with_context($context);

        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test fonction _delete_plagiarism_for_context
     */
    public function test_delete_plagiarism_for_context() {

        $this->resetAfterTest();
        global $DB;

        // On vérifie que la table plagiarism_compilatio_files est vide
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(0, $nbPlagiarismFile);

        // On crée un module de cours et un contexte
        $coursemodule = $this->create_partial_coursemodule();
        $context = $this->create_partial_context($coursemodule->id);

        // On crée cinq plagiarismfiles, un par étudiant, dans un contexte précis
        for($i = 0; $i < 5; $i++) {
            $student = $this->getDataGenerator()->create_user();
            $this->create_partial_plagiarismfile($coursemodule->id, $student->id);
        }

        // On vérifie qu'on a bien cinq plagiarismfiles dans la tablea plagiarism_compilatio_files
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(5, $nbPlagiarismFile);

        // On supprime les plagiarismfiles dans ce contexte précis
        $context = context_module::instance($coursemodule->id);
        provider::_delete_plagiarism_for_context($context);

        // On vérifie qu'on a bien vidé la table plagiarism_compilatio_files
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(0, $nbPlagiarismFile);
    }

    /**
     * Test fonction _delete_plagiarism_for_user
     */
    public function test_delete_plagiarism_for_user() {

        $this->resetAfterTest();
        global $DB;

        // On vérifie que la table plagiarism_compilatio_files est vide
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(0, $nbPlagiarismFile);

        // On crée un étudiant
        $student = $this->getDataGenerator()->create_user();

        // On crée deux contextes différents
        $coursemodule1 = $this->create_partial_coursemodule();
        $context1 = $this->create_partial_context($coursemodule1->id);
        $coursemodule2 = $this->create_partial_coursemodule();
        $context2 = $this->create_partial_context($coursemodule2->id);

        // On crée cinq plagiarismfiles pour chaque contexte
        for($i = 0; $i < 5; $i++) {
            $this->create_partial_plagiarismfile($coursemodule1->id, $student->id);
            $this->create_partial_plagiarismfile($coursemodule2->id, $student->id);
        }

        // On vérifie qu'on a bien dix plagiarismfiles dans la table plagiarism_compilatio_files
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(10, $nbPlagiarismFile);

        // On supprime les fichiers dans le premier contexte
        $context1 = context_module::instance($coursemodule1->id);
        provider::_delete_plagiarism_for_user($student->id, $context1);

        // On vérifie qu'il reste bien cinq plagiarismfiles dans la table plagiarism_compilatio_files
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(5, $nbPlagiarismFile);

        // On supprime les fichiers dans le second contexte
        $context2 = context_module::instance($coursemodule2->id);
        provider::_delete_plagiarism_for_user($student->id, $context2);

        // On vérifie qu'on a bien vidé la table plagiarism_compilatio_files
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(0, $nbPlagiarismFile);
    }

    /**
     * Test fonction delete_data_for_users
     */
    public function test_delete_data_for_users() {

        $this->resetAfterTest();
        global $DB;

        // On vérifie que la table plagiarism_compilatio_files est vide
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(0, $nbPlagiarismFile);

        // On crée trois étudiants
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();

        // On crée un module de cours et un contexte
        $coursemodule = $this->create_partial_coursemodule();
        $context = $this->create_partial_context($coursemodule->id);

        // On crée quinze plagiarismfiles, cinq par étudiant, dans un contexte précis
        for($i = 0; $i < 5; $i++) {
            $this->create_partial_plagiarismfile($coursemodule->id, $student1->id);
            $this->create_partial_plagiarismfile($coursemodule->id, $student2->id);
            $this->create_partial_plagiarismfile($coursemodule->id, $student3->id);
        }

        // On vérifie que la table plagiarism_compilatio_files contient bien quinze plagiarismfiles
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(15, $nbPlagiarismFile);

        // On crée la liste approved_userlist avec les étudiants 1 et 2
        $context = context_module::instance($coursemodule->id);
        $userlist = new approved_userlist($context, 'plagiarism_compilatio', array($student1->id, $student2->id));

        // On supprime les plagiarismfiles pour ces deux étudiants
        provider::delete_data_for_users($userlist);

        // On vérifie qu'il ne reste plus que cinq plagiarismfiles dans la table plagiarism_compilatio_files
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(5, $nbPlagiarismFile);
    }

    /**
     * Fonction de testouille
     */
    public function testouille() {

        $this->resetAfterTest();
        global $DB;

        global $CFG;
        require_once($CFG->dirroot . '/plagiarism/compilatio/api.class.php');
        require_once($CFG->dirroot . '/plagiarism/compilatio/lib.php');

        $compilatio = new compilatioservice('646022b3de50939edccc46f9009924651022036a', 'https://beta.compilatio.net');

        /*
        // CELA FONCTIONNE !!!! -- mais pas "en vrai"
        #region
        $sql = "SELECT *
                FROM mdl_plagiarism_compilatio_files
                WHERE userid = 9";

        $compids = $DB->get_records_sql($sql);

        var_dump($compids);

        foreach($compids as $compid) {
            //var_dump($compilatio->get_doc($compid));
            var_dump($compid->externalid);
            //$compilatio->del_doc($compid->externalid);
        }
        #endregion
        */
        

        
        /*
        // CELA FONCTIONNE !!!! -- mais pas "en vrai"
        #region
        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(0, $nbPlagiarismFile);

        $student = $this->getDataGenerator()->create_user();
        $coursemodule = $this->create_partial_coursemodule();
        $context = $this->create_partial_context($coursemodule->id);
        for($i = 0; $i < 5; $i++) {
            $this->create_partial_plagiarismfile($coursemodule->id, $student->id);
        }

        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(5, $nbPlagiarismFile);

        $sql = "SELECT *
                FROM {plagiarism_compilatio_files}
                WHERE userid = ?";
        $docs = $DB->get_records_sql($sql, array($student->id));

        foreach($docs as $doc) {
            $DB->delete_records('plagiarism_compilatio_files', array('id' => $doc->id));
        }

        $nbPlagiarismFile = $DB->count_records('plagiarism_compilatio_files');
        $this->assertEquals(0, $nbPlagiarismFile);
        #endregion
        */

        /*
        // CELA FONCTIONNE !!!! -- mais pas "en vrai"
        $student = $this->getDataGenerator()->create_user();
        $coursemodule = $this->create_partial_coursemodule();
        $context = $this->create_partial_context($coursemodule->id);
        for($i = 0; $i < 5; $i++) {
            $this->create_partial_plagiarismfile($coursemodule->id, $student->id);
        }
        
        $sql = "SELECT *
                FROM {plagiarism_compilatio_files}
                WHERE userid = ?";
        $docs = $DB->get_records_sql($sql, array($student->id));

        foreach($docs as $doc) {
            echo "\n-------- AVANT MODFIFICATION --------\n";
            var_dump($doc->errorresponse);
            $DB->set_field('plagiarism_compilatio_files', 'errorresponse', "J'ai réussi à écrire quelque chose pour ce document. CQFD : j'arrive bien à accéder au fichier, donc pourquoi la suppression ne fonctionne pas ????????", array("id" => $doc->id));
            //$doc->errorresponse = "J'ai réussi à écrire quelque chose pour ce document. CQFD : j'arrive bien à accéder au fichier, donc pourquoi la suppression ne fonctionne pas ????????";
            //$DB->update_record('plagiarism_compilatio_files', $doc);
        }

        $docs = $DB->get_records_sql($sql, array($student->id));

        foreach($docs as $doc) {
            echo "\n-------- APRES MODIFICATION --------\n";
            var_dump($doc->errorresponse);
        }
        */

        $student = $this->getDataGenerator()->create_user();
        $coursemodule = $this->create_partial_coursemodule();
        $context = $this->create_partial_context($coursemodule->id);
        for($i = 0; $i < 5; $i++) {
            $this->create_partial_plagiarismfile($coursemodule->id, $student->id);
        }
        $sql = "SELECT *
                FROM {plagiarism_compilatio_files}
                WHERE userid = ?";
        $docs = $DB->get_records_sql($sql, array($student->id));

        $texte = "Description des docs : \n";
        foreach($docs as $doc) {
            $texte .= $this->getTexte($doc) . "\n";
        }
        var_dump($texte);
    }
    public function getTexte($doc) {
        $texte = "";
        foreach((array)$doc as $k => $v) {
            $texte .= $k . " => " . $v . "\n";
        }
        return $texte;
    }

#region Fonctions create_partial...

    /**
     * Fonction qui insère seulement quelques champs dans la table course_modules
     */
    private function create_partial_coursemodule() {

        global $DB;

        $coursemodule = new stdClass();
        $coursemodule->visible = 1;
        $id = $DB->insert_record('course_modules', $coursemodule);
        $coursemodule->id = $id;

        return $coursemodule;
    }

    /**
     * Fonction qui insère seulement quelques champs dans la table context
     */
    private function create_partial_context($cmId) {

        global $DB;

        $context = new stdClass();
        $context->contextlevel = CONTEXT_MODULE;
        $context->instanceid = $cmId;
        $id = $DB->insert_record('context', $context);
        $context->id = $id;

        return $context;
    }

    /**
     * Fonction qui insère seulement quelques champs dans la table plagiarism_compilatio_files
     */
    private function create_partial_plagiarismfile($cmId, $userId) {

        global $DB;

        $plagiarismfile = new stdClass();
        $plagiarismfile->cm = $cmId;
        $plagiarismfile->userid = $userId;
        $plagiarismfile->externalid = rand(0, 100);
        $id = $DB->insert_record('plagiarism_compilatio_files', $plagiarismfile);
        $plagiarismfile->id = $id;

        return $plagiarismfile;
    }

#endregion

}

/*

    Commande pour exécuter tous les tests unitaires (se situer dans moodle36/www/moodle36) :
        vendor/bin/phpunit plagiarism/compilatio/tests/privacy/provider_test.php
    
    Commande pour exécuter un seul test unitaire (se situer dans moodle36/www/moodle36) :
        vendor/bin/phpunit --filter test_get_metadata  plagiarism/compilatio/tests/privacy/provider_test.php

*/