<?php

if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}
/**
 *  Name: Risks
 *  Directory: risks
 *  Version 1.0
 *  Type: user
 *  UI Name: Risks
 *  UI Icon: ?
 */
$config = array();
$config['mod_name'] = 'Risks'; // name the module
$config['mod_version'] = '1.0'; // add a version number
$config['mod_directory'] = 'risks'; // tell dotProject where to find this module
$config['mod_setup_class'] = 'CSetupRisks'; // the name of the PHP setup class (used below)
$config['mod_type'] = 'user'; //'core' for standard dP modules, 'user' for additional modules from dotmods
$config['mod_ui_name'] = 'Risks'; // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon'] = 'risks.png'; // name of a related icon //TODO
$config['mod_description'] = 'Risks Plan'; // some description of the module //TODO
$config['mod_config'] = false; // show 'configure' link in viewmods
//$config['permissions_item_table'] = 'risks'; // tell dotProject the database table name
$config['permissions_item_field'] = 'risk_id'; // identify table's primary key (for permissions)
$config['permissions_item_label'] = 'risk_name'; // identify "title" field in table

if (@$a == 'setup') {
    echo dPshowModuleConfig($config);
}

class CSetupRisks {

    function configure() {
        return true;
    }

    function remove() {
        $q = new DBQuery();
        $q->dropTable('risks');
        $q->exec();

        $q->clear();
        $q->setDelete('sysvals');
        $q->addWhere("sysval_title IN ('RiskImpact', 'RiskProbability', 'RiskStatus', 'RiskPotential', 'RiskPriority', 'RiskActive', 'RiskStrategy')");
        $q->exec();
        // modules/projects/locales/ (the risk labels) ships with dotProject:
        // removing the module used to delete those files.
        return null;
    }

    function upgrade($old_version) {
// Place to put upgrade logic, based on the previously installed version.
// Usually handled via a switch statement.
// Since this is the first version of this module, we have nothing to update.
        return true;
    }

    function install() {
        //$this->installProjectsTranslationFile();
        $q = new DBQuery();
        $q->createTable('risks');
        $q->createDefinition("(
                              `risk_id` int(11) NOT NULL AUTO_INCREMENT ,
                              `risk_name` varchar(255) NOT NULL,
                              `risk_responsible` int(11) NOT NULL,
                              `risk_description` varchar(2000) DEFAULT NULL,
                              `risk_probability` varchar(15) NOT NULL,
                              `risk_impact` varchar(15) NOT NULL,
                              `risk_answer_to_risk` varchar(2000) DEFAULT NULL,
                              `risk_status` varchar(15) NOT NULL,
                              `risk_project` int(11) DEFAULT NULL,
                              `risk_task` int(11) DEFAULT NULL,
                              `risk_notes` varchar(2000) DEFAULT NULL,
                              `risk_potential_other_projects` varchar(2) NOT NULL,
                              `risk_lessons_learned` varchar(2000) DEFAULT NULL,
                              `risk_priority` varchar(15) NOT NULL,
                              `risk_active` int(11) NOT NULL,
                              `risk_strategy` int(11) NOT NULL,
                              `risk_prevention_actions` varchar(2000) DEFAULT NULL,
                              `risk_contingency_plan` varchar(2000) DEFAULT NULL,
                              PRIMARY KEY (`risk_id`)
                              )");

        //$q->exec($sql);
        $q->exec();

        $q = new DBQuery();
        $q->addTable('sysvals');
        $q->addInsert('sysval_title', 'RiskImpact');
        $q->addInsert('sysval_key_id', 1);
        $q->addInsert('sysval_value', "0|" . 'LBL_SUPER_LOW_M' . "\n1|" . 'LBL_LOW_M' . "\n2|" . 'LBL_MEDIUM_M' . "\n3|" . 'LBL_HIGH_M' . "\n4|" . 'LBL_SUPER_HIGH_M');
        $q->exec();

        $q->clear();
        $q->addTable('sysvals');
        $q->addInsert('sysval_title', 'RiskProbability');
        $q->addInsert('sysval_key_id', 1);
        $q->addInsert('sysval_value', "0|" . 'LBL_SUPER_LOW_F' . "\n1|" . 'LBL_LOW_F' . "\n2|" . 'LBL_MEDIUM_F' . "\n3|" . 'LBL_HIGH_F' . "\n4|" . 'LBL_SUPER_HIGH_F');
        $q->exec();

        $q->clear();
        $q->addTable('sysvals');
        $q->addInsert('sysval_title', 'RiskStatus');
        $q->addInsert('sysval_key_id', 1);
        $q->addInsert('sysval_value', "0|" . 'LBL_OPEN' . "\n1|" . 'LBL_CLOSED' . "\n2|" . 'LBL_NOT_APLICABLE');
        $q->exec();

        $q->clear();
        $q->addTable('sysvals');
        $q->addInsert('sysval_title', 'RiskPotential');
        $q->addInsert('sysval_key_id', 1);
        $q->addInsert('sysval_value', "0|" . 'LBL_NO' . "\n1|" . 'LBL_YES');
        $q->exec();

        $q->clear();
        $q->addTable('sysvals');
        $q->addInsert('sysval_title', 'RiskPriority');
        $q->addInsert('sysval_key_id', 1);
        $q->addInsert('sysval_value', "0|" . 'LBL_LOW_F' . "\n1|" . 'LBL_MEDIUM_F' . "\n2|" . 'LBL_HIGH_F');
        $q->exec();

        $q->clear();
        $q->addTable('sysvals');
        $q->addInsert('sysval_title', 'RiskActive');
        $q->addInsert('sysval_key_id', 1);
        $q->addInsert('sysval_value', "0|" . 'LBL_YES' . "\n1|" . 'LBL_NO');
        $q->exec();

        $q->clear();
        $q->addTable('sysvals');
        $q->addInsert('sysval_title', 'RiskStrategy');
        $q->addInsert('sysval_key_id', 1);
        $q->addInsert('sysval_value', "0|" . 'LBL_ACCEPT' . "\n1|" . 'LBL_ELIMINATE' . "\n2|" . 'LBL_MITIGATE' . "\n3|" . 'LBL_TRANSFER');
        $q->exec();
        return NULL;
    }

    private function installProjectsTranslationFile() {
        $translationFileUS = DP_BASE_DIR . "/modules/risks/locales/en.inc";
        $translationFilePTBR = DP_BASE_DIR . "/modules/risks/locales/pt_br.inc";
        echo $translationFilePTBR;
        mkdir(DP_BASE_DIR . "/modules/projects/locales");
        $us_contents = file_get_contents($translationFileUS);
        $ptBR_contents = file_get_contents($translationFilePTBR);
        $destFileUS = DP_BASE_DIR . "/modules/projects/locales/en.inc";
        $destFilePTBR = DP_BASE_DIR . "/modules/projects/locales/pt_br.inc";
        $this->updateFile($destFileUS, $us_contents);
        $this->updateFile($destFilePTBR, $ptBR_contents);
    }

    private function updateFile($fileName, $content) {
        if (!file_exists($fileName)) {
            $fileName = str_replace("\\", "/", $fileName);
        }
        $fp = fopen($fileName, 'a');
        fwrite($fp, $content);
        fclose($fp);
    }

}

?>