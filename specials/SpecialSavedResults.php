<?php

// Special Page: Show saved results.
class SpecialSavedResults extends SpecialPage {

	function __construct() {
		parent::__construct( 'SavedResults');
	}

	function execute( $par ) {
        // Check permissions <execute>.
        // Users should be logged-in.
        // Otherwise: Redirect to login page.
        if ( !$this->getUser()->isAllowed( 'execute' ) ) {
            $this->requireLogin();
            return;
        }

        // Check request type if it is download_csv.
        // Call onDownloadAsCsv Hook.
        if($this->getRequest()->getText('operation')== 'download_csv')
        {
            Hooks::run("onDownloadAsCSV",[array($this->getLinksFromDB())]);
            return;
        }

		$output = $this->getOutput();
		$this->setHeaders();

		// Change page title.
        $this->getOutput()->setPageTitle('Saved Results');

        // Run onLoadSearchResults hook which should print saved results.
        Hooks::run('onLoadSearchResults',[$output,'']);

	}

	//params: no parameters
    // return: array of saved links | false.
	private function getLinksFromDB()
    {
        global $wgUser;
        try{

            $db = wfGetDB(DB_MASTER);
            $rows =[];
            $results  = $db->select(wfMessage('table_name')->parse(),['a_link as `link`'],array('user_id' => $wgUser->getId()),
                __METHOD__,
                array( 'ORDER BY' => 'created_at DESC' ));
            if($results->numRows()>0)
            {
                while ($row = $results->next())
                {
                    $rows[] = $row->link;
                }
            }
            return $rows;
        }
        catch (Exception $exception){
            return false;
        }
    }

    // Set a group name for this page.
    protected function getGroupName() {
        return 'mawdoo3_group';
    }
}
