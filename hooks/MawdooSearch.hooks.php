<?php

// Hooks for SavedResults and Search special pages.
class MawdooSearchHooks
{

    // Update database schema by adding new table
    public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater )
    {

        $updater->addExtensionTable( wfMessage('table_name'),
            __DIR__ . '/../sql/table.sql');
        return true;
    }

    // Add assets files to MawdooSearch SpecialPages.
    public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
        $out->addModules( 'ext.mawdooSearch' );
    }

    // Custom hook: on Save results.
    // params: search_result_item: array of search results items.
    // return: true|false
    public static function onSaveResults($search_result_item = [])
    {
        // Get user
        global $wgUser;
        try{

            // Get database.
            $db = wfGetDB(DB_MASTER);
            // get current time.
            $currentDateTs = wfTimestampNow();
            $queryData = [];
            // check if $search_result_item is array and not empty
            if(is_array($search_result_item) && !empty($search_result_item))
            {
                // format selected search results.
                foreach ($search_result_item as $row) {

                    // check if checked attribute is set and comment text is not empty
                    if (isset($row["'checked'"]) && !empty($row["'comment'"])) {
                        $queryData[] = array(
                            'user_id' => $wgUser->getId(),
                            'a_title' => $row["'title'"],
                            'a_link' => $row["'link'"],
                            'a_desc' => $row["'desc'"],
                            'a_comment'=>$row["'comment'"],
                            'created_at' => $currentDateTs
                        );
                    }
                }
            }

            // Bulk insert into table favorite.
            $db->insert(wfMessage('table_name')->parse(),$queryData);
            // Commit db changes.
            $db->commit();
            return true;
        }
        catch (Exception $exception){
            return false;
        }
    }

    // Custom Hook
    // params: $outputPage, $url: redirect url.
    // results:  Add html to page output, true|false.
    public static function getSavedResults(OutputPage $outputPage,$url=null)
    {
        // Get user info.
        global $wgUser;
        try{
            // Get mediawiki database.
            $db = wfGetDB(DB_MASTER);
            // Create select query.
            $results  = $db->select(wfMessage('table_name')->parse(),['id as `id`','a_title as `title`','a_desc as `desc`','a_link as `link`','a_comment as `comment`','created_at'],array('user_id' => $wgUser->getId()),
                __METHOD__,
                array( 'ORDER BY' => 'created_at DESC' ));

            if($results->numRows()>0)
            {
                $rows =[];

                while ($row = $results->next())
                {
                    $rows[] = $row;
                }
                // render saved result items | Html elements.
                // Add html response to page output.
                $htmlResponse = self::renderSavedResults($rows);
                $outputPage->addHTML($htmlResponse);
            }
            else
            {
                // render empty results message | Html elements.
                // Add html response to page output.
                $htmlEmpty = self::emptySavedResult($url);
                $outputPage->addHTML($htmlEmpty);
            }
            return true;
        }
        catch (Exception $exception){
            return false;
        }
    }

    // private function.
    // params: $data:array.
    // return: html elements | html empty results element.
    private static function renderSavedResults($data = [])
    {
        $links = [];
        $response =
            XML::openElement('div',['class'=>'search-results']).
            Xml::openElement('ul');

        try {
            // check if saved results items is array and not empty.
            if (isset($data) && !is_null($data) && is_array($data)) {

                // loop through all items and create html elements.
                foreach ($data as $index => $item) {
                    $links[] = $item->link;

                    $textAreaFieldName = "field_item[$index]['comment']";
                    $titleHiddenFieldName = Html::hidden("field_item[$index]['title']", $item->title);
                    $descHiddenFieldName = Html::hidden("field_item[$index]['desc']", $item->desc);
                    $linkHiddenFieldName = Html::hidden("field_item[$index]['id']", $item->id);

                    $response .=
                        Xml::openElement('li',['data-id'=>$item->id,'class'=>'item']) .

                        XML::element('div', ['class' => 'search-result-item'], null) .
                        XML::element('h3', null, '') .
                        XML::element('a', ["href" => $item->link, "target" => "_blank"], $item->title) .
                        XML::closeElement('a') .
                        XML::closeElement('h3') .
                        XML::element('a', ["href" => $item->link, "target" => "_blank"], $item->link) . XML::closeElement('a') .
                        XML::element('p', null, $item->desc) .

                        XML::openElement('div', ['class' => 'form-textarea']) .
                            XML::element('div',['class'=>'form-control']).
                                XML::label(wfMessage('edit-your-comment'), $textAreaFieldName) .
                            XML::closeElement('div').
                            XML::openElement('div',['class'=>'form-control form-actions']).
                                // Add delete, update actions
                                XML::element('button',['class'=>'delete-item'],wfMessage('delete-item')).
                                XML::element('button',['class'=>'update-item'],wfMessage('update-item')).
                            XML::closeElement('div').
                        XML::closeElement('div') .

                        XML::textarea($textAreaFieldName, $item->comment, 40, 5, ['class' => "comment-text-area"]) .
                        $titleHiddenFieldName .
                        $descHiddenFieldName .
                        $linkHiddenFieldName .
                        XML::closeElement('div') .
                        Xml::closeElement('li');
                }
                $response .= Xml::closeElement('ul');
                $response .= XML::closeElement('div');

                // Create download CSV link.
                $redirect = SpecialPage::getTitleFor('SavedResults')->getFullURL()."?operation=download_csv";
                $response .= Xml::element('a',['href'=>$redirect,'class'=>'download-csv'],wfMessage('download-as-csv'));
                return $response;
            }
        }
        catch (Exception $exception)
        {
            return Xml::element('h3',wfMessage('no-data-found'));
        }
    }

    // private function.
    // params: $url
    // result: html elemet for empty list with redirect link.
    private static function emptySavedResult($url)
    {
        return Xml::openElement('div',['class'=>'empty-list']).
            Xml::element('h2',null,wfMessage('list-is-empty')).
            Xml::element('p',null,wfMessage('no-saved-body')).
            XML::element('a',['href'=>$url],wfMessage('click-here-to-add')).
            Xml::closeElement('div');
    }

    // Custom Hook
    // params
    // $data: array [link,link,link...]
    // $filename: default export.csv
    // $delimiter: default ,
    // return: no return | create CSV file and download it.
    public static function downloadAsCSV($data=[],$filename = "export.csv", $delimiter=",")
    {
        // Set download headers
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'";');

        // open the "output" stream
        $f = fopen('php://output', 'w');

        foreach ($data as $line) {
            fputcsv($f, $line, $delimiter);
        }
        // close file.
        fclose($f);

        exit;
    }
}