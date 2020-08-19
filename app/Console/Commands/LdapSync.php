<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Log;
use Exception;
use App\Models\User;
use App\Services\LdapAd;
use App\Models\Location;
use Illuminate\Console\Command;
use Adldap\Models\User as AdldapUser;

/**
 * LDAP / AD sync command.
 *
 * @author Wes Hulette <jwhulette@gmail.com>
 *
 * @since 5.0.0
 */
class LdapSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:ldap-sync 
                {--location= : A location name } 
                {--location_id= : A location id} 
                {--base_dn= : A diffrent base DN to use } 
                {--summary : Print summary } 
                {--json_summary : Print summary in json format } 
                {--dryrun : Run the sync process but don\'t update the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command line LDAP/AD sync';

    /**
     * An LdapAd instance.
     *
     * @var \App\Models\LdapAd
     */
    private $ldap;

    /**
     * LDAP settings collection.
     *
     * @var \Illuminate\Support\Collection
     */
    private $settings = null;

    /**
     * A default location collection.
     *
     * @var \Illuminate\Support\Collection
     */
    private $defaultLocation = null;

    /**
     * Mapped locations collection.
     *
     * @var \Illuminate\Support\Collection
     */
    private $mappedLocations = null;

    /**
     * The summary collection.
     *
     * @var \Illuminate\Support\Collection
     */
    private $summary;

    /**
     * Is dry-run?
     *
     * @var bool
     */
    private $dryrun = false;

    /**
     * Show users to be imported.
     *
     * @var array
     */
    private $userlist = [];

    /**
     * Create a new command instance.
     */
    public function __construct(LdapAd $ldap)
    {
        parent::__construct();
        $this->ldap     = $ldap;
        $this->settings = $this->ldap->ldapSettings;
        $this->summary  = collect();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('max_execution_time', '600'); //600 seconds = 10 minutes
        ini_set('memory_limit', '500M');
        $old_error_reporting = error_reporting(); // grab old error_reporting .ini setting, for later re-enablement
        error_reporting($old_error_reporting & ~E_DEPRECATED); // disable deprecation warnings, for LDAP in PHP 7.4 (and greater)

        if ($this->option('dryrun')) {
            $this->dryrun = true;
        }
        $this->checkIfLdapIsEnabled();
        $this->checkLdapConnection();
        $this->setBaseDn();
        $this->getUserDefaultLocation();
        /*
         * Use the default location if set, this is needed for the LDAP users sync page
         */
        if (!$this->option('base_dn') && null == $this->defaultLocation) {
            $this->getMappedLocations();
        }
        $this->processLdapUsers();
        // Print table of users
        if ($this->dryrun) {
            $this->info('The following users will be synced!');
            $headers = ['First Name', 'Last Name', 'Username', 'Email', 'Employee #', 'Location Id', 'Status'];
            $this->table($headers, $this->summary->toArray());
        }

        error_reporting($old_error_reporting); // re-enable deprecation warnings.
        return $this->getSummary();
    }

<<<<<<< HEAD
    /**
     * Generate the LDAP sync summary.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     *
     * @return string
     */
    private function getSummary(): string
    {
        if ($this->option('summary') && null === $this->dryrun) {
            $this->summary->each(function ($item) {
                $this->info('USER: '.$item['note']);

                if ('ERROR' === $item['status']) {
                    $this->error('ERROR: '.$item['note']);
                }
            });
        } elseif ($this->option('json_summary')) {
            $json_summary = [
                'error' => false,
                'error_message' => '',
                'summary' => $this->summary->toArray(),
            ];
            $this->info(json_encode($json_summary));
=======
        try {
            $ldap = new LdapAd();
            $ldap->init();
        } catch (\Exception $e) {
            if ($this->option('json_summary')) {
                $json_summary = [ "error" => true, "error_message" => $e->getMessage(), "summary" => [] ];
                $this->info(json_encode($json_summary));
            }
            LOG::info($e);
            return [];
>>>>>>> 49eb9fa79... ldap php7.4
        }

        return '';
    }

<<<<<<< HEAD
    /**
     * Create a new user or update an existing user.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     *
     * @param \Adldap\Models\User $snipeUser
     */
    private function updateCreateUser(AdldapUser $snipeUser): void
    {
        $user = $this->ldap->processUser($snipeUser, $this->defaultLocation, $this->mappedLocations);
        $summary = [
            'firstname'       => $user->first_name,
            'lastname'        => $user->last_name,
            'username'        => $user->username,
            'employee_number' => $user->employee_num,
            'email'           => $user->email,
            'location_id'     => $user->location_id,
        ];
        // Only update the database if is not a dry run
        if (!$this->dryrun) {
            if ($user->isDirty()) { //if nothing on the user changed, don't bother trying to save anything nor put anything in the summary
                if ($user->save()) {
                    $summary['note']   = ($user->wasRecentlyCreated ? 'CREATED' : 'UPDATED');
                    $summary['status'] = 'SUCCESS';
                } else {
                    $errors = '';
                    foreach ($user->getErrors()->getMessages() as  $error) {
                        $errors .= implode(", ",$error);
                    }
                    $summary['note']   = $snipeUser->getDN().' was not imported. REASON: '.$errors;
                    $summary['status'] = 'ERROR';
                }
=======
        try {
            if ($this->option('base_dn') != '') {
                $ldap->baseDn = $this->option('base_dn');
                LOG::debug('Importing users from specified base DN: \"'.$this->option('base_dn').'\".');
>>>>>>> 49eb9fa79... ldap php7.4
            } else {
                $summary = null;
            }
<<<<<<< HEAD
=======
            $ldapusers = $ldap->getLdapUsers();
            $results = $ldapusers->getResults();
            $results["count"] = $ldapusers->count();
        } catch (\Exception $e) {
            if ($this->option('json_summary')) {
                $json_summary = [ "error" => true, "error_message" => $e->getMessage(), "summary" => [] ];
                $this->info(json_encode($json_summary));
            }
            LOG::info($e);
            return [];
>>>>>>> 49eb9fa79... ldap php7.4
        }

        // $summary['note'] = ($user->getOriginal('username') ? 'UPDATED' : 'CREATED'); // this seems, kinda, like, superfluous, relative to the $summary['note'] thing above, yeah?
        if($summary) { //if the $user wasn't dirty, $summary was set to null so that we will skip the following push()
            $this->summary->push($summary);
        }
    }

    /**
     * Process the users to update / create.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     *
     */
    private function processLdapUsers(): void
    {
        try {
            $ldapUsers = $this->ldap->getLdapUsers();
        } catch (Exception $e) {
            $this->outputError($e);
            exit($e->getMessage());
        }

        if (0 == $ldapUsers->count()) {
            $msg = 'ERROR: No users found!';
            Log::error($msg);
            if ($this->dryrun) {
                $this->error($msg);
            }
            exit($msg);
        }

        // Process each individual users
        foreach ($ldapUsers->getResults() as $user) { // AdLdap2's paginate() method is weird, it gets *everything* and ->getResults() returns *everything*
            $this->updateCreateUser($user);
        }
    }

    /**
     * Get the mapped locations if a base_dn is provided.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function getMappedLocations()
    {
        $ldapOuLocation = Location::where('ldap_ou', '!=', '')->select(['id', 'ldap_ou'])->get();
        $locations = $ldapOuLocation->sortBy(function ($ou, $key) {
            return strlen($ou->ldap_ou);
        });
        if ($locations->count() > 0) {
            $msg = 'Some locations have special OUs set. Locations will be automatically set for users in those OUs.';
            LOG::debug($msg);
            if ($this->dryrun) {
                $this->info($msg);
            }

<<<<<<< HEAD
            $this->mappedLocations = $locations->pluck('ldap_ou', 'id'); // TODO: this seems ok-ish, but the key-> value is going location_id -> OU name, and the primary action here is the opposite of that - going from OU's to location ID's.
        }
    }
=======
            // Grab subsets based on location-specific DNs, and overwrite location for these users.
            foreach ($ldap_ou_locations as $ldap_loc) {
                $location_users = Ldap::findLdapUsers($ldap_loc["ldap_ou"]);
                $usernames = array();
                for ($i = 0; $i < $location_users["count"]; $i++) {

                    if (isset($location_users[$i][$ldap_result_username])) {
                        $location_users[$i]["ldap_location_override"] = true;
                        $location_users[$i]["location_id"] = $ldap_loc["id"];
                        $usernames[] = $location_users[$i][$ldap_result_username][0];
                    }

                }

                // Delete located users from the general group.
                foreach ($results as $key => $generic_entry) {
                   if ((is_array($generic_entry)) && (isset($generic_entry[$ldap_result_username]))) {
                        if (in_array($generic_entry[$ldap_result_username][0], $usernames)) {
                            unset($results[$key]);
                        }
                    }
                }
>>>>>>> 49eb9fa79... ldap php7.4

    /**
     * Set the base dn if supplied.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function setBaseDn(): void
    {
        if ($this->option('base_dn')) {
            $this->ldap->baseDn = $this->option('base_dn');
            $msg = sprintf('Importing users from specified base DN: "%s"', $this->ldap->baseDn);
            LOG::debug($msg);
            if ($this->dryrun) {
                $this->info($msg);
            }
        }
    }

<<<<<<< HEAD
    /**
     * Get a default location id for imported users.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function getUserDefaultLocation(): void
    {
        $location = $this->option('location_id') ?? $this->option('location');
        if ($location) {
            $userLocation = Location::where('name', '=', $location)
                ->orWhere('id', '=', intval($location))
                ->select(['name', 'id'])
                ->first();
            if ($userLocation) {
                $msg = 'Importing users with default location: '.$userLocation->name.' ('.$userLocation->id.')';
                LOG::debug($msg);

                if ($this->dryrun) {
                    $this->info($msg);
                }

                $this->defaultLocation = collect([
                    $userLocation->id => $userLocation->name,
                ]);
            } else {
                $msg = 'The supplied location is invalid!';
                LOG::error($msg);
                if ($this->dryrun) {
                    $this->error($msg);
=======
        /* Create user account entries in Snipe-IT */
        $tmp_pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 20);
        $pass = bcrypt($tmp_pass);

        for ($i = 0; $i < $results["count"]; $i++) {
            if (empty($ldap_result_active_flag) || $results[$i][$ldap_result_active_flag][0] == "TRUE") {

                $item = array();
                $item["username"] = isset($results[$i][$ldap_result_username][0]) ? $results[$i][$ldap_result_username][0] : "";
                $item["employee_number"] = isset($results[$i][$ldap_result_emp_num][0]) ? $results[$i][$ldap_result_emp_num][0] : "";
                $item["lastname"] = isset($results[$i][$ldap_result_last_name][0]) ? $results[$i][$ldap_result_last_name][0] : "";
                $item["firstname"] = isset($results[$i][$ldap_result_first_name][0]) ? $results[$i][$ldap_result_first_name][0] : "";
                $item["email"] = isset($results[$i][$ldap_result_email][0]) ? $results[$i][$ldap_result_email][0] : "" ;
                $item["ldap_location_override"] = isset($results[$i]["ldap_location_override"]) ? $results[$i]["ldap_location_override"][0]:"";
                $item["location_id"] = isset($results[$i]["location_id"]) ? $results[$i]["location_id"]:"";

                $user = User::where('username', $item["username"])->first();
                if ($user) {
                    // Updating an existing user.
                    $item["createorupdate"] = 'updated';
                } else {
                    // Creating a new user.
                    $user = new User;
                    $user->password = $pass;
                    $user->activated = 0;
                    $item["createorupdate"] = 'created';
                }

                $user->first_name = $item["firstname"];
                $user->last_name = $item["lastname"];
                $user->username = $item["username"];
                $user->email = $item["email"];
                $user->employee_num = e($item["employee_number"]);

                // Sync activated state for Active Directory.
                if ( isset($results[$i]['useraccountcontrol']) ) {
                  $enabled_accounts = [
                    '512', '544', '66048', '66080', '262656', '262688', '328192', '328224', '4260352'
                  ];
                  $user->activated = ( in_array($results[$i]['useraccountcontrol'][0], $enabled_accounts) ) ? 1 : 0;
                }

                // If we're not using AD, and there isn't an activated flag set, activate all users
                elseif (empty($ldap_result_active_flag)) {
                  $user->activated = 1;
                }

                if ($item['ldap_location_override'] == true) {
                    $user->location_id = $item['location_id'];
                } elseif ((isset($location)) && (!empty($location))) {

                    if ((is_array($location)) && (isset($location['id']))) {
                        $user->location_id = $location['id'];
                    } elseif (is_object($location)) {
                        $user->location_id = $location->id;
                    }

                }

                $user->ldap_import = 1;

                $errors = '';
                if ($user->save()) {
                    $item["note"] = $item["createorupdate"];
                    $item["status"]='success';
                } else {
                    foreach ($user->getErrors()->getMessages() as $key => $err) {
                        $errors .= $err[0];
                    }
                    $item["note"] = $errors;
                    $item["status"]='error';
>>>>>>> 49eb9fa79... ldap php7.4
                }
                exit(0);
            }
        }
    }

    /**
     * Check if LDAP intergration is enabled.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function checkIfLdapIsEnabled(): void
    {
        if (false === $this->settings['ldap_enabled']) {
            $msg = 'LDAP intergration is not enabled. Exiting sync process.';
            $this->info($msg);
            Log::info($msg);
            exit(0);
        }
    }

    /**
     * Check to make sure we can access the server.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function checkLdapConnection(): void
    {
        try {
            $this->ldap->testLdapAdUserConnection();
            $this->ldap->testLdapAdBindConnection();
        } catch (Exception $e) {
            $this->outputError($e);
            exit(0);
        }
    }

    /**
     * Output the json summary to the screen if enabled.
     *
     * @param Exception $error
     */
    private function outputError($error): void
    {
        if ($this->option('json_summary')) {
            $json_summary = [
                'error' => true,
                'error_message' => $error->getMessage(),
                'summary' => [],
            ];
            $this->info(json_encode($json_summary));
        }
        $this->error($error->getMessage());
        LOG::error($error);
    }
}
