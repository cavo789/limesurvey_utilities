<?php

declare(strict_types = 1);

/*
 * Christophe Avonture
 * Written date : 2018-11-06
 *
 * *** REQUIRES PHP7 ***
 *
 * This script is a skeleton whose purpose is to set up all
 * the programming required to access the LimeSurvey database.
 *
 * It is necessary to "just" program the "business" part i. e.
 * the code you want to execute as e. g. queries to obtain data
 * (important note: always use the LimeSurvey API when possible).
 *
 * You could get the list of tables, get data, delete information,
 * update,... depending on the SQL queries you run
 *
 * Your "business" part should be coded in the Process() function.
 *
 * The example here below will get the list of tables but you can
 * do everything you want by using the correct SQL statements
 *
 * -------------------------------------------------------------
 * - SCRIPT LOCATION: This script should be placed in the root -
 * - folder of the LimeSurvey installation. If you put this    -
 * - script elsewhere please adjust the $root variable in the  -
 * - initialize() function                                     -
 * -------------------------------------------------------------
 */

namespace LimeSurvey;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

class dbRunSQL
{
    /**
     * Root folder of the LimeSurvey website
     * For instance: C:\Sites\LimeSurvey.
     *
     * @var string
     */
    private $root = '';

    /**
     * Database object, points to the LimeSurvey database.
     * Initialized by the setDBConnection() function of this class.
     *
     * @var \PDO
     */
    private $db = null;

    /**
     * DSN for the LimeSurvey DB. Infos coming from
     * (site)/application/config/config.php.
     * Initialized by the setDBConnection() function of this class.
     *
     * @var string
     */
    private $sDBDSN = '';

    /**
     * Database username for the LimeSurvey DB. Infos coming from
     * (site)/application/config/config.php.
     * Initialized by the setDBConnection() function of this class.
     *
     * @var string
     */
    private $sDBUsername = '';

    /**
     * Database password for the LimeSurvey DB. Infos coming from
     * (site)/application/config/config.php.
     * Initialized by the setDBConnection() function of this class.
     *
     * @var string
     */
    private $sDBPassword = '';

    /**
     * Database prefix for the LimeSurvey DB. Infos coming from
     * (site)/application/config/config.php.
     * Initialized by the setDBConnection() function of this class.
     *
     * @var string
     */
    private $sDBPrefix = '';

    /**
     * Make a few initializations.
     *
     * @return void
     */
    public function __construct()
    {
        // The running script is, if no changes are made on how the
        // script work, C:/Sites/LimeSurvey/db_run_sql.php
        // Use SCRIPT_FILENAME and not __DIR__ to support symlink
        $script = $_SERVER['SCRIPT_FILENAME'];

        // Get the root of the LimeSurvey app i.e. C:/Sites/LimeSurvey
        $this->root = rtrim(dirname($script), '/') . DS;

        // The configuration file of LimeSurvey will be loaded and that file
        // required the presence of the BASEPATH constant so initialize it.
        // Set the constant required by config.php so should be initialized first
        // @phan-suppress-next-line PhanUnreferencedConstant
        defined('BASEPATH') or define('BASEPATH', $this->root);
    }

    /**
     * Close the database connection.
     *
     * @return void
     */
    public function __destruct()
    {
        unset($this->db);
    }

    /**
     * Do the job. Update this function with your business needs.
     *
     * @return void
     */
    public function Process()
    {
        // Initialize the database connection and if successful,
        // run the action
        if (self::setDBConnection()) {
            // -------------------------------------------
            // - Code your action here below, see        -
            // - the example in the script documentation -
            // - block (top of the script)               -
            // -------------------------------------------

            // Get the list of tables in the database
            $rows = self::DBExecuteStatement('SHOW TABLES', \PDO::FETCH_NUM);

            $list = '<h2>List of tables in the LimeSurvey DB</h2>' .
                '<ol>';

            foreach ($rows as $key => $value) {
                $list .= sprintf('<li>%s</li>', $value[0]);
            }

            $list .= '</ol>';

            echo $list;
        }
    }

    /**
     * Establish a connection to the LimeSurvey database.
     *
     * @throws \Exception If /application/config/config.php file isn't found
     * @throws \Exception If credentials are incorrect
     *
     * @return bool
     */
    private function setDBConnection(): bool
    {
        // Configuration file of LimeSurvey (information's for the
        // database connection are stored there)
        // $root is the root folder of the LimeSurvey website
        $config = $this->root . 'application/config/config.php';

        if (!file_exists($config)) {
            throw new \Exception(
                sprintf('File %s not found', str_replace('/', DS, $config))
            );
        }

        // Load the file config.php file and get LimeSurvey constants
        $arr = include $config;

        // Isolate the database information's
        $this->sDBDSN      = $arr['components']['db']['connectionString'];
        $this->sDBUsername = $arr['components']['db']['username'];
        $this->sDBPassword = $arr['components']['db']['password'];
        $this->sDBPrefix   = $arr['components']['db']['tablePrefix'];

        unset($arr);

        // Establish a connection
        try {
            $this->db = new \PDO(
                $this->sDBDSN,
                $this->sDBUsername,
                $this->sDBPassword,
                // Be sure to correctly handle accentuated characters
                [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]
            );
        } catch (\Exception $e) {
            echo sprintf(
                'Invalid credentials provided for %s, login %s, password %s',
                $this->sDBDSN,
                $this->sDBUsername,
                $this->sDBPassword
            );
        }

        // If the code comes here, the connection was successful
        return true;
    }

    /**
     * Execute a query against the LimeSurvey database
     * For instance
     *      SELECT Count(sid) As Count FROM `#_surveys`.
     *
     * The "#_" prefix used in table names will be replace by the
     * prefix used by LimeSurvey.
     *
     * @param string $sSQL        The SQL statement to fires
     * @param int    $fetch_style Controls the contents of the returned array
     *
     * @see http://php.net/manual/en/pdostatement.fetchall.php for
     * possible values for the $fetch_style parameter
     *
     * @throws \Exception When the database isn't initialized first
     * @throws \Exception When the execution of the SQL statement has failed
     *
     * @return array
     */
    private function DBExecuteStatement(
        string $sSQL,
        int $fetch_style = \PDO::FETCH_ASSOC
    ): array {
        if (null == $this->db) {
            throw new \Exception('The database should be ' .
                'initialized first, please call setDBConnection() first');
        }

        $result = [];

        // Use the correct prefix
        $sSQL = str_replace('#_', $this->sDBPrefix, $sSQL);

        // Prepare the statement
        $query = $this->db->prepare($sSQL);

        // @phan-suppress-next-line PhanUnusedVariable
        $result = [];

        // And execute the statement
        if ($query->execute()) {
            $result = $query->fetchAll($fetch_style);
        } else {
            // There was an error with the SQL statement
            throw new \Exception(
                sprintf('Invalid SQL statement: %s', $sSQL)
            );
        }

        unset($query);

        return $result;
    }
}

// Do the job
$db = new dbRunSQL();
$db->Process();
